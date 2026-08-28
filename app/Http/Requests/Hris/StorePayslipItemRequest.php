<?php

namespace App\Http\Requests\Hris;

use App\Enums\PayslipItemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePayslipItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('payroll.process');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(PayslipItemType::class)],
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
