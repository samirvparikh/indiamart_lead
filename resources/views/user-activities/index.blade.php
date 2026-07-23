@extends('layouts.crm')

@section('title', 'User Activity')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Home</a><span>&rsaquo;</span>
    Administration<span>&rsaquo;</span> User Activity
@endsection

@section('toolbar')
    <div class="crm-page-title"><i class="bi bi-activity"></i> User Activity</div>
    <div class="crm-toolbar-actions">
        <button type="button" class="crm-btn" id="refresh-btn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
    </div>
@endsection

@section('content')
    <div class="crm-content-card">
        <div class="crm-content-card-body">
            <div class="crm-filters">
                <input type="text" id="search" class="crm-input" placeholder="Search user, activity, IP...">
                <select id="user-filter" class="crm-input">
                    <option value="">All Users</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->username }})</option>
                    @endforeach
                </select>
                <select id="type-filter" class="crm-input">
                    <option value="">All Types</option>
                    @foreach ($activityTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->value }}</option>
                    @endforeach
                </select>
                <input type="date" id="date-from" class="crm-input" title="From date">
                <input type="date" id="date-to" class="crm-input" title="To date">
                <button type="button" id="filter-btn" class="crm-btn crm-btn-primary-sm"><i class="bi bi-funnel"></i> Filter</button>
                <button type="button" id="reset-btn" class="crm-btn"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
            </div>
        </div>

        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th data-sort="created_at" data-dir="desc">Date &amp; Time</th>
                        <th data-sort="user_id" data-dir="asc">User</th>
                        <th data-sort="type" data-dir="asc">Activity</th>
                        <th>Description</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody id="table-body"></tbody>
            </table>
        </div>

        <div class="crm-table-footer">
            <span id="record-info">Showing 0 records</span>
            <div class="crm-pagination" id="pagination"></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById('table-body');
    const tableSort = CrmTable.create({ sort_by: 'id', sort_dir: 'desc' });
    let currentPage = 1;

    const typeBadge = (type) => {
        const map = {
            'Login': 'success',
            'Logout': 'secondary',
            'User Created': 'info',
            'User Updated': 'warning',
            'User Deleted': 'danger',
            'Profile Updated': 'info',
            'Password Changed': 'warning',
        };
        return `<span class="crm-badge crm-badge-${map[type] || 'secondary'}">${type || '—'}</span>`;
    };

    async function load(page = 1) {
        const params = new URLSearchParams(tableSort.params({
            page,
            search: document.getElementById('search').value,
            user_id: document.getElementById('user-filter').value,
            type: document.getElementById('type-filter').value,
            date_from: document.getElementById('date-from').value,
            date_to: document.getElementById('date-to').value,
        }));

        const res = await fetch(`{{ route('user-activities.datatable') }}?${params}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const json = await res.json();
        const meta = json.meta;

        tbody.innerHTML = (json.data || []).map((row, i) => `
            <tr>
                <td class="crm-sr">${tableSort.sr(meta, i)}</td>
                <td style="white-space:nowrap;">${row.created_at || '—'}</td>
                <td>
                    <strong>${row.user?.name || 'System'}</strong>
                    <div style="font-size:12px;color:var(--crm-muted);">${row.user?.username || row.user?.email || ''}</div>
                </td>
                <td>${typeBadge(row.type)}</td>
                <td>${row.description || '—'}</td>
                <td>${row.ip_address || '—'}</td>
            </tr>
        `).join('') || '<tr><td colspan="6" style="text-align:center;padding:32px;color:var(--crm-muted);">No user activities found</td></tr>';

        const from = meta.total ? ((meta.current_page - 1) * meta.per_page + 1) : 0;
        const to = Math.min(meta.current_page * meta.per_page, meta.total);
        document.getElementById('record-info').textContent = `Showing ${from}-${to} of ${meta.total} records`;
        document.getElementById('pagination').innerHTML = `
            <button ${meta.current_page <= 1 ? 'disabled' : ''} data-page="${meta.current_page - 1}">&lsaquo;</button>
            <span class="active">${meta.current_page}</span>
            <button ${meta.current_page >= meta.last_page ? 'disabled' : ''} data-page="${meta.current_page + 1}">&rsaquo;</button>
        `;
        currentPage = meta.current_page;
    }

    tableSort.bind(document.querySelector('.crm-table'), load);
    document.getElementById('filter-btn').addEventListener('click', () => load(1));
    document.getElementById('reset-btn').addEventListener('click', () => {
        document.querySelectorAll('.crm-filters .crm-input').forEach((el) => { el.value = ''; });
        load(1);
    });
    document.getElementById('refresh-btn').addEventListener('click', () => load(currentPage));
    document.getElementById('pagination').addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-page]');
        if (btn) load(parseInt(btn.dataset.page));
    });

    load();
});
</script>
@endpush
