@extends('layouts.crm')

@section('title', 'Products')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Home</a><span>&rsaquo;</span> Products
@endsection

@section('toolbar')
    <div class="crm-page-title"><i class="bi bi-box-seam"></i> Products</div>
    <div class="crm-toolbar-actions">
        <button type="button" class="crm-btn" id="refresh-btn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        @can('create', App\Models\Product::class)
            <button type="button" class="crm-btn crm-btn-primary-sm" id="add-btn"><i class="bi bi-plus-lg"></i> Add Product</button>
        @endcan
    </div>
@endsection

@section('content')
    <div class="crm-content-card">
        <div class="crm-content-card-body">
            <div class="crm-filters">
                <input type="text" id="search" class="crm-input" placeholder="Search products...">
                <select id="category_id" class="crm-input">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
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
                        <th data-sort="name" data-dir="asc">Name</th>
                        <th data-sort="sku" data-dir="asc">SKU</th>
                        <th data-sort="category_id" data-dir="asc">Category</th>
                        <th data-sort="price" data-dir="desc">Price</th>
                        <th data-sort="tax_rate" data-dir="desc">Tax %</th>
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

    <div class="crm-modal-backdrop" id="modal" style="display:none;">
        <div class="crm-modal crm-modal-lg">
            <div class="crm-modal-head">
                <h3 id="modal-title">Add Product</h3>
                <button type="button" class="crm-modal-close" id="modal-close">&times;</button>
            </div>
            <form id="product-form">
                <div class="crm-modal-body">
                    <div class="crm-form-grid">
                        <div class="crm-field"><label class="crm-field-label">Name *</label><input class="crm-input" name="name" required></div>
                        <div class="crm-field"><label class="crm-field-label">SKU</label><input class="crm-input" name="sku"></div>
                        <div class="crm-field">
                            <label class="crm-field-label">Category</label>
                            <select class="crm-input" name="category_id">
                                <option value="">None</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="crm-field"><label class="crm-field-label">Unit</label><input class="crm-input" name="unit" placeholder="pcs"></div>
                        <div class="crm-field"><label class="crm-field-label">Price</label><input class="crm-input" type="number" step="0.01" name="price" min="0"></div>
                        <div class="crm-field"><label class="crm-field-label">Tax Rate %</label><input class="crm-input" type="number" step="0.01" name="tax_rate" min="0"></div>
                        <div class="crm-field"><label class="crm-field-label">HSN Code</label><input class="crm-input" name="hsn_code"></div>
                        <div class="crm-field"><label class="crm-field-label">Capacity</label><input class="crm-input" name="capacity" placeholder="e.g. 100 ml to 5000 ml"></div>
                    </div>
                    <div class="crm-field"><label class="crm-field-label">Description</label><textarea class="crm-input" name="description" rows="2"></textarea></div>
                    <div class="crm-field"><label class="crm-field-label">Operation</label><textarea class="crm-input" name="operation" rows="2"></textarea></div>
                    <div class="crm-field"><label class="crm-field-label">Technical Specs (JSON)</label><textarea class="crm-input" name="technical_specifications" rows="3" placeholder='{"PLC":"Delta Make"}'></textarea></div>
                    <div class="crm-field"><label class="crm-field-label">Input Specs (JSON)</label><textarea class="crm-input" name="input_specifications" rows="2"></textarea></div>
                    <div class="crm-field"><label class="crm-field-label">Salient Features (JSON array)</label><textarea class="crm-input" name="salient_features" rows="2" placeholder='["No Bottle No Filling"]'></textarea></div>
                    <div class="crm-field"><label class="crm-field-label">Utility Requirements (JSON)</label><textarea class="crm-input" name="utility_requirements" rows="2"></textarea></div>
                    <div class="crm-field" id="active-field" style="display:none;">
                        <label class="crm-checkbox"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                    </div>
                    <p id="form-error" class="crm-auth-error"></p>
                </div>
                <div class="crm-modal-foot">
                    <button type="button" class="crm-btn" id="modal-cancel">Cancel</button>
                    <button type="submit" class="crm-btn crm-btn-primary-sm">Save Product</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modal');
    const form = document.getElementById('product-form');
    const tbody = document.getElementById('table-body');
    const tableSort = CrmTable.create({ sort_by: 'id', sort_dir: 'desc' });
    let currentPage = 1, editId = null;
    const canEdit = @json(auth()->user()->can('products.edit'));
    const canDelete = @json(auth()->user()->can('products.delete'));

    function openModal(title, data = null) {
        editId = data?.id || null;
        document.getElementById('modal-title').textContent = title;
        document.getElementById('active-field').style.display = editId ? 'block' : 'none';
        form.reset();
        if (data) {
            Object.keys(data).forEach(k => {
                if (!form.elements[k]) return;
                let val = data[k] ?? '';
                if (['technical_specifications','input_specifications','salient_features','utility_requirements'].includes(k) && val && typeof val === 'object') {
                    val = JSON.stringify(val, null, 2);
                }
                form.elements[k].value = val;
            });
            if (data.category_id) form.elements.category_id.value = data.category_id;
            form.elements.is_active.checked = !!data.is_active;
        }
        document.getElementById('form-error').textContent = '';
        modal.style.display = 'flex';
    }
    function closeModal() { modal.style.display = 'none'; editId = null; }

    async function load(page = 1) {
        const params = new URLSearchParams(tableSort.params({
            page, search: document.getElementById('search').value,
            category_id: document.getElementById('category_id').value,
        }));
        const res = await fetch(`{{ route('products.datatable') }}?${params}`, { headers: { 'Accept': 'application/json' } });
        const json = await res.json();
        const m = json.meta;
        tbody.innerHTML = (json.data || []).map((row, i) => `
            <tr>
                <td class="crm-sr">${tableSort.sr(m, i)}</td>
                <td><strong>${row.name}</strong></td>
                <td>${row.sku || '—'}</td>
                <td>${row.category?.name || '—'}</td>
                <td>₹${Number(row.price || 0).toLocaleString()}</td>
                <td>${row.tax_rate ?? 0}%</td>
                <td><span class="crm-badge crm-badge-${row.is_active ? 'success' : 'secondary'}">${row.is_active ? 'Active' : 'Inactive'}</span></td>
                <td class="crm-action-menu">
                    ${canEdit ? `<button type="button" data-edit='${JSON.stringify(row).replace(/'/g,"&#39;")}'><i class="bi bi-pencil"></i></button>` : ''}
                    ${canDelete ? `<button type="button" data-delete="${row.id}"><i class="bi bi-trash"></i></button>` : ''}
                </td>
            </tr>`).join('') || '<tr><td colspan="8" style="text-align:center;padding:32px;color:var(--crm-muted);">No products found</td></tr>';
        document.getElementById('record-info').textContent = `Showing ${m.total ? (m.current_page-1)*m.per_page+1 : 0}-${Math.min(m.current_page*m.per_page,m.total)} of ${m.total} records`;
        document.getElementById('pagination').innerHTML = `<button ${m.current_page<=1?'disabled':''} data-page="${m.current_page-1}">&lsaquo;</button><span class="active">${m.current_page}</span><button ${m.current_page>=m.last_page?'disabled':''} data-page="${m.current_page+1}">&rsaquo;</button>`;
        currentPage = m.current_page;
    }

    tableSort.bind(document.querySelector('.crm-table'), load);
    document.getElementById('add-btn')?.addEventListener('click', () => openModal('Add Product'));
    ['modal-close','modal-cancel'].forEach(id => document.getElementById(id).addEventListener('click', closeModal));
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
            if (!confirm('Delete product?')) return;
            await fetch(`{{ url('products') }}/${del.dataset.delete}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } });
            return load(currentPage);
        }
        const ed = e.target.closest('button[data-edit]');
        if (ed) openModal('Edit Product', JSON.parse(ed.dataset.edit));
    });

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(form));
        if (!data.category_id) delete data.category_id;
        if (editId) data.is_active = form.elements.is_active.checked;
        const url = editId ? `{{ url('products') }}/${editId}` : '{{ route('products.store') }}';
        const res = await fetch(url, {
            method: editId ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify(data),
        });
        const json = await res.json();
        if (json.success) { closeModal(); load(currentPage); }
        else document.getElementById('form-error').textContent = json.message || Object.values(json.errors || {}).flat().join(' ');
    });
    load();
});
</script>
@endpush
