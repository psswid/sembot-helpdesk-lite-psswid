<?php

namespace App\Http\Requests\ExternalData;

use Illuminate\Foundation\Http\FormRequest;

class ExternalDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'city' => ['required', 'string', 'max:120'],
        ];
    }
}
