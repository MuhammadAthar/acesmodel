<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModelPersona;
use App\Models\ModelPersonaPose;
use App\Services\AI\AIServiceManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ModelPersonaController extends Controller
{
    public function index(): JsonResponse
    {
        $personas = ModelPersona::withCount('poses')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        return response()->json($personas);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'gender'      => ['required', 'in:male,female,boy,girl,child,non_binary'],
            'age'         => ['nullable', 'integer', 'min:1', 'max:80'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'ethnicity'   => ['nullable', 'string', 'max:100'],
            'skin_tone'   => ['nullable', 'string', 'max:100'],
            'body_type'   => ['nullable', 'in:slim,athletic,curvy,plus_size,petite,average'],
            'hair'        => ['nullable', 'string', 'max:200'],
            'best_for'    => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'avatar_url'  => ['nullable', 'max:500'],
            'is_active'   => ['boolean'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);
        $persona = ModelPersona::create($data);
        return response()->json($persona, 201);
    }

    public function update(Request $request, ModelPersona $modelPersona): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:100'],
            'gender'      => ['sometimes', 'in:male,female,boy,girl,child,non_binary'],
            'age'         => ['nullable', 'integer', 'min:1', 'max:80'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'ethnicity'   => ['nullable', 'string', 'max:100'],
            'skin_tone'   => ['nullable', 'string', 'max:100'],
            'body_type'   => ['nullable', 'in:slim,athletic,curvy,plus_size,petite,average'],
            'hair'        => ['nullable', 'string', 'max:200'],
            'best_for'    => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'avatar_url'  => ['nullable', 'max:500'],
            'is_active'   => ['boolean'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);
        $modelPersona->update($data);
        return response()->json($modelPersona->fresh());
    }

    public function destroy(ModelPersona $modelPersona): JsonResponse
    {
        $modelPersona->delete();
        return response()->json(['message' => 'Model persona deleted.']);
    }

    // ── POSES ─────────────────────────────────────────────────────────────────

    public function poses(ModelPersona $modelPersona): JsonResponse
    {
        return response()->json($modelPersona->poses()->get());
    }

    public function addPose(Request $request, ModelPersona $modelPersona): JsonResponse
    {
        $data = $request->validate([
            'pose_label' => ['required', 'string', 'max:100'],
            'image'      => ['required', 'file', 'image', 'max:10240'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $path = $request->file('image')
            ->storePublicly('persona-poses/' . $modelPersona->id, 'public');

        $pose = $modelPersona->poses()->create([
            'pose_label' => $data['pose_label'],
            'file_path'  => '/storage/' . $path,
            'sort_order' => $data['sort_order'] ?? $modelPersona->poses()->count(),
        ]);

        if (!$modelPersona->avatar_url) {
            $modelPersona->update(['avatar_url' => $pose->file_path]);
        }

        return response()->json($pose, 201);
    }

    public function deletePose(ModelPersona $modelPersona, ModelPersonaPose $pose): JsonResponse
    {
        if ($pose->persona_id !== $modelPersona->id) abort(404);

        $storagePath = ltrim(str_replace('/storage/', '', $pose->file_path), '/');
        Storage::disk('public')->delete($storagePath);
        $pose->delete();

        return response()->json(['message' => 'Pose deleted.']);
    }

    /** Generate a new pose with AI — uses character_seed + character_lock_prompt for consistency. */
    public function generatePose(Request $request, ModelPersona $modelPersona, AIServiceManager $ai): JsonResponse
    {
        $data = $request->validate([
            'pose_label'    => ['required', 'string', 'max:100'],
            'pose_desc'     => ['required', 'string', 'max:500'],
            'prompt_mode'   => ['nullable', 'in:auto,custom_prompt,custom_json'],
            'custom_prompt' => ['nullable', 'string', 'max:2000'],
            'custom_json'   => ['nullable', 'string', 'max:5000'],
        ]);

        $dbConfig = \App\Models\AiProviderConfig::where('is_active', true)->first();
        if ($dbConfig) $ai = $ai->fromConfig($dbConfig);

        // Init character lock on first pose
        $changed = false;
        if (!$modelPersona->character_lock_prompt) {
            $modelPersona->character_lock_prompt = $modelPersona->toPromptDescription();
            $changed = true;
        }
        if (!$modelPersona->character_seed) {
            $modelPersona->character_seed = rand(1000000, 2147483647);
            $changed = true;
        }
        if ($changed) $modelPersona->save();

        $mode = $data['prompt_mode'] ?? 'auto';
        if ($mode === 'custom_json') {
            $rawInput = json_decode($data['custom_json'] ?? '{}', true);
            if (!is_array($rawInput)) return response()->json(['message' => 'custom_json must be valid JSON.'], 422);
            $prompt  = $rawInput['prompt'] ?? $this->buildPosePrompt($modelPersona, $data['pose_desc']);
            $options = array_diff_key($rawInput, ['prompt' => '']);
        } elseif ($mode === 'custom_prompt') {
            $prompt  = trim($data['custom_prompt'] ?? '');
            if (!$prompt) return response()->json(['message' => 'custom_prompt is required.'], 422);
            $options = $this->defaultOptions();
        } else {
            $prompt  = $this->buildPosePrompt($modelPersona, $data['pose_desc']);
            $options = $this->defaultOptions();
        }

        // Always inject seed for character consistency across poses
        $options['seed'] = $modelPersona->character_seed;

        try {
            $result = $ai->generate($prompt, null, $options);
        } catch (\Throwable $e) {
            Log::error('Pose generation failed', ['error' => $e->getMessage(), 'id' => $modelPersona->id]);
            return response()->json(['message' => 'AI generation failed: ' . $e->getMessage()], 502);
        }

        $outputUrl = $result['output_url'] ?? null;
        if (!$outputUrl) return response()->json(['message' => 'AI returned no image.'], 502);

        $img = Http::timeout(60)->get($outputUrl);
        if ($img->failed()) return response()->json(['message' => 'Could not download generated image.'], 502);

        $filename = 'persona-poses/' . $modelPersona->id . '/' . \Str::slug($data['pose_label']) . '-' . uniqid() . '.jpg';
        Storage::disk('public')->put($filename, $img->body());
        $filePath = '/storage/' . $filename;

        $pose = $modelPersona->poses()->create([
            'pose_label'  => $data['pose_label'],
            'file_path'   => $filePath,
            'prompt_used' => $prompt,
            'sort_order'  => $modelPersona->poses()->count(),
        ]);

        return response()->json($pose, 201);
    }

    // ── AVATAR GENERATION ─────────────────────────────────────────────────────

    public function generateAvatar(Request $request, AIServiceManager $ai): JsonResponse
    {
        $data = $request->validate([
            'persona_id'    => ['nullable', 'exists:model_personas,id'],
            'name'          => ['required', 'string', 'max:100'],
            'gender'        => ['required', 'in:male,female,boy,girl,child,non_binary'],
            'age'           => ['nullable', 'integer', 'min:1', 'max:80'],
            'nationality'   => ['nullable', 'string', 'max:100'],
            'ethnicity'     => ['nullable', 'string', 'max:100'],
            'skin_tone'     => ['nullable', 'string', 'max:100'],
            'body_type'     => ['nullable', 'in:slim,athletic,curvy,plus_size,petite,average'],
            'hair'          => ['nullable', 'string', 'max:200'],
            'description'   => ['nullable', 'string', 'max:1000'],
            'prompt_mode'   => ['nullable', 'in:auto,custom_prompt,custom_json'],
            'custom_prompt' => ['nullable', 'string', 'max:2000'],
            'custom_json'   => ['nullable', 'string', 'max:5000'],
        ]);

        $dbConfig = \App\Models\AiProviderConfig::where('is_active', true)->first();
        if ($dbConfig) $ai = $ai->fromConfig($dbConfig);

        $mode = $data['prompt_mode'] ?? 'auto';
        if ($mode === 'custom_json') {
            $rawInput = json_decode($data['custom_json'] ?? '{}', true);
            if (!is_array($rawInput)) return response()->json(['message' => 'custom_json must be valid JSON.'], 422);
            $prompt  = $rawInput['prompt'] ?? $this->buildAvatarPrompt($data);
            $options = array_diff_key($rawInput, ['prompt' => '']);
        } elseif ($mode === 'custom_prompt') {
            $prompt  = trim($data['custom_prompt'] ?? '');
            if (!$prompt) return response()->json(['message' => 'custom_prompt is required.'], 422);
            $options = $this->defaultOptions();
        } else {
            $prompt  = $this->buildAvatarPrompt($data);
            $options = $this->defaultOptions();
        }

        // Assign a permanent seed that will be reused for all future poses
        $seed = rand(1000000, 2147483647);
        $options['seed'] = $seed;

        try {
            $result = $ai->generate($prompt, null, $options);
        } catch (\Throwable $e) {
            Log::error('Avatar generation failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'AI generation failed: ' . $e->getMessage()], 502);
        }

        $outputUrl = $result['output_url'] ?? null;
        if (!$outputUrl) return response()->json(['message' => 'AI returned no image.'], 502);

        $img = Http::timeout(60)->get($outputUrl);
        if ($img->failed()) return response()->json(['message' => 'Could not download generated image.'], 502);

        $filename  = 'persona-avatars/' . \Str::slug($data['name']) . '-' . uniqid() . '.jpg';
        Storage::disk('public')->put($filename, $img->body());
        $publicUrl = '/storage/' . $filename;

        if (!empty($data['persona_id'])) {
            $persona = ModelPersona::find($data['persona_id']);
            if ($persona) {
                $charPrompt = !empty($data['description'])
                    ? $data['description']
                    : $this->buildSubjectDescription($data);

                $persona->avatar_url            = $publicUrl;
                $persona->character_seed        = $seed;
                $persona->character_lock_prompt = $charPrompt;
                $persona->save();

                // Save as the first pose — always sort_order 0
                $persona->poses()->updateOrCreate(
                    ['pose_label' => 'Portrait', 'sort_order' => 0],
                    ['file_path' => $publicUrl, 'prompt_used' => $prompt]
                );
            }
        }

        return response()->json(['avatar_url' => $publicUrl]);
    }

    // ── PRIVATE HELPERS ───────────────────────────────────────────────────────

    private function defaultOptions(): array
    {
        return [
            'aspect_ratio'        => '2:3',
            'output_format'       => 'jpg',
            'output_quality'      => 90,
            'num_inference_steps' => 4,
            'go_fast'             => false,
            'megapixels'          => '1',
        ];
    }

    private function buildSubjectDescription(array $d): string
    {
        $g = match ($d['gender']) {
            'male'       => 'male',   'female'     => 'female',
            'boy'        => 'young boy', 'girl'    => 'young girl',
            'child'      => 'child',  'non_binary' => 'androgynous person',
            default      => $d['gender'],
        };
        return implode(', ', array_filter([
            "a professional {$g} fashion model",
            isset($d['age'])          ? "{$d['age']} years old"                                : null,
            !empty($d['nationality']) ? "{$d['nationality']} nationality"                      : null,
            !empty($d['ethnicity'])   ? "{$d['ethnicity']} ethnicity"                          : null,
            !empty($d['skin_tone'])   ? "{$d['skin_tone']} skin tone"                          : null,
            !empty($d['body_type'])   ? str_replace('_', ' ', $d['body_type']) . ' build'      : null,
            !empty($d['hair'])        ? $d['hair']                                              : null,
            'elegant posture, confident expression',
        ]));
    }

    private function buildAvatarPrompt(array $data): string
    {
        $subject = !empty($data['description'])
            ? $data['description']
            : $this->buildSubjectDescription($data);

        return implode(', ', [
            "RAW photograph, photorealistic portrait of {$subject}",
            'plain white seamless studio background',
            'soft diffused studio key light, subtle fill light, rim light',
            'Canon EOS R5 Mark II, 85mm f/1.4 prime lens',
            'sharp focus on face, shallow depth of field',
            'hyperrealistic, ultra-detailed, 8K UHD',
            'no text, no watermark, no logo',
        ]);
    }

    private function buildPosePrompt(ModelPersona $persona, string $poseDesc): string
    {
        $subject = $persona->character_lock_prompt ?: $persona->toPromptDescription();
        return implode(', ', [
            "RAW photograph, photorealistic full body shot of {$subject}",
            $poseDesc,
            'plain white seamless studio background',
            'soft diffused key light, rim light',
            'Canon EOS R5 Mark II, 85mm f/1.4',
            'hyperrealistic, ultra-detailed, 8K UHD',
            'no text, no watermark, no logo',
        ]);
    }
}
