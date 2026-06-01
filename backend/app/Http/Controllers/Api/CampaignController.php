<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Jobs\GenerateCampaignAssets;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $campaigns = $request->user()->campaigns()
            ->with('brand')
            ->withCount('generatedAssets')
            ->latest()
            ->paginate(20);
        return response()->json($campaigns);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'theme'       => ['nullable', 'string'],
            'brief'       => ['nullable', 'string'],
            'brand_id'    => ['nullable', 'exists:brands,id'],
            'garment_ids' => ['required', 'array', 'min:1'],
            'garment_ids.*' => ['exists:garments,id'],
            'settings'    => ['nullable', 'array'],
        ]);

        // Verify ownership of brand and garments
        if (!empty($data['brand_id'])) {
            $request->user()->brands()->findOrFail($data['brand_id']);
        }
        $request->user()->garments()->whereIn('id', $data['garment_ids'])->get();

        $campaign = $request->user()->campaigns()->create([
            'name'     => $data['name'],
            'theme'    => $data['theme'] ?? null,
            'brief'    => $data['brief'] ?? null,
            'brand_id' => $data['brand_id'] ?? null,
            'settings' => $data['settings'] ?? null,
            'status'   => 'draft',
        ]);

        $campaign->garments()->attach($data['garment_ids']);

        return response()->json($campaign->load('garments'), 201);
    }

    public function show(Request $request, Campaign $campaign): JsonResponse
    {
        if ($campaign->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return response()->json($campaign->load('garments', 'generatedAssets', 'brand'));
    }

    public function generate(Request $request, Campaign $campaign): JsonResponse
    {
        if ($campaign->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'types'        => ['required', 'array'],  // ['photo','video','ad','copy']
            'ai_model_id'  => ['nullable', 'exists:ai_models,id'],
            'subtypes'     => ['nullable', 'array'],  // ['studio','lifestyle','editorial','reels']
            'persona_id'   => ['nullable', 'exists:model_personas,id'],
            'scene_prompt' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($campaign->status === 'generating') {
            return response()->json(['message' => 'Campaign is already generating.'], 409);
        }

        // Check credits up-front — 1 credit is needed per garment × type combination.
        // Actual deduction happens per successfully generated asset in the job.
        $estimatedCost = count($data['types']) * $campaign->garments()->count();

        if ($request->user()->credits < $estimatedCost) {
            return response()->json([
                'message' => "Insufficient credits. This generation needs at least {$estimatedCost} credit(s). You have {$request->user()->credits}.",
            ], 402);
        }

        $campaign->update(['status' => 'generating']);
        GenerateCampaignAssets::dispatch($campaign, $data);

        return response()->json(['message' => 'Campaign generation started.', 'campaign' => $campaign]);
    }

    public function assets(Request $request, Campaign $campaign): JsonResponse
    {
        if ($campaign->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $assets = $campaign->generatedAssets()
            ->when($request->type, fn($q) => $q->where('asset_type', $request->type))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(30);

        return response()->json($assets);
    }

    public function destroy(Request $request, Campaign $campaign): JsonResponse
    {
        if ($campaign->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $campaign->delete();
        return response()->json(['message' => 'Campaign deleted.']);
    }
}
