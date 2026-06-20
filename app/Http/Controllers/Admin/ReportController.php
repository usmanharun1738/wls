<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * List reports with optional filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Report::with('verifier')->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by incident type
        if ($request->filled('type')) {
            $query->where('incident_type', $request->input('type'));
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Search by location or reference ID
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('location', 'like', "%{$search}%")
                    ->orWhere('reference_id', 'like', "%{$search}%");
            });
        }

        $reports = $query->paginate(20);

        return response()->json($reports);
    }

    /**
     * Verify a report.
     */
    public function verify(Report $report): JsonResponse
    {
        if (! $report->isPending()) {
            return response()->json(['message' => 'Report is not pending.'], 422);
        }

        $report->update([
            'status' => 'verified',
            'verified_by' => auth()->id(),
        ]);

        return response()->json(['message' => 'Report verified.', 'report' => $report->fresh()]);
    }

    /**
     * Reject a report.
     */
    public function reject(Report $report): JsonResponse
    {
        if (! $report->isPending()) {
            return response()->json(['message' => 'Report is not pending.'], 422);
        }

        $report->update([
            'status' => 'rejected',
            'verified_by' => auth()->id(),
        ]);

        return response()->json(['message' => 'Report rejected.', 'report' => $report->fresh()]);
    }
}
