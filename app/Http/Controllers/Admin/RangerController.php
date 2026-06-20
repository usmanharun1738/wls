<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ranger;
use Illuminate\Http\JsonResponse;

class RangerController extends Controller
{
    /**
     * List all rangers.
     */
    public function index(): JsonResponse
    {
        $rangers = Ranger::orderBy('name')->get();

        return response()->json($rangers);
    }
}
