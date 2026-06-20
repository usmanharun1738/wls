<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Return dashboard statistics.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'total' => Report::count(),
            'pending' => Report::where('status', 'pending')->count(),
            'verified' => Report::where('status', 'verified')->count(),
            'rejected' => Report::where('status', 'rejected')->count(),
            'today' => Report::whereDate('created_at', today())->count(),
        ]);
    }
}
