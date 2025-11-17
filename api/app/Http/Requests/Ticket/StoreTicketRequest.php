<?php

namespace App\Http\Requests\Ticket;

use App\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
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

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'string', Rule::in($priorityValues)],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string'],
            // reporter_id is set server-side; ignore any client provided value
        ];
    }
}
