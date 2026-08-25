<?php

namespace App\Http\Resources;

use App\Services\BudgetService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $budgetService = app(BudgetService::class);
        $summary = $budgetService->getBudgetSummary($this->resource);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'amount' => (float) $this->amount,
            'month' => $this->month,
            'year' => $this->year,
            'spent' => $summary['spent'],
            'remaining' => $summary['remaining'],
            'percentage' => $summary['percentage'],
            'status' => $summary['status'],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
