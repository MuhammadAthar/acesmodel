<?php

namespace App\Jobs;

use App\Models\AiProviderConfig;
use App\Models\Campaign;
use App\Models\GeneratedAsset;
use App\Services\AI\AIServiceManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateCampaignAssets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 600;

    public function __construct(
        private Campaign $campaign,
        private array    $options
    ) {}

    public function handle(AIServiceManager $ai): void
    {
        set_time_limit(0); // Replicate polling can take up to 5 minutes
        $campaign = $this->campaign->load(['garments', 'brand', 'user']);
        $types    = $this->options['types'] ?? ['photo'];
        $subtypes = $this->options['subtypes'] ?? ['studio'];
        $aiModelId = $this->options['ai_model_id'] ?? null;

        // Load the active AI config for the user's plan from the admin panel
        $tier     = $campaign->user?->plan ?? 'free';
        $dbConfig = AiProviderConfig::where('tier', $tier)
            ->where('is_active', true)
            ->first()
            ?? AiProviderConfig::where('tier', 'free')->where('is_active', true)->first();

        if ($dbConfig) {
            $ai = $ai->fromConfig($dbConfig);
        }

        foreach ($campaign->garments as $garment) {
            foreach ($types as $type) {
                match ($type) {
                    'photo' => $this->generatePhotos($ai, $campaign, $garment, $subtypes, $aiModelId),
                    'copy'  => $this->generateCopy($campaign, $garment),
                    default => null,
                };
            }
        }

        $campaign->update(['status' => 'ready']);
    }

    private function generatePhotos(AIServiceManager $ai, Campaign $campaign, $garment, array $subtypes, ?int $aiModelId): void
    {
        $brand    = $campaign->brand;
        $brandCtx = $brand
            ? "{$brand->name} brand ({$brand->tone} tone, {$brand->photography_style} photography style)"
            : '';

        // Decide pipeline: virtual try-on when garment image exists, text-to-image fallback
        // Prefer the bg-removed processed_image for try-on — dramatically improves IDM-VTON quality.
        $tryOnImage      = ($garment->processed_image && Storage::disk('public')->exists($garment->processed_image))
            ? $garment->processed_image
            : $garment->original_image;
        $hasGarmentImage = $tryOnImage && Storage::disk('public')->exists($tryOnImage);

        $garmentGender = in_array(strtolower($garment->gender ?? ''), ['male', 'men', 'm']) ? 'male' : 'female';
        $garmentDesc   = $this->buildGarmentDescription($garment);

        // Load persona and scene config early — needed before $preferTryOn decision
        $personaId   = $this->options['persona_id']   ?? null;
        $scenePrompt = $this->options['scene_prompt']  ?? null;
        $persona     = $personaId ? \App\Models\ModelPersona::find($personaId) : null;

        // Pipeline priority:
        // 1. IDM-VTON  — persona avatar photo + garment photo → correct face AND correct garment
        //    (best quality; all personas have real avatar photos uploaded in admin)
        //    If scenePrompt set → apply scene via Flux img2img on top of try-on output.
        // 2. Character-locked text-to-image — no seed (seed re-creates original outfit),
        //    just the physical appearance description as model desc → approximates persona look.
        // 3. Plain text-to-image fallback — generic model description.
        $preferTryOn = $hasGarmentImage;

        // Build list of model reference images to use.
        // If the client selected specific pose IDs → use those pose photos.
        // Otherwise → use every uploaded pose for the persona (all poses → full variety).
        // Falls back to avatar_url if no poses stored.
        $poseImages = [];  // array of base64 data URIs

        if ($persona) {
            $requestedPoseIds = $this->options['pose_ids'] ?? [];

            if (!empty($requestedPoseIds)) {
                $poses = \App\Models\ModelPersonaPose::where('persona_id', $persona->id)
                    ->whereIn('id', $requestedPoseIds)
                    ->orderBy('sort_order')
                    ->get();
            } else {
                $poses = $persona->poses()->orderBy('sort_order')->get();
            }

            foreach ($poses as $pose) {
                $sp = ltrim(preg_replace('#^/?storage/#', '', $pose->file_path), '/');
                if (Storage::disk('public')->exists($sp)) {
                    $bytes = Storage::disk('public')->get($sp);
                    $mime  = Storage::disk('public')->mimeType($sp) ?: 'image/jpeg';
                    $poseImages[] = 'data:' . $mime . ';base64,' . base64_encode($bytes);
                }
            }

            // Ultimate fallback: use avatar photo
            if (empty($poseImages) && $persona->avatar_url) {
                $sp = ltrim(preg_replace('#^/?storage/#', '', $persona->avatar_url), '/');
                if (Storage::disk('public')->exists($sp)) {
                    $bytes = Storage::disk('public')->get($sp);
                    $mime  = Storage::disk('public')->mimeType($sp) ?: 'image/jpeg';
                    $poseImages[] = 'data:' . $mime . ';base64,' . base64_encode($bytes);
                }
            }
        }

        // Legacy single-image reference kept for text-to-image fallback path
        $personaModelImage = $poseImages[0] ?? null;

        // Generate one asset per subtype × pose combination.
        // If no pose images are available (non-persona or no uploads) each subtype is
        // generated once with the text-to-image fallback (poseImages is empty → 1 pass).
        $posesToRun = !empty($poseImages) ? $poseImages : [null];

        $requestIndex = 0;
        foreach ($subtypes as $subtype) {
            foreach ($posesToRun as $poseModelImage) {
                // Stagger requests to avoid Replicate rate-limit (burst=1 on low-credit accounts)
                if ($requestIndex > 0) {
                    sleep(15);
                }
                $requestIndex++;

                $prompt = $this->buildPhotoPrompt($garment, $subtype, $brandCtx, $campaign->theme, $garmentGender, $persona, $scenePrompt);

                $pipeline = ($preferTryOn && $poseModelImage) ? 'tryon' : 'text2img';

                $asset = GeneratedAsset::create([
                    'user_id'           => $campaign->user_id,
                    'campaign_id'       => $campaign->id,
                    'garment_id'        => $garment->id,
                    'ai_model_id'       => $aiModelId,
                    'asset_type'        => 'photo',
                    'asset_subtype'     => $subtype,
                    'ai_provider'       => config('services.ai.provider', 'replicate'),
                    'status'            => 'generating',
                    'generation_params' => ['prompt' => $prompt, 'pipeline' => $pipeline],
                ]);

            try {
                $result = null;

                // ── Path 1: IDM-VTON ─────────────────────────────────────────────────────
                // Uses the actual persona avatar photo so the output has the correct real
                // face/appearance AND the correct selected garment physically composited.
                // No seed needed — the real photo provides identity, not a noise pattern.
                if ($preferTryOn) {
                    $tryOnGender = $persona ? $persona->gender : $garmentGender;
                    $usesTryOn   = !$persona || !$persona->isMinor();
                    if ($usesTryOn) {
                        try {
                            $result = $ai->tryOn(
                                $tryOnImage,
                                $garment->category ?? 'upper_body',
                                in_array($tryOnGender, ['male']) ? 'male' : 'female',
                                $poseModelImage,   // current pose photo (varies per iteration)
                                $garmentDesc
                            );
                        } catch (\Throwable $tryOnErr) {
                            Log::warning('Try-on failed, falling back to text-to-image', [
                                'asset_id' => $asset->id,
                                'error'    => $tryOnErr->getMessage(),
                            ]);
                            sleep(15);
                            $result = null;
                        }
                    }

                    // Apply scene via img2img on top of try-on output
                    if ($result && $result['status'] === 'succeeded' && $result['output_url'] && $scenePrompt) {
                        try {
                            $sceneResult = $ai->img2img(
                                $result['output_url'],
                                $scenePrompt . ', fashion photography, full-body portrait, professional lighting',
                                0.48
                            );
                            if ($sceneResult['status'] === 'succeeded' && $sceneResult['output_url']) {
                                $result = $sceneResult;
                            }
                        } catch (\Throwable $sceneErr) {
                            Log::warning('Scene img2img failed, using raw try-on output', [
                                'asset_id' => $asset->id,
                                'error'    => $sceneErr->getMessage(),
                            ]);
                        }
                    }
                }

                // ── Path 2: Text-to-image fallback ───────────────────────────────────────
                // Reached when: no garment image, minor persona, or IDM-VTON failed.
                // character_lock_prompt is used as model description (appearance only, no outfit).
                // NOTE: we intentionally do NOT pass character_seed — seeds bake in the entire
                // original composition including the admin-pose clothing, not just the face.
                if (!$result) {
                    $fallbackAi = new \App\Services\AI\Providers\ReplicateProvider(
                        $ai->getProvider()->getApiToken(),
                        'black-forest-labs/flux-schnell'
                    );
                    $result = $fallbackAi->generate($prompt, null, [
                        'aspect_ratio'        => 'custom',
                        'width'               => 640,
                        'height'              => 1024,
                        'output_format'       => 'jpg',
                        'output_quality'      => 90,
                        'num_inference_steps' => 4,
                        'go_fast'             => false,
                        'megapixels'          => '1',
                    ]);
                }

                $asset->update([
                    'replicate_prediction_id' => $result['prediction_id'],
                    'status'                  => $result['status'] === 'succeeded' ? 'ready' : 'generating',
                ]);

                if ($result['status'] === 'succeeded' && $result['output_url']) {
                    $this->downloadAndStore($asset, $result['output_url']);
                    // Deduct 1 credit only after the image is successfully generated and stored
                    $campaign->user->deductCreditForAsset(1, $asset);
                }

            } catch (\Throwable $e) {
                $asset->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
                Log::error('Photo generation failed', ['asset_id' => $asset->id, 'error' => $e->getMessage()]);
            }
            } // end foreach $posesToRun
        } // end foreach $subtypes
    }

    /**
     * Build a rich, descriptive garment string from all available garment metadata.
     * Used in both the text-to-image prompt and the IDM-VTON garment_des field.
     */
    private function buildGarmentDescription($garment): string
    {
        $colors = !empty($garment->colors)
            ? implode(' and ', array_filter((array) $garment->colors)) . ' colored'
            : null;

        $parts = array_filter([
            $colors,
            $garment->fabric   ? "{$garment->fabric} fabric"   : null,
            $garment->texture  ? "{$garment->texture} texture"  : null,
            $garment->style,
            $garment->category,
            $garment->season   ? "{$garment->season} wear"      : null,
            $garment->name,
        ]);

        $base = implode(', ', $parts) ?: 'fashion garment';

        // Append AI-analysis tags for richer context
        if (!empty($garment->ai_tags)) {
            $tags = array_slice((array) $garment->ai_tags, 0, 5);
            $base .= ', ' . implode(', ', $tags);
        }

        return $base;
    }

    /** Describe the model appearance for text-to-image prompts. */
    private function buildModelDescription(string $gender): string
    {
        return $gender === 'male'
            ? 'a confident athletic male professional fashion model, mid-20s, perfect posture, strong chiseled features'
            : 'a graceful female professional fashion model, mid-20s, elegant posture, perfect figure, clear skin';
    }

    private function generateCopy(Campaign $campaign, $garment): void
    {
        $copyTypes = [
            'description' => "Write a compelling product description for this {$garment->category} ({$garment->style}, {$garment->fabric}). Campaign: {$campaign->name}. Theme: {$campaign->theme}. Return plain text under 150 words.",
            'caption'     => "Write 3 Instagram captions for this fashion product. Campaign theme: {$campaign->theme}. Tone: engaging, trendy. Return as JSON array.",
            'hashtags'    => "Generate 20 relevant Instagram hashtags for this {$garment->category} post. Campaign: {$campaign->theme}. Return as JSON array of strings.",
        ];

        foreach ($copyTypes as $subtype => $prompt) {
            GeneratedAsset::create([
                'user_id'      => $campaign->user_id,
                'campaign_id'  => $campaign->id,
                'garment_id'   => $garment->id,
                'asset_type'   => 'copy',
                'asset_subtype'=> $subtype,
                'content'      => $prompt, // placeholder; real LLM call goes here
                'status'       => 'ready',
            ]);
        }
    }

    /**
     * Build a rich, multi-layered prompt for hyper-realistic, modern,
     * e-commerce-grade fashion photography using all available context.
     *
     * Layers: quality anchors → scene type → model → garment detail →
     *         brand/theme context → camera/lens → lighting → post-processing.
     */
    private function buildPhotoPrompt($garment, string $subtype, string $brandCtx, ?string $theme, string $gender = 'female', ?\App\Models\ModelPersona $persona = null, ?string $scenePrompt = null): string
    {
        $garmentDesc = $this->buildGarmentDescription($garment);
        // Use character_lock_prompt when available — this is the exact prompt saved by admin
        // when generating the model's poses, so it produces the same visual character.
        $modelDesc   = $persona
            ? ($persona->character_lock_prompt ?: $persona->toPromptDescription())
            : $this->buildModelDescription($gender);
        $themeCtx    = $theme    ? ", {$theme} campaign"         : '';
        $brandFull   = $brandCtx ? ", shot for {$brandCtx}"      : '';

        // Universal quality tokens applied to every output
        $q = 'RAW photograph, photorealistic, hyperrealistic, ultra-detailed, 8K UHD, '
           . 'tack-sharp focus on subject and garment, perfect skin texture, true-to-life colors, '
           . 'color-accurate fabrics, visible thread and stitching detail, '
           . 'no blur, no distortion, no digital artifacts, no text, no watermark, '
           . 'professional post-processing, Lightroom-grade color grading';

        $basePrompt = match ($subtype) {

            // ── Studio / E-commerce Product Photography ──────────────────────────
            'studio' =>
                "{$q}, professional e-commerce fashion product photography, "
                . "{$modelDesc} wearing {$garmentDesc}{$brandFull}{$themeCtx}, "
                . 'pure white seamless infinity cove studio background, full-body centered composition, '
                . 'soft even three-point studio softbox lighting: '
                .   'large 150cm octabox key light at 45° left, two 60cm strip softbox fill lights, '
                .   'hair/rim light from behind, white reflector underneath for shadow elimination, '
                . 'catchlights visible in both eyes, perfectly pressed garment with clean drape and silhouette, '
                . 'Canon EOS R5 Mark II, 85mm f/1.8L prime lens, ISO 100, 1/200s shutter, '
                . 'commercial retail catalog standard: Shopify / ASOS / Net-a-Porter / Nordstrom product page quality, '
                . 'garment is the hero — every seam, button, zipper and fabric texture crystal clear',

            // ── Lifestyle / Outdoor / Street ─────────────────────────────────────
            'lifestyle' =>
                "{$q}, editorial lifestyle fashion photography, "
                . "{$modelDesc} wearing {$garmentDesc}{$brandFull}{$themeCtx}, "
                . 'modern urban outdoor environment: glass-facade office buildings, cobblestone pedestrian street, '
                .   'or sunlit cafe terrace with potted plants — real-world authentic setting, '
                . 'warm golden-hour directional sunlight at 45° from camera left, '
                . 'relaxed confident mid-stride walking or leaning-against-wall pose, '
                . 'Sony A7 IV mirrorless, 35mm f/2.0 Zeiss Batis lens, '
                . 'natural creamy out-of-focus bokeh on background buildings, '
                . 'vibrant true-to-life garment colors, no overexposure, '
                . 'Vogue Runway street-style energy, cinematic warm-neutral color grade, '
                . 'looks like a real photograph taken by a top fashion photographer',

            // ── Editorial / Magazine / Campaign ──────────────────────────────────
            'editorial' =>
                "{$q}, high-fashion editorial photography, "
                . "{$modelDesc} wearing {$garmentDesc}{$brandFull}{$themeCtx}, "
                . 'dramatic Rembrandt chiaroscuro lighting: single large Fresnel key at 45° casting deep artistic shadows, '
                .   'subtle silver-reflector fill from opposite side, '
                . 'minimalist poured-concrete studio backdrop or abstract architectural brutalist environment, '
                . 'powerful avant-garde model pose — strong hands-on-hips or crossed-arms angular attitude, '
                . 'Hasselblad H6D-100c medium-format camera, 80mm f/2.8 HC lens, '
                . 'Vogue / Harpers Bazaar / W Magazine / i-D editorial quality, '
                . 'cinematic teal-orange desaturated color grade, '
                . 'ultra-detailed luxury couture fabric rendering, '
                . 'fashion week campaign energy — looks like a $50,000 print ad',

            // ── Social Media / Reels / TikTok / Instagram ─────────────────────────
            'reels' =>
                "{$q}, Instagram Reels and TikTok fashion content photography, "
                . "{$modelDesc} wearing {$garmentDesc}{$brandFull}{$themeCtx}, "
                . 'vertical 9:16 portrait composition optimised for mobile viewing, '
                . 'bright punchy vibrant trendy aesthetic with bold color palette, '
                . 'modern urban street setting: colorful graffiti mural wall, neon sign backdrop, '
                .   'or pastel-painted building facade — Gen-Z friendly environment, '
                . 'bright natural diffused daylight with subtle fill-flash to eliminate shadows, '
                . 'energetic authentic candid fashion influencer energy, '
                . 'Fujifilm X-T5 film-simulation aesthetic (Provia/Standard), '
                . 'looks like a top-tier 10M-follower Instagram fashion influencer post, '
                . 'scroll-stopping viral-worthy visual composition',

            default =>
                "{$q}, professional fashion photography, "
                . "{$modelDesc} wearing {$garmentDesc}{$brandFull}{$themeCtx}, "
                . 'professional studio three-point lighting, full-body shot, '
                . 'commercial photography quality, Shopify product page standard',
        };

        // Append scene prompt only for the text-to-image fallback path (when try-on
        // wasn't possible). When the two-step pipeline is used, scene is applied via
        // img2img in step 2 and does not need to be in the text prompt.
        if ($scenePrompt) {
            $basePrompt .= ', ' . $scenePrompt;
        }

        return $basePrompt;
    }

    private function downloadAndStore(GeneratedAsset $asset, string $url): void
    {
        $response = Http::get($url);
        if ($response->failed()) {
            return;
        }

        $userId   = $asset->user_id;
        $typeDir  = match ($asset->asset_type) {
            'video'  => 'videos',
            'ad'     => 'ads',
            'social' => 'social',
            default  => 'images',
        };
        $ext      = $asset->asset_type === 'video' ? 'mp4' : 'jpg';
        $filename = "clients/{$userId}/generated/{$typeDir}/{$asset->campaign_id}_{$asset->id}.{$ext}";
        Storage::disk('public')->put($filename, $response->body());

        $asset->update([
            'file_path' => $filename,
            'mime_type' => $asset->asset_type === 'video' ? 'video/mp4' : 'image/jpeg',
            'status'    => 'ready',
        ]);
    }
}
