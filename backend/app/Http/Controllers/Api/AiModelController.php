<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AiModelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $models = $request->user()->aiModels()
            ->with('brand')
            ->orderBy('is_default', 'desc')
            ->latest()
            ->get();
        return response()->json($models);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'ethnicity'    => ['nullable', 'string'],
            'gender'       => ['nullable', 'in:male,female,non-binary'],
            'age_range'    => ['nullable', 'string'],
            'body_type'    => ['nullable', 'string'],
            'hair'         => ['nullable', 'string'],
            'skin_tone'    => ['nullable', 'string'],
            'style_tags'   => ['nullable', 'array'],
            'brand_id'     => ['nullable', 'exists:brands,id'],
            'preview_image'=> ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('preview_image')) {
            $data['preview_image'] = $request->file('preview_image')->store('ai-models/previews', 'public');
        }

        $model = $request->user()->aiModels()->create($data);
        return response()->json($model, 201);
    }

    public function show(Request $request, AiModel $aiModel): JsonResponse
    {
        $this->authorize('view', $aiModel);
        return response()->json($aiModel);
    }

    public function update(Request $request, AiModel $aiModel): JsonResponse
    {
        $this->authorize('update', $aiModel);

        $data = $request->validate([
            'name'         => ['sometimes', 'string', 'max:255'],
            'ethnicity'    => ['nullable', 'string'],
            'gender'       => ['nullable', 'in:male,female,non-binary'],
            'age_range'    => ['nullable', 'string'],
            'body_type'    => ['nullable', 'string'],
            'hair'         => ['nullable', 'string'],
            'skin_tone'    => ['nullable', 'string'],
            'style_tags'   => ['nullable', 'array'],
            'is_default'   => ['nullable', 'boolean'],
        ]);

        $aiModel->update($data);
        return response()->json($aiModel);
    }

    public function destroy(Request $request, AiModel $aiModel): JsonResponse
    {
        $this->authorize('delete', $aiModel);
        if ($aiModel->preview_image) {
            Storage::disk('public')->delete($aiModel->preview_image);
        }
        $aiModel->delete();
        return response()->json(['message' => 'AI Model deleted.']);
    }
}
