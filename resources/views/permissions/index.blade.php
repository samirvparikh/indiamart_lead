@extends('layouts.crm')

@section('title', 'Permissions')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Home</a><span>&rsaquo;</span>
    Administration<span>&rsaquo;</span> Permissions
@endsection

@section('toolbar')
    <div class="crm-page-title"><i class="bi bi-key"></i> Permissions</div>
    <div class="crm-toolbar-actions">
        <button type="button" class="crm-btn" id="refresh-btn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        @can('permissions.edit')
            <button type="button" class="crm-btn crm-btn-primary-sm" id="sync-btn"><i class="bi bi-arrow-repeat"></i> Sync Registry</button>
        @endcan
    </div>
@endsection

@section('content')
    <div class="crm-content-card">
        <div class="crm-content-card-body">
            <div class="crm-filters">
                <input type="text" id="search" class="crm-input" placeholder="Search permissions...">
                <select id="group-filter" class="crm-input">
                    <option value="">All Groups</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group }}">{{ ucfirst($group) }}</option>
                    @endforeach
                </select>
                <button type="button" id="filter-btn" class="crm-btn crm-btn-primary-sm"><i class="bi bi-funnel"></i> Filter</button>
                <button type="button" id="reset-btn" class="crm-btn"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
            </div>
        </div>
        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th data-sort="name" data-dir="asc">Permission</th>
                        <th>Group</th>
                        <th data-sort="guard_name" data-dir="asc">Guard</th>
                        <th>Assigned Roles</th>
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
    const tableSort = CrmTable.create({ sort_by: 'name', sort_dir: 'asc' });
    let currentPage = 1;

    async function load(page = 1) {
        const params = new URLSearchParams(tableSort.params({
            page,
            search: document.getElementById('search').value,
            group: document.getElementById('group-filter').value,
        }));
        const res = await fetch(`{{ route('permissions.datatable') }}?${params}`, { headers: { 'Accept': 'application/json' } });
        const json = await res.json();
        const m = json.meta;
        tbody.innerHTML = (json.data || []).map((row, i) => `
            <tr>
                <td class="crm-sr">${tableSort.sr(m, i)}</td>
                <td><strong>${row.name}</strong></td>
                <td>${row.group}</td>
                <td>${row.guard_name}</td>
                <td>${row.roles_count}</td>
            </tr>
        `).join('') || '<tr><td colspan="5" style="text-align:center;padding:32px;color:var(--crm-muted);">No permissions found</td></tr>';
        document.getElementById('record-info').textContent = `Showing ${m.total ? (m.current_page-1)*m.per_page+1 : 0}-${Math.min(m.current_page*m.per_page,m.total)} of ${m.total} records`;
        document.getElementById('pagination').innerHTML = `<button ${m.current_page<=1?'disabled':''} data-page="${m.current_page-1}">&lsaquo;</button><span class="active">${m.current_page}</span><button ${m.current_page>=m.last_page?'disabled':''} data-page="${m.current_page+1}">&rsaquo;</button>`;
        currentPage = m.current_page;
    }

    tableSort.bind(document.querySelector('.crm-table'), load);
    document.getElementById('filter-btn').addEventListener('click', () => load(1));
    document.getElementById('reset-btn').addEventListener('click', () => {
        document.querySelectorAll('.crm-filters .crm-input').forEach((el) => { el.value = ''; });
        load(1);
    });
    document.getElementById('refresh-btn').addEventListener('click', () => load(currentPage));
    document.getElementById('pagination').addEventListener('click', e => { const b = e.target.closest('button[data-page]'); if (b) load(+b.dataset.page); });
    document.getElementById('sync-btn')?.addEventListener('click', async () => {
        const btn = document.getElementById('sync-btn');
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Syncing...';
        try {
            const res = await fetch('{{ route('permissions.sync') }}', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            });
            const json = await res.json();
            alert(json.message || 'Sync complete.');
            if (json.success) load(1);
        } catch (err) {
            alert('Sync failed.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });
    load();
});
</script>
@endpush
