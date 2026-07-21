<?php

namespace App\Http\Requests\Lead;

use App\Enums\FollowupType;
use App\Enums\LeadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lead = $this->route('lead');

        return $lead && $this->user()?->can('recordAction', $lead);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $assigneeRules = $this->user()?->canAccessAdministration()
            ? [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ]
            : ['prohibited'];

        return [
            'action_type' => ['required', Rule::in(FollowupType::values())],
            'status' => ['required', Rule::in(LeadStatus::values())],
            'notes' => ['required', 'string', 'max:5000'],
            'next_followup_at' => ['nullable', 'date', 'after_or_equal:today'],
            'assigned_to' => $assigneeRules,
        ];
    }
}
