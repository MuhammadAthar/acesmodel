<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AiModel::with('user:id,name,email');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $models = $query->latest()->paginate(20);

        return response()->json($models);
    }

    public function show(AiModel $aiModel): JsonResponse
    {
        $aiModel->load('user:id,name,email');
        return response()->json($aiModel);
    }

    public function update(Request $request, AiModel $aiModel): JsonResponse
    {
        $data = $request->validate([
            'name'       => ['sometimes', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $aiModel->update($data);

        return response()->json($aiModel);
    }

    public function destroy(AiModel $aiModel): JsonResponse
    {
        $aiModel->delete();
        return response()->json(['message' => 'Model deleted.']);
    }
}
