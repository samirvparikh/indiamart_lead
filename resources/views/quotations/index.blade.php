@extends('layouts.crm')

@section('title', 'Quotations')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Home</a><span>&rsaquo;</span> Quotations
@endsection

@section('toolbar')
    <div class="crm-page-title"><i class="bi bi-file-earmark-text"></i> Quotations</div>
    <div class="crm-toolbar-actions">
        <button type="button" class="crm-btn" id="refresh-btn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        @can('create', App\Models\Quotation::class)
            <a href="{{ route('quotations.create') }}" class="crm-btn crm-btn-primary-sm"><i class="bi bi-plus-lg"></i> Create Quotation</a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="crm-content-card">
        <div class="crm-content-card-body">
            <div class="crm-filters">
                <input type="text" id="search" class="crm-input" placeholder="Search quotation / customer / subject...">
                <select id="status" class="crm-input">
                    <option value="">All Status</option>
                    @foreach (App\Enums\QuotationStatus::cases() as $s)
                        <option value="{{ $s->value }}">{{ $s->value }}</option>
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
                        <th data-sort="quotation_number" data-dir="asc">Quotation #</th>
                        <th data-sort="subject" data-dir="asc">Subject</th>
                        <th>Customer</th>
                        <th data-sort="quotation_date" data-dir="desc">Date</th>
                        <th data-sort="status" data-dir="asc">Status</th>
                        <th data-sort="total" data-dir="desc">Total</th>
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
    const tableSort = CrmTable.create({ sort_by: 'id', sort_dir: 'desc' });
    let currentPage = 1;
    const canDelete = @json(auth()->user()->can('quotations.delete'));
    const statusMap = { Draft: 'secondary', Sent: 'info', Accepted: 'success', Rejected: 'danger', Expired: 'warning' };

    async function load(page = 1) {
        const params = new URLSearchParams(tableSort.params({
            page,
            search: document.getElementById('search').value,
            status: document.getElementById('status').value,
        }));
        const res = await fetch(`{{ route('quotations.datatable') }}?${params}`, {
            headers: { 'Accept': 'application/json' },
        });
        const json = await res.json();
        const m = json.meta;
        tbody.innerHTML = (json.data || []).map((row, i) => {
            const st = row.status?.value || row.status || 'Draft';
            return `<tr>
                <td class="crm-sr">${tableSort.sr(m, i)}</td>
                <td><a href="{{ url('quotations') }}/${row.id}"><strong>${row.quotation_number}</strong></a></td>
                <td>${row.subject || '—'}</td>
                <td>${row.customer?.name || '—'}</td>
                <td>${row.quotation_date ? row.quotation_date.substring(0,10) : '—'}</td>
                <td><span class="crm-badge crm-badge-${statusMap[st] || 'secondary'}">${st}</span></td>
                <td>₹${Number(row.total || 0).toLocaleString()}</td>
                <td class="crm-action-menu">
                    <a href="{{ url('quotations') }}/${row.id}" title="View"><i class="bi bi-eye"></i></a>
                    <a href="{{ url('quotations') }}/${row.id}/pdf" title="Download PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                    ${canDelete ? `<button type="button" data-delete="${row.id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
                </td>
            </tr>`;
        }).join('') || '<tr><td colspan="8" style="text-align:center;padding:32px;color:var(--crm-muted);">No quotations found</td></tr>';

        document.getElementById('record-info').textContent =
            `Showing ${m.total ? (m.current_page-1)*m.per_page+1 : 0}-${Math.min(m.current_page*m.per_page,m.total)} of ${m.total} records`;
        document.getElementById('pagination').innerHTML =
            `<button ${m.current_page<=1?'disabled':''} data-page="${m.current_page-1}">&lsaquo;</button><span class="active">${m.current_page}</span><button ${m.current_page>=m.last_page?'disabled':''} data-page="${m.current_page+1}">&rsaquo;</button>`;
        currentPage = m.current_page;
    }

    tableSort.bind(document.querySelector('.crm-table'), load);
    document.getElementById('filter-btn').addEventListener('click', () => load(1));
    document.getElementById('reset-btn').addEventListener('click', () => {
        document.querySelectorAll('.crm-filters .crm-input').forEach((el) => { el.value = ''; });
        load(1);
    });
    document.getElementById('refresh-btn').addEventListener('click', () => load(currentPage));
    document.getElementById('pagination').addEventListener('click', e => {
        const b = e.target.closest('button[data-page]');
        if (b) load(+b.dataset.page);
    });

    tbody.addEventListener('click', async e => {
        const del = e.target.closest('button[data-delete]');
        if (!del || !confirm('Delete quotation?')) return;
        await fetch(`{{ url('quotations') }}/${del.dataset.delete}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        });
        load(currentPage);
    });

    load();
});
</script>
@endpush
