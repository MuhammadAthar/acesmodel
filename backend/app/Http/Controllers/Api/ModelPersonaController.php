<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ModelPersona;
use Illuminate\Http\JsonResponse;

class ModelPersonaController extends Controller
{
    /** Return all active personas for the Studio model-picker (includes poses). */
    public function index(): JsonResponse
    {
        $personas = ModelPersona::active()
            ->with(['poses' => fn($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->get([
                'id', 'name', 'gender', 'age', 'nationality',
                'ethnicity', 'skin_tone', 'body_type', 'hair',
                'best_for', 'avatar_url', 'sort_order',
            ]);

        return response()->json($personas);
    }

    /** Return a single active persona with all poses. */
    public function show(ModelPersona $modelPersona): JsonResponse
    {
        if (!$modelPersona->is_active) abort(404);

        return response()->json($modelPersona->load('poses'));
    }
}
