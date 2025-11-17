<?php

namespace App\Http\Requests\Triage;

use Illuminate\Foundation\Http\FormRequest;

class AcceptTriageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by route middleware (can:update,ticket)
        return true;
    }

    public function rules(): array
    {
        return [
            'priority' => ['required', 'string', 'in:low,medium,high'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'min:1', 'max:30'],
            'assignee_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'status' => ['sometimes', 'string', 'in:open,in_progress,resolved,closed'],
            'correlation_id' => ['sometimes', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'priority.in' => 'Priority must be one of: low, medium, high.',
            'status.in' => 'Status must be one of: open, in_progress, resolved, closed.',
        ];
    }
}
