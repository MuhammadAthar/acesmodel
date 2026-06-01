<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::where('role', 'user')
            ->withCount(['campaigns', 'aiModels', 'generatedAssets']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($plan = $request->input('plan')) {
            $query->where('plan', $plan);
        }

        $users = $query->latest()->paginate(20);

        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        $user->loadCount(['campaigns', 'aiModels', 'generatedAssets']);
        $user->load(['subscriptions' => fn($q) => $q->latest()->limit(5)]);

        return response()->json($user);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name'    => ['sometimes', 'string', 'max:255'],
            'email'   => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'plan'    => ['sometimes', 'in:free,starter,growth,agency,enterprise'],
            'credits' => ['sometimes', 'integer', 'min:0'],
            'role'    => ['sometimes', 'in:user,superadmin'],
        ]);

        $user->update($data);

        return response()->json($user);
    }

    public function updatePassword(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'password' => ['required', Password::min(8)],
        ]);

        $user->update(['password' => Hash::make($data['password'])]);

        return response()->json(['message' => 'Password updated.']);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->isSuperAdmin()) {
            return response()->json(['message' => 'Cannot delete a superadmin.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }

    public function grantCredits(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'credits' => ['required', 'integer', 'min:1'],
        ]);

        $user->increment('credits', $data['credits']);

        return response()->json(['message' => 'Credits granted.', 'credits' => $user->fresh()->credits]);
    }
}
