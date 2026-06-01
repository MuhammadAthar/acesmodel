<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Campaign;
use App\Models\GeneratedAsset;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $totalUsers        = User::where('role', 'user')->count();
        $newUsersThisMonth = User::where('role', 'user')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $activeSubs   = Subscription::where('status', 'active')->count();
        $totalModels  = AiModel::count();
        $totalAssets  = GeneratedAsset::count();
        $totalCampaigns = Campaign::count();

        $planBreakdown = User::where('role', 'user')
            ->selectRaw('plan, count(*) as count')
            ->groupBy('plan')
            ->pluck('count', 'plan');

        $recentUsers = User::where('role', 'user')
            ->latest()
            ->limit(8)
            ->get(['id', 'name', 'email', 'plan', 'credits', 'created_at']);

        return response()->json([
            'total_users'         => $totalUsers,
            'new_users_this_month'=> $newUsersThisMonth,
            'active_subscriptions'=> $activeSubs,
            'total_ai_models'     => $totalModels,
            'total_assets'        => $totalAssets,
            'total_campaigns'     => $totalCampaigns,
            'plan_breakdown'      => $planBreakdown,
            'recent_users'        => $recentUsers,
        ]);
    }
}
