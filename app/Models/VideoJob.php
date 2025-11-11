<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoJob extends Model
{
    protected $fillable = [
        'user_id',
        'tool',
        'input_json',
        'reference_image_path',
        'generated_videos',
        'status',
        'is_saved',
        'error_message',
        'operation_name',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'input_json' => 'array',
        'generated_videos' => 'array',
        'is_saved' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
