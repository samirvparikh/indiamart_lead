@extends('layouts.crm')

@section('title', 'Roles')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Home</a><span>&rsaquo;</span>
    Administration<span>&rsaquo;</span> Roles
@endsection

@section('toolbar')
    <div class="crm-page-title"><i class="bi bi-shield-lock"></i> Roles</div>
    <div class="crm-toolbar-actions">
        <button type="button" class="crm-btn" id="refresh-btn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        @can('roles.create')
            <button type="button" class="crm-btn crm-btn-primary-sm" id="add-btn"><i class="bi bi-plus-lg"></i> Add Role</button>
        @endcan
    </div>
@endsection

@section('content')
    <div class="crm-content-card">
        <div class="crm-content-card-body">
            <div class="crm-filters">
                <input type="text" id="search" class="crm-input" placeholder="Search roles...">
                <button type="button" id="filter-btn" class="crm-btn crm-btn-primary-sm"><i class="bi bi-funnel"></i> Filter</button>
                <button type="button" id="reset-btn" class="crm-btn"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
            </div>
        </div>
        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th data-sort="name" data-dir="asc">Role</th>
                        <th>Permissions</th>
                        <th>Users</th>
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

    <div class="crm-modal-backdrop" id="modal" style="display:none;">
        <div class="crm-modal" style="max-width:720px;" @click.stop>
            <div class="crm-modal-head">
                <h3 id="modal-title">Add Role</h3>
                <button type="button" class="crm-modal-close" id="modal-close">&times;</button>
            </div>
            <form id="role-form">
                <div class="crm-modal-body">
                    <div class="crm-field">
                        <label class="crm-field-label">Role Name *</label>
                        <input class="crm-input" name="name" required>
                    </div>
                    <div style="margin-top:16px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                            <label class="crm-field-label" style="margin:0;">Permissions</label>
                            <button type="button" class="crm-btn" id="toggle-all-perms" style="padding:4px 10px;font-size:12px;">Select All</button>
                        </div>
                        <div style="max-height:320px;overflow:auto;border:1px solid var(--crm-border);border-radius:var(--crm-radius);padding:12px;">
                            @foreach ($permissionGroups as $group)
                                <div style="margin-bottom:14px;">
                                    <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--crm-muted);margin-bottom:6px;">{{ $group['group'] }}</div>
                                    <div class="crm-form-grid" style="grid-template-columns:1fr 1fr;">
                                        @foreach ($group['permissions'] as $perm)
                                            <label class="crm-checkbox">
                                                <input type="checkbox" name="permissions[]" value="{{ $perm['name'] }}"> {{ $perm['name'] }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <p id="form-error" class="crm-auth-error"></p>
                </div>
                <div class="crm-modal-foot">
                    <button type="button" class="crm-btn" id="modal-cancel">Cancel</button>
                    <button type="submit" class="crm-btn crm-btn-primary-sm">Save Role</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modal');
    const form = document.getElementById('role-form');
    const tbody = document.getElementById('table-body');
    const tableSort = CrmTable.create({ sort_by: 'name', sort_dir: 'asc' });
    let currentPage = 1, editId = null, allSelected = false;
    const canEdit = @json(auth()->user()->can('roles.edit'));
    const canDelete = @json(auth()->user()->can('roles.delete'));
    const protectedRoles = ['Super Admin', 'Admin'];

    function openModal(title, data = null) {
        editId = data?.id || null;
        document.getElementById('modal-title').textContent = title;
        form.reset();
        allSelected = false;
        document.getElementById('toggle-all-perms').textContent = 'Select All';
        if (data) {
            form.elements.name.value = data.name || '';
            form.elements.name.readOnly = protectedRoles.includes(data.name);
            const selected = data.permissions || [];
            form.querySelectorAll('input[name="permissions[]"]').forEach(cb => {
                cb.checked = selected.includes(cb.value);
            });
        } else {
            form.elements.name.readOnly = false;
        }
        document.getElementById('form-error').textContent = '';
        modal.style.display = 'flex';
    }
    function closeModal() { modal.style.display = 'none'; editId = null; }

    async function load(page = 1) {
        const params = new URLSearchParams(tableSort.params({ page, search: document.getElementById('search').value }));
        const res = await fetch(`{{ route('roles.datatable') }}?${params}`, { headers: { 'Accept': 'application/json' } });
        const json = await res.json();
        const m = json.meta;
        tbody.innerHTML = (json.data || []).map((row, i) => {
            const label = row.name === 'Admin' ? 'System Admin' : row.name;
            const showDelete = canDelete && !protectedRoles.includes(row.name);
            return `<tr>
                <td class="crm-sr">${tableSort.sr(m, i)}</td>
                <td><strong>${label}</strong></td>
                <td>${row.permissions_count}</td>
                <td>${row.users_count}</td>
                <td class="crm-action-menu">
                    ${canEdit ? `<button type="button" data-edit='${JSON.stringify(row).replace(/'/g,"&#39;")}'><i class="bi bi-pencil"></i></button>` : ''}
                    ${showDelete ? `<button type="button" data-delete="${row.id}"><i class="bi bi-trash"></i></button>` : ''}
                </td>
            </tr>`;
        }).join('') || '<tr><td colspan="5" style="text-align:center;padding:32px;color:var(--crm-muted);">No roles found</td></tr>';
        document.getElementById('record-info').textContent = `Showing ${m.total ? (m.current_page-1)*m.per_page+1 : 0}-${Math.min(m.current_page*m.per_page,m.total)} of ${m.total} records`;
        document.getElementById('pagination').innerHTML = `<button ${m.current_page<=1?'disabled':''} data-page="${m.current_page-1}">&lsaquo;</button><span class="active">${m.current_page}</span><button ${m.current_page>=m.last_page?'disabled':''} data-page="${m.current_page+1}">&rsaquo;</button>`;
        currentPage = m.current_page;
    }

    tableSort.bind(document.querySelector('.crm-table'), load);
    document.getElementById('add-btn')?.addEventListener('click', () => openModal('Add Role'));
    document.getElementById('modal-close').addEventListener('click', closeModal);
    document.getElementById('modal-cancel').addEventListener('click', closeModal);
    document.getElementById('filter-btn').addEventListener('click', () => load(1));
    document.getElementById('reset-btn').addEventListener('click', () => {
        document.querySelectorAll('.crm-filters .crm-input').forEach((el) => { el.value = ''; });
        load(1);
    });
    document.getElementById('refresh-btn').addEventListener('click', () => load(currentPage));
    document.getElementById('pagination').addEventListener('click', e => { const b = e.target.closest('button[data-page]'); if (b) load(+b.dataset.page); });
    document.getElementById('toggle-all-perms').addEventListener('click', () => {
        allSelected = !allSelected;
        form.querySelectorAll('input[name="permissions[]"]').forEach(cb => { cb.checked = allSelected; });
        document.getElementById('toggle-all-perms').textContent = allSelected ? 'Clear All' : 'Select All';
    });

    tbody.addEventListener('click', async e => {
        const del = e.target.closest('button[data-delete]');
        if (del) {
            if (!confirm('Delete this role?')) return;
            const res = await fetch(`{{ url('roles') }}/${del.dataset.delete}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } });
            const json = await res.json();
            if (!json.success) alert(json.message || 'Delete failed.');
            return load(currentPage);
        }
        const ed = e.target.closest('button[data-edit]');
        if (ed) openModal('Edit Role', JSON.parse(ed.dataset.edit));
    });

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const permissions = [...form.querySelectorAll('input[name="permissions[]"]:checked')].map(cb => cb.value);
        const data = { name: form.elements.name.value, permissions };
        const url = editId ? `{{ url('roles') }}/${editId}` : '{{ route('roles.store') }}';
        const res = await fetch(url, {
            method: editId ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify(data),
        });
        const json = await res.json();
        if (json.success) { closeModal(); load(currentPage); }
        else {
            const msg = json.message || (json.errors ? Object.values(json.errors).flat().join(' ') : 'Save failed.');
            document.getElementById('form-error').textContent = msg;
        }
    });
    load();
});
</script>
@endpush
