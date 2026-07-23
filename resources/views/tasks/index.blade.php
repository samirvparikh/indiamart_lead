@extends('layouts.crm')

@section('title', 'Tasks')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Home</a><span>&rsaquo;</span> Tasks
@endsection

@section('toolbar')
    <div class="crm-page-title"><i class="bi bi-list-check"></i> Tasks</div>
    <div class="crm-toolbar-actions">
        <button type="button" class="crm-btn" id="refresh-btn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        @can('create', App\Models\Task::class)
            <button type="button" class="crm-btn crm-btn-primary-sm" id="add-btn"><i class="bi bi-plus-lg"></i> Add Task</button>
        @endcan
    </div>
@endsection

@section('content')
    <div class="crm-content-card">
        <div class="crm-content-card-body">
            <div class="crm-filters">
                <input type="text" id="search" class="crm-input" placeholder="Search tasks...">
                <select id="status" class="crm-input">
                    <option value="">All Status</option>
                    @foreach (App\Enums\TaskStatus::cases() as $s)
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
                        <th data-sort="title" data-dir="asc">Title</th>
                        <th data-sort="assigned_to" data-dir="asc">Assigned To</th>
                        <th data-sort="priority" data-dir="asc">Priority</th>
                        <th data-sort="status" data-dir="asc">Status</th>
                        <th data-sort="due_date" data-dir="desc">Due Date</th>
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
        <div class="crm-modal">
            <div class="crm-modal-head">
                <h3 id="modal-title">Add Task</h3>
                <button type="button" class="crm-modal-close" id="modal-close">&times;</button>
            </div>
            <form id="task-form">
                <div class="crm-modal-body">
                    <div class="crm-field"><label class="crm-field-label">Title *</label><input class="crm-input" name="title" required></div>
                    <div class="crm-field"><label class="crm-field-label">Description</label><textarea class="crm-input" name="description" rows="2"></textarea></div>
                    <div class="crm-form-grid">
                        <div class="crm-field">
                            <label class="crm-field-label">Assign To</label>
                            <select class="crm-input" name="assigned_to">
                                <option value="">Unassigned</option>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="crm-field">
                            <label class="crm-field-label">Customer</label>
                            <select class="crm-input" name="customer_id">
                                <option value="">None</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="crm-field">
                            <label class="crm-field-label">Priority</label>
                            <select class="crm-input" name="priority">
                                @foreach (App\Enums\LeadPriority::cases() as $p)
                                    <option value="{{ $p->value }}" @selected($p->value === 'Medium')>{{ $p->value }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="crm-field"><label class="crm-field-label">Due Date</label><input class="crm-input" type="date" name="due_date"></div>
                    </div>
                    <div class="crm-field" id="status-field" style="display:none;">
                        <label class="crm-field-label">Status</label>
                        <select class="crm-input" name="status">
                            @foreach (App\Enums\TaskStatus::cases() as $s)
                                <option value="{{ $s->value }}">{{ $s->value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <p id="form-error" class="crm-auth-error"></p>
                </div>
                <div class="crm-modal-foot">
                    <button type="button" class="crm-btn" id="modal-cancel">Cancel</button>
                    <button type="submit" class="crm-btn crm-btn-primary-sm">Save Task</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modal');
    const form = document.getElementById('task-form');
    const tbody = document.getElementById('table-body');
    const tableSort = CrmTable.create({ sort_by: 'id', sort_dir: 'desc' });
    let currentPage = 1, editId = null;
    const canEdit = @json(auth()->user()->can('tasks.edit'));
    const canDelete = @json(auth()->user()->can('tasks.delete'));

    function openModal(title, data = null) {
        editId = data?.id || null;
        document.getElementById('modal-title').textContent = title;
        document.getElementById('status-field').style.display = editId ? 'block' : 'none';
        form.reset();
        if (data) {
            Object.keys(data).forEach(k => { if (form.elements[k]) form.elements[k].value = data[k] ?? ''; });
            const st = data.status?.value || data.status;
            if (st) form.elements.status.value = st;
            const pr = data.priority?.value || data.priority;
            if (pr) form.elements.priority.value = pr;
            if (data.due_date) form.elements.due_date.value = data.due_date.substring(0, 10);
        }
        document.getElementById('form-error').textContent = '';
        modal.style.display = 'flex';
    }
    function closeModal() { modal.style.display = 'none'; editId = null; }

    async function load(page = 1) {
        const params = new URLSearchParams(tableSort.params({ page, search: document.getElementById('search').value, status: document.getElementById('status').value }));
        const res = await fetch(`{{ route('tasks.datatable') }}?${params}`, { headers: { 'Accept': 'application/json' } });
        const json = await res.json();
        const m = json.meta;
        tbody.innerHTML = (json.data || []).map((row, i) => {
            const st = row.status?.value || row.status || 'Pending';
            const pr = row.priority?.value || row.priority || 'Medium';
            return `<tr>
                <td class="crm-sr">${tableSort.sr(m, i)}</td>
                <td><strong>${row.title}</strong></td>
                <td>${row.assignee?.name || '—'}</td>
                <td><span class="crm-badge crm-badge-secondary">${pr}</span></td>
                <td><span class="crm-badge crm-badge-${st === 'Completed' ? 'success' : 'warning'}">${st}</span></td>
                <td>${row.due_date ? row.due_date.substring(0,10) : '—'}</td>
                <td class="crm-action-menu">
                    ${canEdit ? `<button type="button" data-edit='${JSON.stringify(row).replace(/'/g,"&#39;")}'><i class="bi bi-pencil"></i></button>` : ''}
                    ${canDelete ? `<button type="button" data-delete="${row.id}"><i class="bi bi-trash"></i></button>` : ''}
                </td>
            </tr>`;
        }).join('') || '<tr><td colspan="7" style="text-align:center;padding:32px;color:var(--crm-muted);">No tasks found</td></tr>';
        document.getElementById('record-info').textContent = `Showing ${m.total ? (m.current_page-1)*m.per_page+1 : 0}-${Math.min(m.current_page*m.per_page,m.total)} of ${m.total} records`;
        document.getElementById('pagination').innerHTML = `<button ${m.current_page<=1?'disabled':''} data-page="${m.current_page-1}">&lsaquo;</button><span class="active">${m.current_page}</span><button ${m.current_page>=m.last_page?'disabled':''} data-page="${m.current_page+1}">&rsaquo;</button>`;
        currentPage = m.current_page;
    }

    tableSort.bind(document.querySelector('.crm-table'), load);
    document.getElementById('add-btn')?.addEventListener('click', () => openModal('Add Task'));
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
            if (!confirm('Delete task?')) return;
            await fetch(`{{ url('tasks') }}/${del.dataset.delete}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } });
            return load(currentPage);
        }
        const ed = e.target.closest('button[data-edit]');
        if (ed) openModal('Edit Task', JSON.parse(ed.dataset.edit));
    });

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(form));
        if (!data.assigned_to) delete data.assigned_to;
        if (!data.customer_id) delete data.customer_id;
        if (!data.due_date) delete data.due_date;
        const url = editId ? `{{ url('tasks') }}/${editId}` : '{{ route('tasks.store') }}';
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
