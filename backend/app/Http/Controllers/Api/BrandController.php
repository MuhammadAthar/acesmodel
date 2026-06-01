<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($request->user()->brands);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'website'     => ['nullable', 'url'],
            'description' => ['nullable', 'string'],
            'logo'        => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('logo')) {
            $userId = $request->user()->id;
            $data['logo'] = $request->file('logo')->store("clients/{$userId}/brand-assets/logos", 'public');
        }

        $brand = $request->user()->brands()->create($data);
        return response()->json($brand, 201);
    }

    public function show(Request $request, Brand $brand): JsonResponse
    {
        $this->authorize('view', $brand);
        return response()->json($brand);
    }

    public function update(Request $request, Brand $brand): JsonResponse
    {
        $this->authorize('update', $brand);

        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'website'     => ['nullable', 'url'],
            'description' => ['nullable', 'string'],
            'logo'        => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('logo')) {
            if ($brand->logo) {
                Storage::disk('public')->delete($brand->logo);
            }
            $userId = $request->user()->id;
            $data['logo'] = $request->file('logo')->store("clients/{$userId}/brand-assets/logos", 'public');
        }

        $brand->update($data);
        return response()->json($brand);
    }

    public function destroy(Request $request, Brand $brand): JsonResponse
    {
        $this->authorize('delete', $brand);
        $brand->delete();
        return response()->json(['message' => 'Brand deleted.']);
    }

    public function analyzeDna(Request $request, Brand $brand): JsonResponse
    {
        $this->authorize('update', $brand);

        $request->validate([
            'assets'   => ['required', 'array', 'min:1'],
            'assets.*' => ['image', 'max:10240'],
        ]);

        // Queue DNA analysis job
        \App\Jobs\AnalyzeBrandDna::dispatch($brand, $request->file('assets'));

        return response()->json(['message' => 'Brand DNA analysis queued.']);
    }
}
