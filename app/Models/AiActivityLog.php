<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AiActivityLog extends Model
{
    protected $table = 'ai_activity_logs';

    protected $fillable = [
        'user_id',
        'activity_type',
        'model',
        'prompt',
        'output',
        'token_count',
        'status',
        'error_message',
        'latency_ms',
        'cost_cents',
        'ip_address',
        'user_agent',
        'meta',
    ];

    protected $casts = [
        'token_count' => 'integer',
        'latency_ms' => 'integer',
        'cost_cents' => 'integer',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeCurrentUser(Builder $query): Builder
    {
        return $query->where('user_id', Auth::id());
    }
}
