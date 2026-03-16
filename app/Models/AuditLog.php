<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'model_type',
        'model_id',
        'model_name',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // Relationship to get the user who performed the action
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scope to filter by user
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Scope to filter by action type
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    // Scope to filter by model type
    public function scopeByModelType($query, $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    // Scope to filter by date range
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}