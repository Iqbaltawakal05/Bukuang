<?php

namespace App\Http\Resources;

use App\Services\FinancialGoalService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialGoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $financialGoalService = app(FinancialGoalService::class);
        $summary = $financialGoalService->getFinancialGoalSummary($this->resource);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'target_amount' => (float) $this->target_amount,
            'current_amount' => (float) $this->current_amount,
            'remaining' => $summary['remaining'],
            'percentage' => $summary['percentage'],
            'target_date' => $this->target_date?->format('Y-m-d'),
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
