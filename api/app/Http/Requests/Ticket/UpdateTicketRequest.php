<?php

namespace App\Http\Requests\Ticket;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route middleware enforces policy; this request can allow.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $priorityValues = array_map(static fn (TicketPriority $e) => $e->value, TicketPriority::cases());
        $statusValues = array_map(static fn (TicketStatus $e) => $e->value, TicketStatus::cases());

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'priority' => ['sometimes', 'string', Rule::in($priorityValues)],
            'status' => ['sometimes', 'string', Rule::in($statusValues)],
            'assignee_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'location' => ['sometimes', 'string', 'max:120'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string'],
        ];
    }
}
