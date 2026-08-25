<?php

namespace App\Http\Requests\FinancialGoal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinancialGoalRequest extends FormRequest
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
            'current_amount' => ['nullable', 'numeric', 'gte:0', 'max:999999999999.99'],
            'target_date' => ['required', 'date', 'after_or_equal:today'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in(['active', 'completed', 'cancelled'])],
        ];
    }
}
