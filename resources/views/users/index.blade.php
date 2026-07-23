@extends('layouts.crm')

@section('title', 'Users')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Home</a><span>&rsaquo;</span>
    Administration<span>&rsaquo;</span> Users
@endsection

@section('toolbar')
    <div class="crm-page-title"><i class="bi bi-person-gear"></i> Users</div>
    <div class="crm-toolbar-actions">
        <button type="button" class="crm-btn" id="refresh-btn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        @can('create', App\Models\User::class)
            <button type="button" class="crm-btn crm-btn-primary-sm" id="add-btn"><i class="bi bi-plus-lg"></i> Add User</button>
        @endcan
    </div>
@endsection

@section('content')
    <div class="crm-content-card">
        <div class="crm-content-card-body">
            <div class="crm-filters">
                <input type="text" id="search" class="crm-input" placeholder="Search users...">
                <select id="role-filter" class="crm-input">
                    <option value="">All Roles</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}">{{ $role }}</option>
                    @endforeach
                </select>
                <select id="status-filter" class="crm-input">
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
                        <th>#</th>
                        <th data-sort="username" data-dir="asc">Username</th>
                        <th data-sort="first_name" data-dir="asc">Name</th>
                        <th data-sort="email" data-dir="asc">Email</th>
                        <th data-sort="mobile" data-dir="asc">Mobile</th>
                        <th>Role</th>
                        <th data-sort="is_active" data-dir="asc">Status</th>
                        <th data-sort="last_login_at" data-dir="desc">Last Login</th>
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
        <div class="crm-modal" @click.stop>
            <div class="crm-modal-head">
                <h3 id="modal-title">Add User</h3>
                <button type="button" class="crm-modal-close" id="modal-close">&times;</button>
            </div>
            <form id="user-form">
                <div class="crm-modal-body">
                    <div class="crm-form-grid">
                        <div class="crm-field"><label class="crm-field-label">Username *</label><input class="crm-input" name="username" required autocomplete="off"></div>
                        <div class="crm-field"><label class="crm-field-label">First Name *</label><input class="crm-input" name="first_name" required></div>
                        <div class="crm-field"><label class="crm-field-label">Last Name</label><input class="crm-input" name="last_name"></div>
                        <div class="crm-field"><label class="crm-field-label">Email *</label><input class="crm-input" type="email" name="email" required></div>
                        <div class="crm-field"><label class="crm-field-label">Mobile</label><input class="crm-input" name="mobile" inputmode="numeric"></div>
                        <div class="crm-field"><label class="crm-field-label">Designation</label><input class="crm-input" name="designation"></div>
                        <div class="crm-field">
                            <label class="crm-field-label">Role *</label>
                            <select class="crm-input" name="role" required>
                                <option value="">Select role</option>
                                @foreach ($roles as $role)
                                    @if ($role !== 'Super Admin' || auth()->user()->isSuperAdmin())
                                        <option value="{{ $role }}">{{ $role === 'Admin' ? 'System Admin' : $role }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="crm-field" id="active-field" style="display:none;">
                            <label class="crm-field-label">Status</label>
                            <label class="crm-checkbox"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                        </div>
                        <div class="crm-field"><label class="crm-field-label" id="password-label">Password *</label><input class="crm-input" type="password" name="password"></div>
                        <div class="crm-field"><label class="crm-field-label">Confirm Password</label><input class="crm-input" type="password" name="password_confirmation"></div>
                    </div>
                    <p id="form-error" class="crm-auth-error"></p>
                </div>
                <div class="crm-modal-foot">
                    <button type="button" class="crm-btn" id="modal-cancel">Cancel</button>
                    <button type="submit" class="crm-btn crm-btn-primary-sm">Save User</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modal');
    const form = document.getElementById('user-form');
    const tbody = document.getElementById('table-body');
    const tableSort = CrmTable.create({ sort_by: 'id', sort_dir: 'desc' });
    let currentPage = 1, editId = null;
    const canEdit = @json(auth()->user()->can('users.edit'));
    const canDelete = @json(auth()->user()->can('users.delete'));
    const currentUserId = @json(auth()->id());

    function openModal(title, data = null) {
        editId = data?.id || null;
        document.getElementById('modal-title').textContent = title;
        document.getElementById('active-field').style.display = editId ? 'block' : 'none';
        document.getElementById('password-label').textContent = editId ? 'Password (leave blank to keep)' : 'Password *';
        form.reset();
        form.elements.password.required = !editId;
        if (data) {
            ['username','first_name','last_name','email','mobile','designation'].forEach(k => { if (form.elements[k]) form.elements[k].value = data[k] || ''; });
            if (form.elements.role) form.elements.role.value = data.role || '';
            form.elements.is_active.checked = !!data.is_active;
        }
        document.getElementById('form-error').textContent = '';
        modal.style.display = 'flex';
    }
    function closeModal() { modal.style.display = 'none'; editId = null; }

    async function load(page = 1) {
        const params = new URLSearchParams(tableSort.params({
            page,
            search: document.getElementById('search').value,
            role: document.getElementById('role-filter').value,
            is_active: document.getElementById('status-filter').value,
        }));
        const res = await fetch(`{{ route('users.datatable') }}?${params}`, { headers: { 'Accept': 'application/json' } });
        const json = await res.json();
        const m = json.meta;
        tbody.innerHTML = (json.data || []).map((row, i) => {
            const roleLabel = row.role === 'Admin' ? 'System Admin' : (row.role || '—');
            const showDelete = canDelete && row.id !== currentUserId;
            return `<tr>
                <td class="crm-sr">${tableSort.sr(m, i)}</td>
                <td><strong>${row.username}</strong></td>
                <td>${row.name}<div style="font-size:12px;color:var(--crm-muted);">${row.designation || ''}</div></td>
                <td>${row.email}</td>
                <td>${row.mobile || '—'}</td>
                <td>${roleLabel}</td>
                <td><span class="crm-badge crm-badge-${row.is_active ? 'success' : 'secondary'}">${row.is_active ? 'Active' : 'Inactive'}</span></td>
                <td style="white-space:nowrap;">${row.last_login_at || '—'}</td>
                <td class="crm-action-menu">${canEdit ? `<button type="button" data-edit='${JSON.stringify(row).replace(/'/g,"&#39;")}' title="Edit"><i class="bi bi-pencil"></i></button>` : ''}${showDelete ? `<button type="button" data-delete="${row.id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}</td>
            </tr>`;
        }).join('') || '<tr><td colspan="9" style="text-align:center;padding:32px;color:var(--crm-muted);">No users found</td></tr>';
        document.getElementById('record-info').textContent = `Showing ${m.total ? (m.current_page-1)*m.per_page+1 : 0}-${Math.min(m.current_page*m.per_page,m.total)} of ${m.total} records`;
        document.getElementById('pagination').innerHTML = `<button ${m.current_page<=1?'disabled':''} data-page="${m.current_page-1}">&lsaquo;</button><span class="active">${m.current_page}</span><button ${m.current_page>=m.last_page?'disabled':''} data-page="${m.current_page+1}">&rsaquo;</button>`;
        currentPage = m.current_page;
    }

    tableSort.bind(document.querySelector('.crm-table'), load);
    document.getElementById('add-btn')?.addEventListener('click', () => openModal('Add User'));
    document.getElementById('modal-close').addEventListener('click', closeModal);
    document.getElementById('modal-cancel').addEventListener('click', closeModal);
    document.getElementById('filter-btn').addEventListener('click', () => load(1));
    document.getElementById('refresh-btn').addEventListener('click', () => load(currentPage));
    document.getElementById('pagination').addEventListener('click', e => { const b = e.target.closest('button[data-page]'); if (b) load(+b.dataset.page); });

    tbody.addEventListener('click', async e => {
        const del = e.target.closest('button[data-delete]');
        if (del) {
            if (!confirm('Delete this user?')) return;
            const res = await fetch(`{{ url('users') }}/${del.dataset.delete}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } });
            const json = await res.json();
            if (!json.success) alert(json.message || 'Delete failed.');
            return load(currentPage);
        }
        const ed = e.target.closest('button[data-edit]');
        if (ed) openModal('Edit User', JSON.parse(ed.dataset.edit));
    });

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(form));
        if (editId) data.is_active = form.elements.is_active.checked;
        if (editId && !data.password) { delete data.password; delete data.password_confirmation; }
        const url = editId ? `{{ url('users') }}/${editId}` : '{{ route('users.store') }}';
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
