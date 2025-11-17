<?php

namespace App\Http\Requests\Ticket;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route middleware handles 'can:viewAny', so this request itself can authorize.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $statusValues = array_map(static fn (TicketStatus $e) => $e->value, TicketStatus::cases());
        $priorityValues = array_map(static fn (TicketPriority $e) => $e->value, TicketPriority::cases());

        return [
            'status' => ['sometimes', 'string', Rule::in($statusValues)],
            'priority' => ['sometimes', 'string', Rule::in($priorityValues)],
            'assignee_id' => ['sometimes', 'integer', 'exists:users,id'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
