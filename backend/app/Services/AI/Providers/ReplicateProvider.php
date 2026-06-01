<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\ImageGeneratorInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReplicateProvider implements ImageGeneratorInterface
{
    private string $apiToken;
    private string $baseUrl = 'https://api.replicate.com/v1';

    // Default image model — overridden by admin config
    private string $imageModel = 'black-forest-labs/flux-schnell';
    // Vision model for garment analysis
    private string $visionModel = 'meta/llama-3.2-11b-vision-instruct';
    // Virtual try-on model — cuuupid/idm-vton (versioned)
    private string $tryOnModel        = 'cuuupid/idm-vton';
    private string $tryOnModelVersion = '0513734a452173b8173e907e3a59d19a36266e55b48528559432bd21c7d7e985';
    // Fallback text-to-image model used when try-on is configured but called as text-to-image
    private string $fallbackTextModel = 'black-forest-labs/flux-schnell';

    /**
     * Models that accept a reference image (img2img / kontext).
     * All others are treated as text-to-image only.
     */
    private array $imageInputModels = [
        'flux-kontext',
        'flux-dev',
        'img2img',
    ];

    /**
     * Diverse, neutral full-body base model photos used as the "person" input
     * for virtual try-on. Mix of gender/ethnicity. Falls back to these when
     * no custom model photos are uploaded.
     */
    private array $baseModelPhotos = [
        'female' => [
            'https://images.unsplash.com/photo-1529626455594-4ff0802cfb7e?w=512&h=768&fit=crop&crop=top',
            'https://images.unsplash.com/photo-1469334031218-e382a71b716b?w=512&h=768&fit=crop&crop=top',
            'https://images.unsplash.com/photo-1551803091-e20673f15770?w=512&h=768&fit=crop&crop=top',
            'https://images.unsplash.com/photo-1554568218-0f1715e72254?w=512&h=768&fit=crop&crop=top',
        ],
        'male' => [
            'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=512&h=768&fit=crop&crop=top',
            'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=512&h=768&fit=crop&crop=top',
            'https://images.unsplash.com/photo-1487222477894-8943e31ef7b2?w=512&h=768&fit=crop&crop=top',
        ],
    ];

    public function __construct(?string $apiToken = null, ?string $modelId = null)
    {
        $this->apiToken = $apiToken ?? config('services.replicate.token', '');
        if ($modelId) {
            $this->imageModel = $modelId;
        }
    }

    public function getApiToken(): string { return $this->apiToken; }

    /** Whether the current model supports a reference image input. */
    private function modelSupportsImageInput(): bool
    {
        foreach ($this->imageInputModels as $keyword) {
            if (str_contains(strtolower($this->imageModel), $keyword)) {
                return true;
            }
        }
        return false;
    }

    /** Whether the current model is a virtual try-on model. */
    public function isTryOnModel(): bool
    {
        return str_contains(strtolower($this->imageModel), 'tryon')
            || str_contains(strtolower($this->imageModel), 'try-on')
            || str_contains(strtolower($this->imageModel), 'viton')
            || str_contains(strtolower($this->imageModel), 'idm-vton');
    }

    /**
     * Read a local storage file and return it as a base64 data-URI.
     * This lets Replicate fetch garment images even on localhost (no public URL needed).
     */
    private function toBase64DataUri(string $storagePath): string
    {
        $content  = Storage::disk('public')->get($storagePath);
        $mime     = Storage::disk('public')->mimeType($storagePath) ?: 'image/jpeg';
        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    public function generate(string $prompt, ?string $referenceImagePath = null, array $options = []): array
    {
        // If the configured model is a try-on model, it can't handle text-to-image;
        // fall back to a reliable default.
        $imageModel = $this->isTryOnModel() ? $this->fallbackTextModel : $this->imageModel;

        $input = ['prompt' => $prompt, ...$options];

        // Pass garment as base64 for models that accept a reference image.
        if ($referenceImagePath
            && $this->modelSupportsImageInput()
            && Storage::disk('public')->exists($referenceImagePath)) {
            $input['input_image'] = $this->toBase64DataUri($referenceImagePath);
        }

        $response = Http::withToken($this->apiToken)
            ->post("{$this->baseUrl}/models/{$imageModel}/predictions", [
                'input' => $input,
            ]);

        if ($response->failed()) {
            Log::error('Replicate generate failed', ['body' => $response->body(), 'model' => $imageModel]);
            throw new \RuntimeException('Replicate API error: ' . $response->body());
        }

        $data = $response->json();

        if (empty($data['id'])) {
            throw new \RuntimeException('Replicate returned no prediction id: ' . json_encode($data));
        }

        return $this->pollUntilDone($data['id'], 60);
    }

    /**
     * Virtual try-on: places the garment on a model photo.
     *
     * Uses fashn/tryon on Replicate. Garment image is sent as base64
     * so it works even when the server is on localhost.
     *
     * @param string $garmentImagePath  Storage-disk-relative path to garment image
     * @param string $category          Garment category (dress, shirt, trousers, etc.)
     * @param string $gender            'male' | 'female' (picks a matching base model)
     * @param string|null $baseModelUrl Custom base model photo URL (overrides default pool)
     */
    public function tryOn(
        string  $garmentImagePath,
        string  $category,
        string  $gender             = 'female',
        ?string $baseModelUrl       = null,
        ?string $garmentDescription = null
    ): array {
        if (!Storage::disk('public')->exists($garmentImagePath)) {
            throw new \RuntimeException('Garment image not found: ' . $garmentImagePath);
        }

        $garmentB64 = $this->toBase64DataUri($garmentImagePath);

        // Pick a base model photo
        $pool = $this->baseModelPhotos[strtolower($gender)] ?? $this->baseModelPhotos['female'];
        $modelImageUrl = $baseModelUrl ?? $pool[array_rand($pool)];

        $idmCategory = $this->mapTryOnCategory($category);

        // cuuupid/idm-vton — versioned model, uses POST /v1/predictions with "version" field
        // Retry up to 3 times on 429 rate-limit responses (low-credit accounts burst=1)
        $maxTryOnAttempts = 3;
        $response = null;
        for ($attempt = 1; $attempt <= $maxTryOnAttempts; $attempt++) {
            $response = Http::withToken($this->apiToken)
                ->post("{$this->baseUrl}/predictions", [
                    'version' => $this->tryOnModelVersion,
                    'input'   => [
                        'human_img'    => $modelImageUrl,
                        'garm_img'     => $garmentB64,
                        'garment_des'  => $garmentDescription ?: (ucfirst(str_replace('_', ' ', $category)) . ' garment'),
                        'category'     => $idmCategory,
                        'crop'         => false,
                        'steps'        => 30,
                        'seed'         => rand(0, 9999),
                    ],
                ]);

            if ($response->status() === 429) {
                $retryAfter = (int) ($response->json('retry_after') ?? 10);
                $waitSeconds = max($retryAfter + 2, 12); // always wait at least 12s
                Log::info('Try-on rate limited (429), retrying', [
                    'attempt'      => $attempt,
                    'wait_seconds' => $waitSeconds,
                ]);
                if ($attempt < $maxTryOnAttempts) {
                    sleep($waitSeconds);
                    continue;
                }
            }

            break; // success or non-429 error
        }

        if ($response->failed()) {
            Log::error('Replicate try-on failed', ['body' => $response->body()]);
            throw new \RuntimeException('Replicate try-on API error: ' . $response->body());
        }

        $data = $response->json();

        if (empty($data['id'])) {
            throw new \RuntimeException('Try-on: no prediction id returned: ' . json_encode($data));
        }

        return $this->pollUntilDone($data['id'], 60);
    }

    /** Map garment category string → IDM-VTON category enum (upper_body / lower_body / dresses). */
    private function mapTryOnCategory(string $category): string
    {
        $lower = strtolower($category);
        if (str_contains($lower, 'trouser') || str_contains($lower, 'pant')
            || str_contains($lower, 'skirt')  || str_contains($lower, 'short')
            || str_contains($lower, 'jean')   || str_contains($lower, 'legging')) {
            return 'lower_body';
        }
        if (str_contains($lower, 'dress')    || str_contains($lower, 'jumpsuit')
            || str_contains($lower, 'suit')  || str_contains($lower, 'abaya')
            || str_contains($lower, 'kurta') || str_contains($lower, 'shalwar')
            || str_contains($lower, 'gown')  || str_contains($lower, 'overall')) {
            return 'dresses';
        }
        return 'upper_body';  // shirt, blouse, jacket, hoodie, coat, etc.
    }

    /** Poll a prediction until done or timeout. Returns the standard result array. */
    private function pollUntilDone(string $predictionId, int $maxAttempts = 60, int $intervalSeconds = 5): array
    {
        $attempts = 0;
        while ($attempts < $maxAttempts) {
            sleep($intervalSeconds);
            $statusData = $this->status($predictionId);

            if ($statusData['status'] === 'succeeded') {
                return [
                    'prediction_id' => $predictionId,
                    'status'        => 'succeeded',
                    'output_url'    => $statusData['output_url'],
                ];
            }

            if (in_array($statusData['status'], ['failed', 'canceled'])) {
                throw new \RuntimeException(
                    'Prediction ' . $statusData['status'] . ': ' . ($statusData['error'] ?? 'unknown error')
                );
            }

            $attempts++;
        }

        throw new \RuntimeException('Replicate prediction timed out (prediction_id: ' . $predictionId . ')');
    }

    public function status(string $predictionId): array
    {
        $response = Http::withToken($this->apiToken)
            ->get("{$this->baseUrl}/predictions/{$predictionId}");

        if ($response->failed()) {
            return ['status' => 'failed', 'output_url' => null, 'error' => $response->body()];
        }

        $data = $response->json();

        return [
            'status'     => $data['status'],
            'output_url' => is_array($data['output'] ?? null) ? ($data['output'][0] ?? null) : ($data['output'] ?? null),
            'error'      => $data['error'] ?? null,
        ];
    }

    /**
     * Remove the background from a garment image using Replicate's remove-bg model.
     * Stores the result (PNG with transparent background) back to the public disk.
     *
     * @param  string $storagePath  Disk-relative path of the original garment image
     * @return string               Disk-relative path of the new bg-removed PNG
     */
    public function removeBackground(string $storagePath): string
    {
        $imageData = Storage::disk('public')->exists($storagePath)
            ? $this->toBase64DataUri($storagePath)
            : Storage::disk('public')->url($storagePath);

        $response = Http::withToken($this->apiToken)
            ->post("{$this->baseUrl}/models/lucataco/remove-bg/predictions", [
                'input' => ['image' => $imageData],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('remove-bg API error: ' . $response->body());
        }

        $data = $response->json();
        if (empty($data['id'])) {
            throw new \RuntimeException('remove-bg: no prediction id: ' . json_encode($data));
        }

        // Poll until done
        for ($i = 0; $i < 30; $i++) {
            sleep(3);
            $poll   = Http::withToken($this->apiToken)
                ->get("{$this->baseUrl}/predictions/{$data['id']}")->json();
            $status = $poll['status'] ?? 'starting';

            if ($status === 'succeeded') {
                $outputUrl = is_array($poll['output'] ?? null)
                    ? ($poll['output'][0] ?? null)
                    : ($poll['output'] ?? null);

                if (!$outputUrl) {
                    throw new \RuntimeException('remove-bg succeeded but returned no output URL');
                }

                // Download the PNG and save next to the original
                $pngContent = Http::get($outputUrl)->body();
                $newPath    = preg_replace('/\.[^.]+$/', '_nobg.png', $storagePath);
                Storage::disk('public')->put($newPath, $pngContent);
                return $newPath;
            }

            if (in_array($status, ['failed', 'canceled'])) {
                throw new \RuntimeException('remove-bg prediction ' . $status . ': ' . ($poll['error'] ?? 'unknown'));
            }
        }

        throw new \RuntimeException('remove-bg timed out.');
    }

    public function analyze(string $imagePath, string $instruction): array
    {
        // Send as base64 so analysis works on localhost too
        $imageData = Storage::disk('public')->exists($imagePath)
            ? $this->toBase64DataUri($imagePath)
            : Storage::disk('public')->url($imagePath);

        $response = Http::withToken($this->apiToken)
            ->post("{$this->baseUrl}/models/{$this->visionModel}/predictions", [
                'input' => [
                    'image'  => $imageData,
                    'prompt' => $instruction,
                ],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Replicate Vision API error: ' . $response->body());
        }

        $data          = $response->json();
        $predictionId  = $data['id'];
        $attempts      = 0;

        while ($attempts < 20) {
            sleep(3);
            // Poll the prediction directly so we can access the raw output array
            $poll   = Http::withToken($this->apiToken)
                ->get("{$this->baseUrl}/predictions/{$predictionId}")
                ->json();
            $status = $poll['status'] ?? 'starting';

            if ($status === 'succeeded') {
                $output = $poll['output'] ?? '';
                // LLaVA and similar text models stream tokens as an array — join them all
                if (is_array($output)) {
                    $output = implode('', $output);
                }
                // Extract the JSON object even when surrounded by explanation text
                if (preg_match('/\{.*\}/s', $output, $m)) {
                    $decoded = json_decode($m[0], true);
                    if ($decoded) return $decoded;
                }
                return ['raw' => $output];
            }

            if (in_array($status, ['failed', 'canceled'])) {
                throw new \RuntimeException('Analysis prediction ' . $status . ': ' . ($poll['error'] ?? 'unknown'));
            }
            $attempts++;
        }

        throw new \RuntimeException('Analysis timed out.');
    }

    /**
     * Image-to-image transformation using Flux-dev.
     *
     * Takes a source image URL (e.g. IDM-VTON output) and a text prompt
     * describing the desired scene/style change. Uses strength to control
     * how much the image is altered — lower = more garment/model preserved,
     * higher = more scene change applied.
     *
     * @param  string $sourceImageUrl  Public URL of the source image (e.g. try-on output)
     * @param  string $prompt          Text prompt describing the target scene/style
     * @param  float  $strength        0.0–1.0 — how much to change (0.45 = 45% altered)
     * @return array                   Same format as generate(): ['prediction_id', 'status', 'output_url']
     */
    public function img2img(string $sourceImageUrl, string $prompt, float $strength = 0.45): array
    {
        // Flux-dev supports both text-to-image and image-to-image.
        // We send via the versioned /predictions endpoint with the flux-dev model.
        $response = Http::withToken($this->apiToken)
            ->post("{$this->baseUrl}/models/black-forest-labs/flux-dev/predictions", [
                'input' => [
                    'prompt'               => $prompt,
                    'image'                => $sourceImageUrl,
                    'strength'             => $strength,
                    'num_inference_steps'  => 28,
                    'guidance'             => 3.5,
                    'output_format'        => 'jpg',
                    'output_quality'       => 90,
                    'aspect_ratio'         => 'custom',
                    'width'                => 640,
                    'height'               => 1024,
                ],
            ]);

        if ($response->failed()) {
            Log::error('Replicate img2img failed', ['body' => $response->body()]);
            throw new \RuntimeException('Replicate img2img API error: ' . $response->body());
        }

        $data = $response->json();
        if (empty($data['id'])) {
            throw new \RuntimeException('img2img: no prediction id returned: ' . json_encode($data));
        }

        return $this->pollUntilDone($data['id'], 60);
    }
}

