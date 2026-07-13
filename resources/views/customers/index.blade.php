@extends('layouts.crm')

@section('title', 'Customers')
@section('module', 'customers')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Home</a><span>&rsaquo;</span> Customers
@endsection

@section('toolbar')
    <div class="crm-page-title"><i class="bi bi-people"></i> Customers</div>
    <div class="crm-toolbar-actions">
        <button type="button" class="crm-btn" id="refresh-btn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        @can('create', App\Models\Customer::class)
            <a href="{{ route('customers.create') }}" class="crm-btn crm-btn-primary-sm"><i class="bi bi-plus-lg"></i> Add Customer</a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="crm-content-card">
        <div class="crm-content-card-body">
            <div class="crm-filters">
                <input type="text" id="search" class="crm-input" placeholder="Search customers...">
                <select id="is_active" class="crm-input">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                <button type="button" id="filter-btn" class="crm-btn crm-btn-primary-sm"><i class="bi bi-funnel"></i> Filter</button>
            </div>
        </div>
        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Company</th>
                        <th>Status</th>
                        <th>Actions</th>
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
    const pagination = document.getElementById('pagination');
    const recordInfo = document.getElementById('record-info');
    let currentPage = 1;
    const canDelete = @json(auth()->user()->can('customers.delete'));

    async function load(page = 1) {
        const params = new URLSearchParams({
            page,
            search: document.getElementById('search').value,
            is_active: document.getElementById('is_active').value,
        });
        const res = await fetch(`{{ route('customers.datatable') }}?${params}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();
        tbody.innerHTML = (json.data || []).map(row => `
            <tr>
                <td><strong>${row.name}</strong></td>
                <td>${row.mobile || '—'}</td>
                <td>${row.email || '—'}</td>
                <td>${row.company?.name || '—'}</td>
                <td><span class="crm-badge crm-badge-${row.is_active ? 'success' : 'secondary'}">${row.is_active ? 'Active' : 'Inactive'}</span></td>
                <td class="crm-action-menu">
                    <a href="{{ url('customers') }}/${row.id}" title="View"><i class="bi bi-eye"></i></a>
                    <a href="{{ url('customers') }}/${row.id}/edit" title="Edit"><i class="bi bi-pencil"></i></a>
                    ${canDelete ? `<button type="button" data-delete="${row.id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
                </td>
            </tr>
        `).join('') || '<tr><td colspan="6" style="text-align:center;color:var(--crm-muted);padding:32px;">No customers found</td></tr>';

        const meta = json.meta;
        const from = meta.total ? ((meta.current_page - 1) * meta.per_page + 1) : 0;
        const to = Math.min(meta.current_page * meta.per_page, meta.total);
        recordInfo.textContent = `Showing ${from}-${to} of ${meta.total} records`;
        pagination.innerHTML = `
            <button ${meta.current_page <= 1 ? 'disabled' : ''} data-page="${meta.current_page - 1}">&lsaquo;</button>
            <span class="active">${meta.current_page}</span>
            <button ${meta.current_page >= meta.last_page ? 'disabled' : ''} data-page="${meta.current_page + 1}">&rsaquo;</button>`;
        currentPage = meta.current_page;
    }

    document.getElementById('filter-btn').addEventListener('click', () => load(1));
    document.getElementById('refresh-btn').addEventListener('click', () => load(currentPage));
    pagination.addEventListener('click', e => {
        const btn = e.target.closest('button[data-page]');
        if (btn) load(parseInt(btn.dataset.page));
    });
    tbody.addEventListener('click', async e => {
        const btn = e.target.closest('button[data-delete]');
        if (!btn || !confirm('Delete this customer?')) return;
        const res = await fetch(`{{ url('customers') }}/${btn.dataset.delete}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        if (res.ok) load(currentPage);
    });
    load();
});
</script>
@endpush
