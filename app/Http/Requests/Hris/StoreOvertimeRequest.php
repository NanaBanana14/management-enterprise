<?php

namespace App\Http\Requests\Hris;

use Illuminate\Foundation\Http\FormRequest;

class StoreOvertimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('overtime.create') && $this->user()->employee !== null;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'min:0.5', 'max:12'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
