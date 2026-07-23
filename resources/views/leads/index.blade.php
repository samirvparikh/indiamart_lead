@extends('layouts.crm')

@section('title', $pageTitle)

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Home</a>
    <span>&rsaquo;</span>
    <a href="{{ route('leads.index') }}">Leads</a>
    <span>&rsaquo;</span> {{ $pageTitle }}
@endsection

@section('toolbar')
    <div class="crm-page-title">
        <i class="bi {{ $leadScope === 'my' ? 'bi-person-check' : 'bi-people' }}"></i> {{ $pageTitle }}
    </div>
    <div class="crm-toolbar-actions">
        @can('create', App\Models\Lead::class)
            <button type="button" class="crm-btn crm-btn-primary-sm" id="lead-sync-btn">
                <i class="bi bi-arrow-repeat"></i> Sync
            </button>
        @endcan
        <button type="button" class="crm-btn" id="lead-refresh-btn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        @can('create', App\Models\Lead::class)
            <a href="{{ route('leads.create') }}" class="crm-btn crm-btn-primary-sm"><i class="bi bi-plus-lg"></i> Create Lead</a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="crm-content-card">
        <div class="crm-content-card-body">
            <div class="crm-filters">
                <input type="text" id="lead-search" class="crm-input" placeholder="Search leads...">
                <input type="date" id="lead-date-from" class="crm-input" title="From date">
                <input type="date" id="lead-date-to" class="crm-input" title="To date">
                <select id="lead-status" class="crm-input">
                    <option value="">All Status</option>
                    @foreach (App\Enums\LeadStatus::cases() as $status)
                        <option value="{{ $status->value }}">{{ $status->value }}</option>
                    @endforeach
                </select>
                <select id="lead-source" class="crm-input">
                    <option value="">All Sources</option>
                    @foreach ($leadSources as $source)
                        <option value="{{ $source->id }}">{{ $source->name }}</option>
                    @endforeach
                </select>
                <button type="button" id="lead-filter-btn" class="crm-btn crm-btn-primary-sm"><i class="bi bi-funnel"></i> Filter</button>
                <button type="button" id="lead-reset-btn" class="crm-btn"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
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
                        <th data-sort="lead_source_id" data-dir="asc">Source</th>
                        <th data-sort="status" data-dir="asc">Status</th>
                        <th data-sort="assigned_to" data-dir="asc">Assigned</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="leads-table-body"></tbody>
            </table>
        </div>

        <div class="crm-table-footer">
            <span id="leads-record-info">Showing 0 records</span>
            <div class="crm-pagination" id="leads-pagination"></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById('leads-table-body');
    const pagination = document.getElementById('leads-pagination');
    const recordInfo = document.getElementById('leads-record-info');
    const tableEl = document.querySelector('.crm-table');
    const tableSort = CrmTable.create({ sort_by: 'id', sort_dir: 'desc' });
    let currentPage = 1;

    const statusBadge = (status) => {
        const map = { 'Won': 'success', 'Lost': 'danger', 'New': 'info', 'Assigned': 'warning' };
        const cls = map[status] || 'secondary';
        return `<span class="crm-badge crm-badge-${cls}">${status}</span>`;
    };

    async function loadLeads(page = 1) {
        const params = new URLSearchParams(tableSort.params({
            page,
            search: document.getElementById('lead-search').value,
            date_from: document.getElementById('lead-date-from').value,
            date_to: document.getElementById('lead-date-to').value,
            status: document.getElementById('lead-status').value,
            lead_source_id: document.getElementById('lead-source').value,
        }));

        const response = await fetch(`{{ $datatableRoute }}?${params}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await response.json();
        const meta = json.meta;

        tbody.innerHTML = (json.data || []).map((lead, i) => `
            <tr>
                <td class="crm-sr">${tableSort.sr(meta, i)}</td>
                <td><strong>${lead.lead_number}</strong></td>
                <td>${lead.customer_name}</td>
                <td>${lead.mobile || '-'}</td>
                <td>${lead.lead_source?.name || '-'}</td>
                <td>${statusBadge(lead.status)}</td>
                <td>${lead.assignee?.name || '-'}</td>
                <td><a href="{{ url('leads') }}/${lead.id}" class="crm-link" title="View"><i class="bi bi-eye"></i></a></td>
            </tr>
        `).join('') || '<tr><td colspan="8" style="text-align:center;color:var(--crm-muted);padding:32px;">No leads found</td></tr>';

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

    tableSort.bind(tableEl, loadLeads);
    document.getElementById('lead-filter-btn').addEventListener('click', () => loadLeads(1));
    document.getElementById('lead-reset-btn').addEventListener('click', () => {
        document.querySelectorAll('.crm-filters .crm-input').forEach((el) => { el.value = ''; });
        loadLeads(1);
    });
    document.getElementById('lead-refresh-btn').addEventListener('click', () => loadLeads(currentPage));
    document.getElementById('lead-sync-btn')?.addEventListener('click', async () => {
        const btn = document.getElementById('lead-sync-btn');
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Syncing...';
        try {
            const res = await fetch('{{ route('leads.sync-indiamart') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
            });
            const json = await res.json();
            if (json.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Sync Successful',
                    text: json.message || 'Leads synced successfully.',
                    confirmButtonColor: '#2563eb',
                });
                loadLeads(1);
            } else {
                await Swal.fire({
                    icon: 'error',
                    title: 'Sync Failed',
                    text: json.message || 'Sync failed.',
                    confirmButtonColor: '#dc2626',
                });
            }
        } catch (err) {
            await Swal.fire({
                icon: 'error',
                title: 'Sync Failed',
                text: 'Sync failed. Please try again.',
                confirmButtonColor: '#dc2626',
            });
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });
    pagination.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-page]');
        if (btn) loadLeads(parseInt(btn.dataset.page));
    });

    loadLeads();
});
</script>
@endpush
