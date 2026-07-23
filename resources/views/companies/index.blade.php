@extends('layouts.crm')

@section('title', 'Companies')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Home</a><span>&rsaquo;</span> Companies
@endsection

@section('toolbar')
    <div class="crm-page-title"><i class="bi bi-building"></i> Companies</div>
    <div class="crm-toolbar-actions">
        <button type="button" class="crm-btn" id="refresh-btn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        @can('create', App\Models\Company::class)
            <button type="button" class="crm-btn crm-btn-primary-sm" id="add-btn"><i class="bi bi-plus-lg"></i> Add Company</button>
        @endcan
    </div>
@endsection

@section('content')
    <div class="crm-content-card">
        <div class="crm-content-card-body">
            <div class="crm-filters">
                <input type="text" id="search" class="crm-input" placeholder="Search companies...">
                <button type="button" id="filter-btn" class="crm-btn crm-btn-primary-sm"><i class="bi bi-funnel"></i> Filter</button>
                <button type="button" id="reset-btn" class="crm-btn"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
            </div>
        </div>
        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th data-sort="name" data-dir="asc">Name</th>
                        <th data-sort="phone" data-dir="asc">Phone</th>
                        <th data-sort="email" data-dir="asc">Email</th>
                        <th data-sort="gst_number" data-dir="asc">GST</th>
                        <th data-sort="city" data-dir="asc">City</th>
                        <th data-sort="is_active" data-dir="asc">Status</th>
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

    <div class="crm-modal-backdrop" id="modal" style="display:none;" x-data x-cloak>
        <div class="crm-modal" @click.stop>
            <div class="crm-modal-head">
                <h3 id="modal-title">Add Company</h3>
                <button type="button" class="crm-modal-close" id="modal-close">&times;</button>
            </div>
            <form id="company-form">
                <div class="crm-modal-body">
                    <div class="crm-form-grid">
                        <div class="crm-field"><label class="crm-field-label">Name *</label><input class="crm-input" name="name" required></div>
                        <div class="crm-field"><label class="crm-field-label">Phone</label><input class="crm-input" name="phone"></div>
                        <div class="crm-field"><label class="crm-field-label">Email</label><input class="crm-input" type="email" name="email"></div>
                        <div class="crm-field"><label class="crm-field-label">GST Number</label><input class="crm-input" name="gst_number"></div>
                        <div class="crm-field"><label class="crm-field-label">City</label><input class="crm-input" name="city"></div>
                        <div class="crm-field"><label class="crm-field-label">State</label><input class="crm-input" name="state"></div>
                    </div>
                    <div class="crm-field" id="active-field" style="display:none;">
                        <label class="crm-checkbox"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                    </div>
                    <p id="form-error" class="crm-auth-error"></p>
                </div>
                <div class="crm-modal-foot">
                    <button type="button" class="crm-btn" id="modal-cancel">Cancel</button>
                    <button type="submit" class="crm-btn crm-btn-primary-sm">Save Company</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modal');
    const form = document.getElementById('company-form');
    const tbody = document.getElementById('table-body');
    const tableSort = CrmTable.create({ sort_by: 'id', sort_dir: 'desc' });
    let currentPage = 1, editId = null;
    const canEdit = @json(auth()->user()->can('companies.edit'));
    const canDelete = @json(auth()->user()->can('companies.delete'));

    function openModal(title, data = null) {
        editId = data?.id || null;
        document.getElementById('modal-title').textContent = title;
        document.getElementById('active-field').style.display = editId ? 'block' : 'none';
        form.reset();
        if (data) {
            ['name','phone','email','gst_number','city','state'].forEach(k => {
                if (form.elements[k]) form.elements[k].value = data[k] || '';
            });
            form.elements.is_active.checked = !!data.is_active;
        }
        document.getElementById('form-error').textContent = '';
        modal.style.display = 'flex';
    }
    function closeModal() { modal.style.display = 'none'; editId = null; }

    async function load(page = 1) {
        const params = new URLSearchParams(tableSort.params({ page, search: document.getElementById('search').value }));
        const res = await fetch(`{{ route('companies.datatable') }}?${params}`, { headers: { 'Accept': 'application/json' } });
        const json = await res.json();
        const m = json.meta;
        tbody.innerHTML = (json.data || []).map((row, i) => `
            <tr>
                <td class="crm-sr">${tableSort.sr(m, i)}</td>
                <td><strong>${row.name}</strong></td>
                <td>${row.phone || '—'}</td>
                <td>${row.email || '—'}</td>
                <td>${row.gst_number || '—'}</td>
                <td>${row.city || '—'}</td>
                <td><span class="crm-badge crm-badge-${row.is_active ? 'success' : 'secondary'}">${row.is_active ? 'Active' : 'Inactive'}</span></td>
                <td class="crm-action-menu">
                    ${canEdit ? `<button type="button" data-edit='${JSON.stringify(row).replace(/'/g,"&#39;")}'><i class="bi bi-pencil"></i></button>` : ''}
                    ${canDelete ? `<button type="button" data-delete="${row.id}"><i class="bi bi-trash"></i></button>` : ''}
                </td>
            </tr>`).join('') || '<tr><td colspan="8" style="text-align:center;padding:32px;color:var(--crm-muted);">No companies found</td></tr>';
        document.getElementById('record-info').textContent = `Showing ${m.total ? (m.current_page-1)*m.per_page+1 : 0}-${Math.min(m.current_page*m.per_page,m.total)} of ${m.total} records`;
        document.getElementById('pagination').innerHTML = `<button ${m.current_page<=1?'disabled':''} data-page="${m.current_page-1}">&lsaquo;</button><span class="active">${m.current_page}</span><button ${m.current_page>=m.last_page?'disabled':''} data-page="${m.current_page+1}">&rsaquo;</button>`;
        currentPage = m.current_page;
    }

    document.getElementById('add-btn')?.addEventListener('click', () => openModal('Add Company'));
    document.getElementById('modal-close').addEventListener('click', closeModal);
    document.getElementById('modal-cancel').addEventListener('click', closeModal);
    tableSort.bind(document.querySelector('.crm-table'), load);
    document.getElementById('filter-btn').addEventListener('click', () => load(1));
    document.getElementById('reset-btn').addEventListener('click', () => {
        document.querySelectorAll('.crm-filters .crm-input').forEach((el) => { el.value = ''; });
        load(1);
    });
    document.getElementById('refresh-btn').addEventListener('click', () => load(currentPage));
    document.getElementById('pagination').addEventListener('click', e => { const b = e.target.closest('button[data-page]'); if (b) load(+b.dataset.page); });

    tbody.addEventListener('click', async e => {
        const del = e.target.closest('button[data-delete]');
        if (del) {
            if (!confirm('Delete company?')) return;
            await fetch(`{{ url('companies') }}/${del.dataset.delete}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } });
            return load(currentPage);
        }
        const ed = e.target.closest('button[data-edit]');
        if (ed) openModal('Edit Company', JSON.parse(ed.dataset.edit));
    });

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(form));
        if (editId) data.is_active = form.elements.is_active.checked;
        const url = editId ? `{{ url('companies') }}/${editId}` : '{{ route('companies.store') }}';
        const res = await fetch(url, {
            method: editId ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify(data),
        });
        const json = await res.json();
        if (json.success) { closeModal(); load(currentPage); }
        else document.getElementById('form-error').textContent = json.message || 'Save failed.';
    });
    load();
});
</script>
@endpush
