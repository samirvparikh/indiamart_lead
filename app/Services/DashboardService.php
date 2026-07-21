<?php

namespace App\Services;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * @return array<string, int|float>
     */
    public function getStats(?User $user = null): array
    {
        $query = Lead::query();

        if ($user && ! $user->can('leads.view') && $user->can('dashboard.view')) {
            $query->where('assigned_to', $user->id);
        } elseif ($user && ! $user->seesUnrestrictedRecords()) {
            $query->where('assigned_to', $user->id);
        }

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $weekStart = Carbon::now()->startOfWeek();
        $monthStart = Carbon::now()->startOfMonth();

        $base = clone $query;

        $totalLeads = (clone $base)->count();
        $todayLeads = (clone $base)->whereDate('created_at', $today)->count();
        $yesterdayLeads = (clone $base)->whereDate('created_at', $yesterday)->count();
        $weeklyLeads = (clone $base)->where('created_at', '>=', $weekStart)->count();
        $monthlyLeads = (clone $base)->where('created_at', '>=', $monthStart)->count();

        $wonLeads = (clone $base)->where('status', LeadStatus::Won->value)->count();
        $lostLeads = (clone $base)->where('status', LeadStatus::Lost->value)->count();
        $openLeads = (clone $base)->active()->count();
        $contactedLeads = (clone $base)->where('status', LeadStatus::Contacted->value)->count();
        $todayRejectedLeads = (clone $base)
            ->where('status', LeadStatus::Lost->value)
            ->whereDate('lost_at', $today)
            ->count();
        $todayFollowups = (clone $base)
            ->active()
            ->whereDate('next_followup_at', $today)
            ->count();
        $overdueFollowups = (clone $base)
            ->active()
            ->whereNotNull('next_followup_at')
            ->where('next_followup_at', '<', $today->copy()->startOfDay())
            ->count();
        $unassignedLeads = (clone $base)
            ->active()
            ->whereNull('assigned_to')
            ->count();
        $pendingFollowups = (clone $base)->whereNotNull('next_followup_at')
            ->where('next_followup_at', '<=', now())
            ->whereNotIn('status', [LeadStatus::Won->value, LeadStatus::Lost->value, LeadStatus::Junk->value])
            ->count();

        $revenue = (clone $base)->where('status', LeadStatus::Won->value)->sum('won_value');
        $conversionRate = $totalLeads > 0 ? round(($wonLeads / $totalLeads) * 100, 2) : 0;

        return [
            'today_leads' => $todayLeads,
            'yesterday_leads' => $yesterdayLeads,
            'weekly_leads' => $weeklyLeads,
            'monthly_leads' => $monthlyLeads,
            'total_leads' => $totalLeads,
            'open_leads' => $openLeads,
            'contacted_leads' => $contactedLeads,
            'today_rejected_leads' => $todayRejectedLeads,
            'pending_followups' => $pendingFollowups,
            'today_followups' => $todayFollowups,
            'overdue_followups' => $overdueFollowups,
            'unassigned_leads' => $unassignedLeads,
            'won_leads' => $wonLeads,
            'lost_leads' => $lostLeads,
            'conversion_rate' => $conversionRate,
            'revenue' => (float) $revenue,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getLeadSourceChart(): array
    {
        return Lead::query()
            ->select('lead_source_id', DB::raw('count(*) as total'))
            ->with('leadSource:id,name,color')
            ->groupBy('lead_source_id')
            ->get()
            ->map(fn ($row) => [
                'source' => $row->leadSource?->name ?? 'Unknown',
                'color' => $row->leadSource?->color ?? '#6c757d',
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getLeadStatusChart(): array
    {
        return Lead::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getTopSalesExecutives(int $limit = 5): array
    {
        return Lead::query()
            ->selectRaw('assigned_to, count(*) as total, sum(case when status = ? then 1 else 0 end) as won', [LeadStatus::Won->value])
            ->whereNotNull('assigned_to')
            ->groupBy('assigned_to')
            ->orderByDesc('won')
            ->limit($limit)
            ->with('assignee:id,name')
            ->get()
            ->map(fn ($row) => [
                'user' => $row->assignee?->name ?? 'Unknown',
                'total_leads' => (int) $row->total,
                'won_leads' => (int) $row->won,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function getConsoleData(?User $user = null): array
    {
        $stats = $this->getStats($user);
        $query = $this->scopedLeadQuery($user);

        $monthStart = Carbon::now()->startOfMonth();
        $prevMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $prevMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $currentRevenue = (float) (clone $query)
            ->where('status', LeadStatus::Won->value)
            ->where('created_at', '>=', $monthStart)
            ->sum('won_value');

        $prevRevenue = (float) (clone $query)
            ->where('status', LeadStatus::Won->value)
            ->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])
            ->sum('won_value');

        $currentLost = (clone $query)
            ->where('status', LeadStatus::Lost->value)
            ->where('created_at', '>=', $monthStart)
            ->count();

        $prevLost = (clone $query)
            ->where('status', LeadStatus::Lost->value)
            ->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])
            ->count();

        $currentWon = (clone $query)
            ->where('status', LeadStatus::Won->value)
            ->where('created_at', '>=', $monthStart)
            ->count();

        $prevWon = (clone $query)
            ->where('status', LeadStatus::Won->value)
            ->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])
            ->count();

        return [
            'stats' => $stats,
            'metrics' => [
                [
                    'key' => 'revenue',
                    'label' => 'Total Revenue',
                    'value' => '₹'.number_format($stats['revenue'], 0),
                    'change' => $this->percentChange($currentRevenue, $prevRevenue),
                    'trend' => $currentRevenue >= $prevRevenue ? 'up' : 'down',
                    'icon' => 'bi-currency-rupee',
                    'tone' => 'blue',
                    'spark' => $this->getSparkline($user, 'revenue'),
                ],
                [
                    'key' => 'lost_leads',
                    'label' => 'Lost Leads',
                    'value' => number_format($stats['lost_leads']),
                    'change' => $this->percentChange($currentLost, $prevLost),
                    'trend' => $currentLost <= $prevLost ? 'up' : 'down',
                    'icon' => 'bi-graph-down-arrow',
                    'tone' => 'red',
                    'spark' => $this->getSparkline($user, 'lost'),
                ],
                [
                    'key' => 'conversion_rate',
                    'label' => 'Conversion Rate',
                    'value' => $stats['conversion_rate'].'%',
                    'change' => $this->percentChange($currentWon, $prevWon),
                    'trend' => $currentWon >= $prevWon ? 'up' : 'down',
                    'icon' => 'bi-percent',
                    'tone' => 'green',
                    'spark' => $this->getSparkline($user, 'won'),
                ],
                [
                    'key' => 'pending_followups',
                    'label' => 'Pending Followups',
                    'value' => number_format($stats['pending_followups']),
                    'change' => $this->percentChange($stats['pending_followups'], max(1, $stats['today_followups'])),
                    'trend' => 'down',
                    'icon' => 'bi-clock-history',
                    'tone' => 'orange',
                    'spark' => $this->getSparkline($user, 'followups'),
                ],
            ],
            'monthly_trend' => $this->getMonthlyTrend($user),
            'lead_sources' => $this->getLeadSourceChart(),
            'lead_statuses' => $this->getLeadStatusChart(),
            'operational_health' => $this->getOperationalHealth($stats),
        ];
    }

    /**
     * @return array{labels: list<string>, new_leads: list<int>, won_leads: list<int>, revenue: list<float>, lost_leads: list<int>}
     */
    public function getMonthlyTrend(?User $user = null, int $months = 9): array
    {
        $query = $this->scopedLeadQuery($user);
        $labels = [];
        $newLeads = [];
        $wonLeads = [];
        $revenue = [];
        $lostLeads = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = Carbon::now()->subMonths($i)->startOfMonth();
            $end = Carbon::now()->subMonths($i)->endOfMonth();
            $labels[] = $start->format('M');

            $monthQuery = (clone $query)->whereBetween('created_at', [$start, $end]);
            $newLeads[] = (clone $monthQuery)->count();
            $wonLeads[] = (clone $monthQuery)->where('status', LeadStatus::Won->value)->count();
            $revenue[] = (float) (clone $monthQuery)->where('status', LeadStatus::Won->value)->sum('won_value');
            $lostLeads[] = (clone $monthQuery)->where('status', LeadStatus::Lost->value)->count();
        }

        return [
            'labels' => $labels,
            'new_leads' => $newLeads,
            'won_leads' => $wonLeads,
            'revenue' => $revenue,
            'lost_leads' => $lostLeads,
        ];
    }

    /**
     * @return list<int|float>
     */
    public function getSparkline(?User $user, string $type, int $points = 12): array
    {
        $query = $this->scopedLeadQuery($user);
        $data = [];

        for ($i = $points - 1; $i >= 0; $i--) {
            $start = Carbon::now()->subMonths($i)->startOfMonth();
            $end = Carbon::now()->subMonths($i)->endOfMonth();
            $monthQuery = (clone $query)->whereBetween('created_at', [$start, $end]);

            $data[] = match ($type) {
                'revenue' => (float) (clone $monthQuery)->where('status', LeadStatus::Won->value)->sum('won_value') / 100000,
                'lost' => (clone $monthQuery)->where('status', LeadStatus::Lost->value)->count(),
                'won' => (clone $monthQuery)->where('status', LeadStatus::Won->value)->count(),
                'followups' => (clone $monthQuery)->whereNotNull('next_followup_at')->count(),
                default => (clone $monthQuery)->count(),
            };
        }

        return $data;
    }

    /**
     * @param  array<string, int|float>  $stats
     * @return array{score: float, label: string}
     */
    public function getOperationalHealth(array $stats): array
    {
        $conversion = (float) ($stats['conversion_rate'] ?? 0);
        $followupPenalty = min(30, ((int) ($stats['pending_followups'] ?? 0)) * 2);
        $score = max(0, min(100, round($conversion + 40 - $followupPenalty, 1)));

        $label = match (true) {
            $score >= 80 => 'Excellent',
            $score >= 60 => 'Good',
            $score >= 40 => 'Fair',
            default => 'Needs Attention',
        };

        return ['score' => $score, 'label' => $label];
    }

    protected function scopedLeadQuery(?User $user)
    {
        $query = Lead::query();

        if ($user && ! $user->can('leads.view') && $user->can('dashboard.view')) {
            $query->where('assigned_to', $user->id);
        } elseif ($user && ! $user->seesUnrestrictedRecords()) {
            $query->where('assigned_to', $user->id);
        }

        return $query;
    }

    protected function percentChange(float|int $current, float|int $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
