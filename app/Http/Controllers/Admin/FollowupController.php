<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeadResource;
use App\Models\LeadSource;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowupController extends Controller
{
    public function __construct(
        protected LeadService $leadService,
    ) {}

    public function index(Request $request): View
    {
        return $this->followupIndex($request, 'my');
    }

    public function myFollowups(Request $request): View
    {
        return $this->followupIndex($request, 'my');
    }

    public function allFollowups(Request $request): View
    {
        return $this->followupIndex($request, 'all');
    }

    public function myFollowupsDatatable(Request $request): JsonResponse
    {
        return $this->followupDatatable($request, true);
    }

    public function allFollowupsDatatable(Request $request): JsonResponse
    {
        return $this->followupDatatable($request, false);
    }

    protected function followupIndex(Request $request, string $scope): View
    {
        abort_unless($request->user()->can('followups.view'), 403);

        return view('followups.index', [
            'leadSources' => LeadSource::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'followupScope' => $scope,
            'pageTitle' => $scope === 'my' ? 'My Followups' : 'All Followups',
            'datatableRoute' => $scope === 'my'
                ? route('followups.my.datatable')
                : route('followups.all.datatable'),
        ]);
    }

    protected function followupDatatable(Request $request, bool $assignedToCurrentUser): JsonResponse
    {
        abort_unless($request->user()->can('followups.view'), 403);

        $filters = $request->only([
            'search', 'lead_source_id', 'status', 'followup_date_from',
            'followup_date_to', 'sort_by', 'sort_dir',
        ]);
        $filters['has_followup'] = true;
        $filters['sort_by'] = $filters['sort_by'] ?? 'next_followup_at';
        $filters['sort_dir'] = $filters['sort_dir'] ?? 'desc';

        if ($assignedToCurrentUser) {
            $filters['assigned_only'] = true;
            $filters['user_id'] = $request->user()->id;
        }

        $leads = $this->leadService->list($filters, (int) $request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => LeadResource::collection($leads->items()),
            'meta' => [
                'current_page' => $leads->currentPage(),
                'last_page' => $leads->lastPage(),
                'per_page' => $leads->perPage(),
                'total' => $leads->total(),
            ],
        ]);
    }
}
