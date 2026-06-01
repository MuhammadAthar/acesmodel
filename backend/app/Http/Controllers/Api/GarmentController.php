<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Garment;
use App\Jobs\AnalyzeGarment;
use App\Jobs\RemoveGarmentBackground;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GarmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $garments = $request->user()->garments()
            ->with('brand')
            ->latest()
            ->paginate(20);
        return response()->json($garments);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'image'    => ['required', 'image', 'max:20480'],
            'name'     => ['nullable', 'string', 'max:255'],
            'brand_id' => ['nullable', 'exists:brands,id'],
        ]);

        // Verify brand belongs to user
        if (!empty($data['brand_id'])) {
            $request->user()->brands()->findOrFail($data['brand_id']);
        }

        $userId = $request->user()->id;
        $path = $request->file('image')->store("clients/{$userId}/uploads", 'public');

        $garment = $request->user()->garments()->create([
            'name'           => $data['name'] ?? null,
            'brand_id'       => $data['brand_id'] ?? null,
            'original_image' => $path,
        ]);

        // Dispatch AI garment analysis job (sync driver: runs immediately)
        AnalyzeGarment::dispatch($garment);
        RemoveGarmentBackground::dispatch($garment);
        $garment->refresh(); // pick up analyzed=true / processed_image if jobs ran synchronously

        return response()->json($garment, 201);
    }

    public function show(Request $request, Garment $garment): JsonResponse
    {
        if ($garment->user_id !== $request->user()->id) abort(403);
        return response()->json($garment->load('brand', 'generatedAssets'));
    }

    public function retryAnalysis(Request $request, Garment $garment): JsonResponse
    {
        if ($garment->user_id !== $request->user()->id) abort(403);
        if (!$garment->analyzed) {
            AnalyzeGarment::dispatch($garment);
        }
        if (!$garment->processed_image) {
            RemoveGarmentBackground::dispatch($garment);
        }
        $garment->refresh();
        return response()->json($garment);
    }

    public function destroy(Request $request, Garment $garment): JsonResponse
    {
        if ($garment->user_id !== $request->user()->id) abort(403);
        Storage::disk('public')->delete($garment->original_image);
        if ($garment->processed_image) {
            Storage::disk('public')->delete($garment->processed_image);
        }
        $garment->delete();
        return response()->json(['message' => 'Garment deleted.']);
    }
}
