<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\AssignLeadRequest;
use App\Http\Requests\Lead\StoreLeadActionRequest;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Http\Resources\LeadResource;
use App\Models\Category;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\User;
use App\Services\IndiaMartLeadSyncService;
use App\Services\LeadAssignmentService;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService,
        protected LeadAssignmentService $assignmentService,
        protected IndiaMartLeadSyncService $indiaMartLeadSyncService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Lead::class);

        return $this->leadIndexView('all');
    }

    public function myLeads(Request $request): View
    {
        $this->authorize('viewAny', Lead::class);

        return $this->leadIndexView('my');
    }

    public function allLeads(Request $request): View
    {
        $this->authorize('viewAny', Lead::class);

        return $this->leadIndexView('all');
    }

    protected function leadIndexView(string $scope): View
    {
        return view('leads.index', [
            'leadSources' => LeadSource::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'leadScope' => $scope,
            'pageTitle' => $scope === 'my' ? 'My Leads' : 'All Leads',
            'datatableRoute' => $scope === 'my'
                ? route('leads.my.datatable')
                : route('leads.all.datatable'),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Lead::class);

        return $this->leadDatatable($request, false);
    }

    public function myLeadsDatatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Lead::class);

        return $this->leadDatatable($request, true);
    }

    public function allLeadsDatatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Lead::class);

        return $this->leadDatatable($request, false);
    }

    protected function leadDatatable(Request $request, bool $assignedToCurrentUser): JsonResponse
    {
        $filters = $request->only([
            'search', 'lead_source_id', 'status', 'priority',
            'assigned_to', 'state', 'city', 'date_from', 'date_to',
            'sort_by', 'sort_dir',
        ]);

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

    public function syncIndiaMart(Request $request): JsonResponse
    {
        $this->authorize('create', Lead::class);

        if (! $request->user()->can('indiamart.sync') && ! $request->user()->can('leads.create')) {
            abort(403);
        }

        $result = $this->indiaMartLeadSyncService->sync($request->user());

        $message = $result['total'] === 0
            ? 'Sync complete: no records found in IndiaMART source table.'
            : sprintf(
                'Sync complete: %d inserted, %d skipped (already exist).',
                $result['inserted'],
                $result['skipped']
            );

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $result,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Lead::class);

        return view('leads.create', $this->formData());
    }

    public function store(StoreLeadRequest $request): JsonResponse
    {
        try {
            $lead = $this->leadService->create($request->validated(), $request->user());
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lead created successfully.',
            'data' => new LeadResource($lead),
        ], 201);
    }

    public function show(Lead $lead): View
    {
        $this->authorize('view', $lead);

        $lead->load(['leadSource', 'assignee', 'creator', 'category', 'followups', 'notes.creator']);
        $lead->setRelation('activities', $lead->activities()->with('causer')->latest()->get());

        return view('leads.show', [
            'lead' => $lead,
            'users' => request()->user()->canAccessAdministration()
                ? User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function edit(Lead $lead): View
    {
        $this->authorize('update', $lead);

        return view('leads.edit', array_merge(['lead' => $lead], $this->formData()));
    }

    public function update(UpdateLeadRequest $request, Lead $lead): JsonResponse
    {
        $lead = $this->leadService->update($lead, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Lead updated successfully.',
            'data' => new LeadResource($lead),
        ]);
    }

    public function storeAction(StoreLeadActionRequest $request, Lead $lead): JsonResponse
    {
        $lead = $this->leadService->recordAction($lead, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Lead activity recorded successfully.',
            'data' => new LeadResource($lead),
        ], 201);
    }

    public function destroy(Lead $lead): JsonResponse
    {
        $this->authorize('delete', $lead);

        $this->leadService->delete($lead);

        return response()->json([
            'success' => true,
            'message' => 'Lead deleted successfully.',
        ]);
    }

    public function assign(AssignLeadRequest $request, Lead $lead): JsonResponse
    {
        $assignee = User::query()->findOrFail($request->validated('assigned_to'));

        $lead = $this->assignmentService->assign(
            $lead,
            $assignee,
            $request->user(),
            $request->validated('notes')
        );

        return response()->json([
            'success' => true,
            'message' => 'Lead assigned successfully.',
            'data' => new LeadResource($lead->load(['assignee', 'leadSource'])),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return [
            'leadSources' => LeadSource::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ];
    }
}
