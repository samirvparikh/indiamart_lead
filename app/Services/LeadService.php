<?php

namespace App\Services;

use App\Enums\ActivityType;
use App\Enums\FollowupStatus;
use App\Enums\FollowupType;
use App\Enums\LeadStatus;
use App\Events\LeadCreated;
use App\Events\LeadStatusChanged;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadFollowup;
use App\Models\LeadStatusLog;
use App\Models\Setting;
use App\Models\User;
use App\Support\DatatableSort;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LeadService
{
    public function __construct(
        protected LeadAssignmentService $assignmentService,
    ) {}

    public function generateLeadNumber(): string
    {
        $prefix = Setting::getValue('general', 'lead_number_prefix', config('torq_packaging.lead_number_prefix', 'LD'));
        $sequence = (Lead::withTrashed()->max('id') ?? 0) + 1;

        return sprintf('%s-%s', $prefix, str_pad((string) $sequence, 6, '0', STR_PAD_LEFT));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $creator): Lead
    {
        return DB::transaction(function () use ($data, $creator) {
            $duplicate = $this->findDuplicate(
                $data['indiamart_lead_id'] ?? null,
                $data['mobile'] ?? null,
                $data['email'] ?? null,
                $data['company_name'] ?? null,
            );

            if ($duplicate) {
                throw new InvalidArgumentException('A duplicate lead already exists: '.$duplicate->lead_number);
            }

            $data['lead_number'] = $this->generateLeadNumber();
            $data['created_by'] = $creator->id;
            $data['status'] = $data['status'] ?? LeadStatus::New->value;

            $lead = Lead::query()->create($data);

            $this->logActivity($lead, ActivityType::LeadCreated, 'Lead created', $creator);

            LeadCreated::dispatch($lead, $creator);

            if (! empty($data['assigned_to'])) {
                $assignee = User::query()->findOrFail($data['assigned_to']);
                $this->assignmentService->assign($lead, $assignee, $creator);
            }

            return $lead->fresh(['leadSource', 'assignee', 'category', 'creator']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Lead $lead, array $data, User $user): Lead
    {
        return DB::transaction(function () use ($lead, $data, $user) {
            $originalStatus = $lead->status?->value;
            $originalAssignee = $lead->assigned_to;
            $newStatus = $data['status'] ?? null;
            $newAssignee = $data['assigned_to'] ?? null;

            unset($data['status'], $data['assigned_to']);

            $lead->update($data);
            $lead = $lead->fresh();

            $this->logActivity($lead, ActivityType::Edited, 'Lead updated', $user);

            if ($newStatus && $originalStatus !== $newStatus) {
                $lead = $this->changeStatus($lead, LeadStatus::from($newStatus), $user);
            }

            if ($newAssignee && (int) $newAssignee !== (int) $originalAssignee) {
                $assignee = User::query()->findOrFail($newAssignee);
                $lead = $this->assignmentService->assign($lead, $assignee, $user);
            }

            return $lead->fresh(['leadSource', 'assignee', 'category', 'creator']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordAction(Lead $lead, array $data, User $user): Lead
    {
        return DB::transaction(function () use ($lead, $data, $user) {
            $lead = Lead::query()->lockForUpdate()->findOrFail($lead->id);
            $originalStatus = $lead->status;

            if ($lead->assigned_to === null) {
                $assignee = $user->canAccessAdministration()
                    ? User::query()->where('is_active', true)->findOrFail($data['assigned_to'])
                    : $user;

                $lead = $this->assignmentService->assign(
                    $lead,
                    $assignee,
                    $user,
                    'Assigned automatically from lead action'
                );
            } elseif (
                $user->canAccessAdministration()
                && (int) $data['assigned_to'] !== (int) $lead->assigned_to
            ) {
                $assignee = User::query()->where('is_active', true)->findOrFail($data['assigned_to']);
                $lead = $this->assignmentService->assign($lead, $assignee, $user, 'Reassigned from lead action');
            }

            $requestedStatus = LeadStatus::from($data['status']);

            if ($requestedStatus !== $originalStatus && $requestedStatus !== $lead->status) {
                $lead = $this->changeStatus($lead, $requestedStatus, $user, $data['notes']);
            }

            $actionType = FollowupType::from($data['action_type']);
            $nextFollowupAt = $data['next_followup_at'] ?? null;
            $leadUpdates = ['last_contacted_at' => now()];

            if ($nextFollowupAt) {
                $leadUpdates['next_followup_at'] = $nextFollowupAt;
            }

            $lead->update($leadUpdates);

            LeadFollowup::query()->create([
                'lead_id' => $lead->id,
                'type' => $actionType->value,
                'status' => FollowupStatus::Completed->value,
                'subject' => $actionType->value.' activity',
                'notes' => $data['notes'],
                'scheduled_at' => now(),
                'completed_at' => now(),
                'next_followup_at' => $nextFollowupAt,
                'created_by' => $user->id,
                'assigned_to' => $lead->assigned_to,
            ]);

            $this->logActivity(
                $lead,
                ActivityType::from($actionType->value),
                $data['notes'],
                $user,
                [
                    'action_type' => $actionType->value,
                    'next_followup_at' => $nextFollowupAt,
                ]
            );

            return $lead->fresh(['leadSource', 'assignee', 'category', 'creator']);
        });
    }

    public function delete(Lead $lead): bool
    {
        return (bool) $lead->delete();
    }

    public function changeStatus(Lead $lead, LeadStatus $status, User $user, ?string $notes = null): Lead
    {
        $fromStatus = $lead->status;

        $updateData = ['status' => $status->value];

        if ($status === LeadStatus::Won) {
            $updateData['won_at'] = now();
        }

        if ($status === LeadStatus::Lost) {
            $updateData['lost_at'] = now();
        }

        $lead->update($updateData);
        $lead = $lead->fresh();

        LeadStatusLog::query()->create([
            'lead_id' => $lead->id,
            'from_status' => $fromStatus?->value,
            'to_status' => $status->value,
            'notes' => $notes,
            'changed_by' => $user->id,
        ]);

        $this->logActivity($lead, ActivityType::StatusChanged, "Status changed to {$status->value}", $user, [
            'from' => $fromStatus?->value,
            'to' => $status->value,
        ]);

        LeadStatusChanged::dispatch($lead, $fromStatus, $status, $user);

        return $lead;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = Lead::query()
            ->with(['leadSource', 'assignee', 'creator', 'category']);

        $this->applyFilters($query, $filters);

        DatatableSort::apply($query, $filters, [
            'id', 'lead_number', 'customer_name', 'mobile', 'email', 'status',
            'assigned_to', 'lead_source_id', 'next_followup_at', 'created_at', 'updated_at',
        ], 'id', 'desc');

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Lead
    {
        return Lead::query()->with(['leadSource', 'assignee', 'category'])->find($id);
    }

    public function findByIndiamartId(string $indiamartLeadId): ?Lead
    {
        return Lead::query()->where('indiamart_lead_id', $indiamartLeadId)->first();
    }

    public function findDuplicate(?string $indiamartLeadId, ?string $mobile, ?string $email, ?string $companyName): ?Lead
    {
        if ($indiamartLeadId) {
            $existing = $this->findByIndiamartId($indiamartLeadId);
            if ($existing) {
                return $existing;
            }
        }

        if (! $mobile && ! $email && ! $companyName) {
            return null;
        }

        return Lead::query()
            ->where(function (Builder $builder) use ($mobile, $email, $companyName) {
                $hasCondition = false;

                if ($mobile) {
                    $builder->where('mobile', $mobile);
                    $hasCondition = true;
                }

                if ($email) {
                    $hasCondition
                        ? $builder->orWhere('email', $email)
                        : $builder->where('email', $email);
                    $hasCondition = true;
                }

                if ($companyName) {
                    $hasCondition
                        ? $builder->orWhere('company_name', $companyName)
                        : $builder->where('company_name', $companyName);
                }
            })
            ->first();
    }

    public function logActivity(
        Lead $lead,
        ActivityType $type,
        string $description,
        ?User $user = null,
        ?array $properties = null
    ): LeadActivity {
        return LeadActivity::query()->create([
            'lead_id' => $lead->id,
            'type' => $type->value,
            'title' => $type->value,
            'description' => $description,
            'properties' => $properties,
            'causer_id' => $user?->id,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * @param  Builder<Lead>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('lead_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['lead_source_id'])) {
            $query->where('lead_source_id', $filters['lead_source_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (! empty($filters['state'])) {
            $query->where('state', $filters['state']);
        }

        if (! empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['has_followup'])) {
            $query->whereNotNull('next_followup_at');
        }

        if (! empty($filters['followup_date_from'])) {
            $query->whereDate('next_followup_at', '>=', $filters['followup_date_from']);
        }

        if (! empty($filters['followup_date_to'])) {
            $query->whereDate('next_followup_at', '<=', $filters['followup_date_to']);
        }

        if (! empty($filters['assigned_only']) && ! empty($filters['user_id'])) {
            $query->where('assigned_to', $filters['user_id']);
        }
    }
}
