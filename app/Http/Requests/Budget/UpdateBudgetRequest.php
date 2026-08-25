<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;
        $budgetId = $this->route('budget')->id;

        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($userId) {
                    $query->whereNull('user_id')->orWhere('user_id', $userId);
                }),
                Rule::unique('budgets')->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId)
                        ->where('month', $this->input('month'))
                        ->where('year', $this->input('year'))
                        ->whereNull('deleted_at');
                })->ignore($budgetId),
            ],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.unique' => 'Budget untuk kategori ini pada bulan dan tahun yang sama sudah ada.',
        ];
    }
}
