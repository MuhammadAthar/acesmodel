<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeneratedAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $assets = $request->user()->generatedAssets()
            ->when($request->asset_type, fn($q) => $q->where('asset_type', $request->asset_type))
            ->when($request->platform, fn($q) => $q->where('platform', $request->platform))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->with('garment', 'aiModel')
            ->latest()
            ->paginate(30);
        return response()->json($assets);
    }

    public function show(Request $request, GeneratedAsset $asset): JsonResponse
    {
        $this->authorize('view', $asset);
        return response()->json($asset->append('url'));
    }

    public function destroy(Request $request, GeneratedAsset $asset): JsonResponse
    {
        $this->authorize('delete', $asset);
        if ($asset->file_path) {
            Storage::disk('public')->delete($asset->file_path);
        }
        $asset->delete();
        return response()->json(['message' => 'Asset deleted.']);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $assets = $request->user()->generatedAssets()->whereIn('id', $data['ids'])->get();

        foreach ($assets as $asset) {
            if ($asset->file_path) {
                Storage::disk('public')->delete($asset->file_path);
            }
            $asset->delete();
        }

        return response()->json(['message' => count($assets) . ' assets deleted.']);
    }

    public function regenerate(Request $request, GeneratedAsset $asset): JsonResponse
    {
        if ($asset->user_id !== $request->user()->id) {
            abort(403);
        }

        $data = $request->validate([
            'instructions' => ['nullable', 'string', 'max:500'],
        ]);

        // Build prompt: use original, then append user instructions if given
        $originalPrompt = $asset->generation_params['prompt'] ?? '';
        $prompt = $originalPrompt;
        if (!empty($data['instructions'])) {
            $prompt .= '. Also: ' . trim($data['instructions']);
        }

        // Use Flux Schnell — always text-to-image path, picks token from config
        $replicate = new \App\Services\AI\Providers\ReplicateProvider(null, 'black-forest-labs/flux-schnell');
        $subtype   = $asset->asset_subtype;

        try {
            $result = $replicate->generate($prompt, null, [
                'aspect_ratio'        => 'custom',
                'width'               => 640,
                'height'              => 1024,
                'output_format'       => 'jpg',
                'output_quality'      => 90,
                'num_inference_steps' => 4,
                'go_fast'             => false,
                'megapixels'          => '1',
            ]);

            if ($result['status'] === 'succeeded' && $result['output_url']) {
                $imageResponse = Http::get($result['output_url']);
                if ($imageResponse->successful()) {
                    $filename = "clients/{$asset->user_id}/generated/images/{$asset->campaign_id}_{$asset->id}_r.jpg";

                    if ($asset->file_path) {
                        Storage::disk('public')->delete($asset->file_path);
                    }
                    Storage::disk('public')->put($filename, $imageResponse->body());

                    $asset->update([
                        'file_path'         => $filename,
                        'mime_type'         => 'image/jpeg',
                        'status'            => 'ready',
                        'generation_params' => array_merge($asset->generation_params ?? [], [
                            'prompt'         => $prompt,
                            'regenerated_at' => now()->toISOString(),
                        ]),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            $asset->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            return response()->json(['error' => 'Regeneration failed: ' . $e->getMessage()], 500);
        }

        return response()->json($asset->fresh());
    }
}
