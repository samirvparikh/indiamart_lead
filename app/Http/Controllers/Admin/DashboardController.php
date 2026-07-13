<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', \App\Models\Lead::class);

        return view('dashboard.index', [
            'console' => $this->dashboardService->getConsoleData($request->user()),
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Lead::class);

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getConsoleData($request->user()),
        ]);
    }
}
