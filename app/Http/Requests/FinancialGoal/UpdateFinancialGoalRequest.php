<?php

namespace App\Http\Requests\FinancialGoal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFinancialGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'target_amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'current_amount' => ['required', 'numeric', 'gte:0', 'max:999999999999.99'],
            'target_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['active', 'completed', 'cancelled'])],
        ];
    }
}
