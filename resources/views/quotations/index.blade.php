@extends('layouts.crm')

@section('title', 'Quotations')
@section('module', 'quotations')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Home</a><span>&rsaquo;</span> Quotations
@endsection

@section('toolbar')
    <div class="crm-page-title"><i class="bi bi-file-earmark-text"></i> Quotations</div>
    <div class="crm-toolbar-actions">
        <button type="button" class="crm-btn" id="refresh-btn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        @can('create', App\Models\Quotation::class)
            <button type="button" class="crm-btn crm-btn-primary-sm" id="add-btn"><i class="bi bi-plus-lg"></i> Create Quotation</button>
        @endcan
    </div>
@endsection

@section('content')
    <div class="crm-content-card">
        <div class="crm-content-card-body">
            <div class="crm-filters">
                <input type="text" id="search" class="crm-input" placeholder="Search quotation number...">
                <select id="status" class="crm-input">
                    <option value="">All Status</option>
                    @foreach (App\Enums\QuotationStatus::cases() as $s)
                        <option value="{{ $s->value }}">{{ $s->value }}</option>
                    @endforeach
                </select>
                <button type="button" id="filter-btn" class="crm-btn crm-btn-primary-sm"><i class="bi bi-funnel"></i> Filter</button>
            </div>
        </div>
        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>Quotation #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Total</th>
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
                <h3>Create Quotation</h3>
                <button type="button" class="crm-modal-close" id="modal-close">&times;</button>
            </div>
            <form id="quotation-form">
                <div class="crm-modal-body">
                    <div class="crm-form-grid">
                        <div class="crm-field">
                            <label class="crm-field-label">Customer</label>
                            <select class="crm-input" name="customer_id">
                                <option value="">Select Customer</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="crm-field"><label class="crm-field-label">Quotation Date *</label><input class="crm-input" type="date" name="quotation_date" value="{{ date('Y-m-d') }}" required></div>
                        <div class="crm-field"><label class="crm-field-label">Valid Until</label><input class="crm-input" type="date" name="valid_until"></div>
                    </div>
                    <div class="crm-field">
                        <label class="crm-field-label">Line Items</label>
                        <table class="crm-line-items-table" id="items-table">
                            <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Tax %</th><th></th></tr></thead>
                            <tbody id="items-body"></tbody>
                        </table>
                        <button type="button" class="crm-btn" id="add-item" style="margin-top:8px;"><i class="bi bi-plus"></i> Add Item</button>
                    </div>
                    <p id="form-error" class="crm-auth-error"></p>
                </div>
                <div class="crm-modal-foot">
                    <button type="button" class="crm-btn" id="modal-cancel">Cancel</button>
                    <button type="submit" class="crm-btn crm-btn-primary-sm">Save Quotation</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const products = @json($products);
    const modal = document.getElementById('modal');
    const itemsBody = document.getElementById('items-body');
    const tbody = document.getElementById('table-body');
    let currentPage = 1;
    const canDelete = @json(auth()->user()->can('quotations.delete'));

    const statusMap = { Draft: 'secondary', Sent: 'info', Accepted: 'success', Rejected: 'danger', Expired: 'warning' };

    function addItemRow(data = {}) {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input class="crm-input item-name" list="product-list" value="${data.product_name || ''}" placeholder="Product name" required></td>
            <td><input class="crm-input item-qty" type="number" step="0.01" min="0.01" value="${data.quantity || 1}" required></td>
            <td><input class="crm-input item-price" type="number" step="0.01" min="0" value="${data.unit_price || 0}" required></td>
            <td><input class="crm-input item-tax" type="number" step="0.01" min="0" value="${data.tax_rate || 0}"></td>
            <td><button type="button" class="crm-btn remove-item"><i class="bi bi-trash"></i></button></td>`;
        itemsBody.appendChild(tr);
    }

    function openModal() {
        document.getElementById('quotation-form').reset();
        itemsBody.innerHTML = '';
        addItemRow();
        document.getElementById('form-error').textContent = '';
        modal.style.display = 'flex';
    }
    function closeModal() { modal.style.display = 'none'; }

    async function load(page = 1) {
        const params = new URLSearchParams({ page, search: document.getElementById('search').value, status: document.getElementById('status').value });
        const res = await fetch(`{{ route('quotations.datatable') }}?${params}`, { headers: { 'Accept': 'application/json' } });
        const json = await res.json();
        tbody.innerHTML = (json.data || []).map(row => {
            const st = row.status?.value || row.status || 'Draft';
            return `<tr>
                <td><strong>${row.quotation_number}</strong></td>
                <td>${row.customer?.name || '—'}</td>
                <td>${row.quotation_date ? row.quotation_date.substring(0,10) : '—'}</td>
                <td><span class="crm-badge crm-badge-${statusMap[st] || 'secondary'}">${st}</span></td>
                <td>₹${Number(row.total || 0).toLocaleString()}</td>
                <td class="crm-action-menu">${canDelete ? `<button type="button" data-delete="${row.id}"><i class="bi bi-trash"></i></button>` : ''}</td>
            </tr>`;
        }).join('') || '<tr><td colspan="6" style="text-align:center;padding:32px;color:var(--crm-muted);">No quotations found</td></tr>';
        const m = json.meta;
        document.getElementById('record-info').textContent = `Showing ${m.total ? (m.current_page-1)*m.per_page+1 : 0}-${Math.min(m.current_page*m.per_page,m.total)} of ${m.total} records`;
        document.getElementById('pagination').innerHTML = `<button ${m.current_page<=1?'disabled':''} data-page="${m.current_page-1}">&lsaquo;</button><span class="active">${m.current_page}</span><button ${m.current_page>=m.last_page?'disabled':''} data-page="${m.current_page+1}">&rsaquo;</button>`;
        currentPage = m.current_page;
    }

    document.getElementById('add-btn')?.addEventListener('click', openModal);
    document.getElementById('add-item').addEventListener('click', () => addItemRow());
    itemsBody.addEventListener('click', e => { if (e.target.closest('.remove-item') && itemsBody.children.length > 1) e.target.closest('tr').remove(); });
    ['modal-close','modal-cancel'].forEach(id => document.getElementById(id).addEventListener('click', closeModal));
    document.getElementById('filter-btn').addEventListener('click', () => load(1));
    document.getElementById('refresh-btn').addEventListener('click', () => load(currentPage));
    document.getElementById('pagination').addEventListener('click', e => { const b = e.target.closest('button[data-page]'); if (b) load(+b.dataset.page); });

    tbody.addEventListener('click', async e => {
        const del = e.target.closest('button[data-delete]');
        if (!del || !confirm('Delete quotation?')) return;
        await fetch(`{{ url('quotations') }}/${del.dataset.delete}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } });
        load(currentPage);
    });

    document.getElementById('quotation-form').addEventListener('submit', async e => {
        e.preventDefault();
        const form = e.target;
        const items = [...itemsBody.querySelectorAll('tr')].map(tr => ({
            product_name: tr.querySelector('.item-name').value,
            quantity: parseFloat(tr.querySelector('.item-qty').value),
            unit_price: parseFloat(tr.querySelector('.item-price').value),
            tax_rate: parseFloat(tr.querySelector('.item-tax').value || 0),
        }));
        const payload = {
            customer_id: form.customer_id.value || null,
            quotation_date: form.quotation_date.value,
            valid_until: form.valid_until.value || null,
            items,
        };
        const res = await fetch('{{ route('quotations.store') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify(payload),
        });
        const json = await res.json();
        if (json.success) { closeModal(); load(1); }
        else document.getElementById('form-error').textContent = json.message || Object.values(json.errors || {}).flat().join(' ');
    });
    load();
});
</script>
<datalist id="product-list">
    @foreach ($products as $p)
        <option value="{{ $p->name }}" data-price="{{ $p->price }}" data-tax="{{ $p->tax_rate }}">
    @endforeach
</datalist>
@endpush
