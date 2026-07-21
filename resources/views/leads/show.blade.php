@extends('layouts.crm')

@section('title', $lead->lead_number)

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Home</a><span>&rsaquo;</span>
    <a href="{{ route('leads.index') }}">Leads</a><span>&rsaquo;</span> {{ $lead->lead_number }}
@endsection

@section('toolbar')
    <div class="crm-page-title"><i class="bi bi-funnel"></i> {{ $lead->customer_name }}</div>
    <div class="crm-toolbar-actions">
        @can('update', $lead)
            <a href="{{ route('leads.edit', $lead) }}" class="crm-btn crm-btn-primary-sm"><i class="bi bi-pencil"></i> Edit</a>
        @endcan
        <a href="{{ route('leads.index') }}" class="crm-btn">Back</a>
    </div>
@endsection

@section('content')
    <div class="crm-stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));">
        <div class="crm-stat-card"><p>Status</p><h3 style="font-size:1rem;">{{ $lead->status?->value }}</h3></div>
        <div class="crm-stat-card"><p>Priority</p><h3 style="font-size:1rem;">{{ $lead->priority?->value }}</h3></div>
        <div class="crm-stat-card"><p>Assigned</p><h3 style="font-size:1rem;">{{ $lead->assignee?->name ?? '—' }}</h3></div>
        <div class="crm-stat-card"><p>Mobile</p><h3 style="font-size:1rem;">{{ $lead->mobile ?? '—' }}</h3></div>
        <div class="crm-stat-card"><p>Source</p><h3 style="font-size:1rem;">{{ $lead->leadSource?->name ?? '—' }}</h3></div>
    </div>

    @if($lead->requirement)
        <div class="crm-content-card" style="margin-bottom:16px;">
            <div class="crm-content-card-body">
                <h4 style="margin:0 0 8px;font-size:0.9rem;color:var(--crm-muted);">REQUIREMENT</h4>
                <p style="margin:0;">{{ $lead->requirement }}</p>
            </div>
        </div>
    @endif

    <div class="crm-content-card">
        <div class="crm-content-card-body">
            <h4 style="margin:0 0 16px;font-size:0.9rem;color:var(--crm-muted);">ACTIVITY TIMELINE</h4>
            <div class="crm-table-wrap">
                <table class="crm-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date & Time</th>
                            <th>Activity</th>
                            <th>Description</th>
                            <th>Action By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lead->activities as $activity)
                            <tr>
                                <td class="crm-sr">{{ $loop->iteration }}</td>
                                <td style="white-space:nowrap;">{{ $activity->created_at?->format('d M Y, h:i A') }}</td>
                                <td><span class="crm-badge crm-badge-info">{{ $activity->type?->value }}</span></td>
                                <td>{{ $activity->description ?: '—' }}</td>
                                <td>{{ $activity->causer?->name ?? 'System' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align:center;color:var(--crm-muted);padding:32px;">No activities yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('recordAction', $lead)
        <div class="crm-content-card crm-lead-action-card">
            <div class="crm-content-card-body">
                <div class="crm-lead-action-head">
                    <div>
                        <span class="crm-lead-action-kicker">LEAD ACTIVITY</span>
                        <h3>Record Lead Action</h3>
                        <p>Log the latest conversation and schedule the next follow-up.</p>
                    </div>
                    <span class="crm-lead-action-icon"><i class="bi bi-chat-square-text"></i></span>
                </div>

                <form id="lead-action-form">
                    @csrf
                    <div class="crm-form-grid">
                        <div class="crm-field">
                            <label class="crm-field-label" for="action_type">Action Type *</label>
                            <select class="crm-input" id="action_type" name="action_type" required>
                                @foreach (App\Enums\FollowupType::cases() as $type)
                                    <option value="{{ $type->value }}">{{ $type->value }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="crm-field">
                            <label class="crm-field-label" for="status">Lead Status *</label>
                            <select class="crm-input" id="status" name="status" required>
                                @foreach (App\Enums\LeadStatus::cases() as $status)
                                    <option value="{{ $status->value }}" @selected($lead->status === $status)>{{ $status->value }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="crm-field">
                            @if(auth()->user()->canAccessAdministration())
                                <label class="crm-field-label" for="assigned_to">Assign To *</label>
                                <select class="crm-input" id="assigned_to" name="assigned_to" required>
                                    <option value="">Select user</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" @selected($lead->assigned_to == $user->id)>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <label class="crm-field-label">Assigned To</label>
                                <div class="crm-assignee-display">
                                    <i class="bi bi-person-check"></i>
                                    <span>
                                        {{ $lead->assignee?->name ?? auth()->user()->name }}
                                        @if(!$lead->assignee)
                                            <small>Will be assigned on submit</small>
                                        @endif
                                    </span>
                                </div>
                            @endif
                        </div>

                        <div class="crm-field">
                            <label class="crm-field-label" for="next_followup_at">Next Follow-up</label>
                            <input
                                class="crm-input"
                                id="next_followup_at"
                                name="next_followup_at"
                                type="datetime-local"
                                min="{{ now()->format('Y-m-d\TH:i') }}"
                                value="{{ $lead->next_followup_at?->format('Y-m-d\TH:i') }}"
                            >
                        </div>

                        <div class="crm-field crm-field-span-2">
                            <label class="crm-field-label" for="notes">Action Notes *</label>
                            <textarea
                                class="crm-input crm-action-notes"
                                id="notes"
                                name="notes"
                                rows="4"
                                maxlength="5000"
                                placeholder="Enter discussion, customer response, outcome, or next steps..."
                                required
                            ></textarea>
                        </div>
                    </div>

                    <div class="crm-lead-action-footer">
                        <p id="action-form-message" class="crm-auth-error" role="alert"></p>
                        <button type="submit" class="crm-btn crm-btn-primary-sm" id="action-submit-btn">
                            <i class="bi bi-check2-circle"></i> Save Activity
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection

@push('scripts')
<script>
document.getElementById('lead-action-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const form = event.currentTarget;
    const button = document.getElementById('action-submit-btn');
    const message = document.getElementById('action-form-message');
    const data = Object.fromEntries(new FormData(form));

    button.disabled = true;
    button.innerHTML = '<i class="bi bi-arrow-repeat"></i> Saving...';
    message.textContent = '';

    try {
        const response = await fetch('{{ route('leads.actions.store', $lead) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify(data),
        });
        const json = await response.json();

        if (!response.ok || !json.success) {
            const errors = json.errors ? Object.values(json.errors).flat().join(' ') : null;
            throw new Error(errors || json.message || 'Unable to save lead activity.');
        }

        await Swal.fire({
            icon: 'success',
            title: 'Activity saved',
            text: json.message,
            timer: 1200,
            showConfirmButton: false,
        });
        window.location.reload();
    } catch (error) {
        message.textContent = error.message;
        button.disabled = false;
        button.innerHTML = '<i class="bi bi-check2-circle"></i> Save Activity';
    }
});
</script>
@endpush
