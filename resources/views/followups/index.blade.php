@extends('layouts.crm')

@section('title', $pageTitle)

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Home</a>
    <span>&rsaquo;</span>
    <a href="{{ route('followups.index') }}">Followups</a>
    <span>&rsaquo;</span> {{ $pageTitle }}
@endsection

@section('toolbar')
    <div class="crm-page-title">
        <i class="bi {{ $followupScope === 'my' ? 'bi-person-check' : 'bi-calendar2-check' }}"></i> {{ $pageTitle }}
    </div>
    <div class="crm-toolbar-actions">
        <button type="button" class="crm-btn" id="followup-refresh-btn">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
        @can('create', App\Models\Lead::class)
            <a href="{{ route('leads.create') }}" class="crm-btn crm-btn-primary-sm">
                <i class="bi bi-plus-lg"></i> Create Lead
            </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="crm-content-card">
        <div class="crm-content-card-body">
            <div class="crm-filters">
                <input type="text" id="followup-search" class="crm-input" placeholder="Search followups...">
                <input type="date" id="followup-date-from" class="crm-input" title="Follow-up from date">
                <input type="date" id="followup-date-to" class="crm-input" title="Follow-up to date">
                <select id="followup-status" class="crm-input">
                    <option value="">All Status</option>
                    @foreach (App\Enums\LeadStatus::cases() as $status)
                        <option value="{{ $status->value }}">{{ $status->value }}</option>
                    @endforeach
                </select>
                <select id="followup-source" class="crm-input">
                    <option value="">All Sources</option>
                    @foreach ($leadSources as $source)
                        <option value="{{ $source->id }}">{{ $source->name }}</option>
                    @endforeach
                </select>
                <button type="button" id="followup-filter-btn" class="crm-btn crm-btn-primary-sm">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <button type="button" id="followup-reset-btn" class="crm-btn">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>

        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th data-sort="lead_number" data-dir="asc">Lead #</th>
                        <th data-sort="customer_name" data-dir="asc">Customer</th>
                        <th data-sort="mobile" data-dir="asc">Mobile</th>
                        <th data-sort="status" data-dir="asc">Status</th>
                        <th data-sort="assigned_to" data-dir="asc">Assigned</th>
                        <th data-sort="next_followup_at" data-dir="desc">Next Followup Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="followups-table-body"></tbody>
            </table>
        </div>

        <div class="crm-table-footer">
            <span id="followups-record-info">Showing 0 records</span>
            <div class="crm-pagination" id="followups-pagination"></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById('followups-table-body');
    const pagination = document.getElementById('followups-pagination');
    const recordInfo = document.getElementById('followups-record-info');
    const tableEl = document.querySelector('.crm-table');
    const tableSort = CrmTable.create({ sort_by: 'next_followup_at', sort_dir: 'desc' });
    let currentPage = 1;

    const statusBadge = (status) => {
        const map = { 'Won': 'success', 'Lost': 'danger', 'New': 'info', 'Assigned': 'warning' };
        return `<span class="crm-badge crm-badge-${map[status] || 'secondary'}">${status}</span>`;
    };

    const formatFollowupDate = (value) => {
        if (!value) return '-';
        return new Intl.DateTimeFormat('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date(value));
    };

    async function loadFollowups(page = 1) {
        const params = new URLSearchParams(tableSort.params({
            page,
            search: document.getElementById('followup-search').value,
            followup_date_from: document.getElementById('followup-date-from').value,
            followup_date_to: document.getElementById('followup-date-to').value,
            status: document.getElementById('followup-status').value,
            lead_source_id: document.getElementById('followup-source').value,
        }));

        const response = await fetch(`{{ $datatableRoute }}?${params}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await response.json();
        const meta = json.meta;

        tbody.innerHTML = (json.data || []).map((lead, index) => `
            <tr>
                <td class="crm-sr">${tableSort.sr(meta, index)}</td>
                <td><strong>${lead.lead_number}</strong></td>
                <td>${lead.customer_name}</td>
                <td>${lead.mobile || '-'}</td>
                <td>${statusBadge(lead.status)}</td>
                <td>${lead.assignee?.name || '-'}</td>
                <td><strong>${formatFollowupDate(lead.next_followup_at)}</strong></td>
                <td><a href="{{ url('leads') }}/${lead.id}" class="crm-link" title="View Lead"><i class="bi bi-eye"></i></a></td>
            </tr>
        `).join('') || '<tr><td colspan="8" style="text-align:center;color:var(--crm-muted);padding:32px;">No followups found</td></tr>';

        const from = meta.total ? ((meta.current_page - 1) * meta.per_page + 1) : 0;
        const to = Math.min(meta.current_page * meta.per_page, meta.total);
        recordInfo.textContent = `Showing ${from}-${to} of ${meta.total} records`;

        pagination.innerHTML = `
            <button ${meta.current_page <= 1 ? 'disabled' : ''} data-page="${meta.current_page - 1}">&lsaquo;</button>
            <span class="active">${meta.current_page}</span>
            <button ${meta.current_page >= meta.last_page ? 'disabled' : ''} data-page="${meta.current_page + 1}">&rsaquo;</button>
        `;
        currentPage = meta.current_page;
    }

    tableSort.bind(tableEl, loadFollowups);
    document.getElementById('followup-filter-btn').addEventListener('click', () => loadFollowups(1));
    document.getElementById('followup-reset-btn').addEventListener('click', () => {
        document.querySelectorAll('.crm-filters .crm-input').forEach((el) => { el.value = ''; });
        loadFollowups(1);
    });
    document.getElementById('followup-refresh-btn').addEventListener('click', () => loadFollowups(currentPage));
    pagination.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-page]');
        if (button) loadFollowups(parseInt(button.dataset.page));
    });

    loadFollowups();
});
</script>
@endpush
