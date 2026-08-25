<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoalContributionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'financial_goal_id' => $this->financial_goal_id,
            'amount' => (float) $this->amount,
            'contribution_date' => $this->contribution_date ? $this->contribution_date->format('Y-m-d') : null,
            'notes' => $this->notes,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
