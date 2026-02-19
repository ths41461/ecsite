<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiChatSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_token',
        'quiz_result_id',
        'context_json',
    ];

    protected $casts = [
        'context_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'session_id');
    }

    public function quizResult(): BelongsTo
    {
        return $this->belongsTo(QuizResult::class);
    }
}
