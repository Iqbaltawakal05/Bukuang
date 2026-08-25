<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialGoal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'target_amount',
        'current_amount',
        'target_date',
        'description',
        'status',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'target_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contributions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GoalContribution::class);
    }
}
