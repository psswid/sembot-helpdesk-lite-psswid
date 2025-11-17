<?php

namespace App\Http\Requests\Triage;

use Illuminate\Foundation\Http\FormRequest;

class RejectTriageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['sometimes', 'string', 'max:1000'],
            'correlation_id' => ['sometimes', 'string'],
        ];
    }
}
