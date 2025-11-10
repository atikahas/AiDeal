<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImageJob extends Model
{
    protected $fillable = [
        'user_id',
        'tool',
        'input_json',
        'source_image_path',
        'mask_image_path',
        'result_image_path',
        'generated_images',
        'status',
        'is_saved',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'input_json' => 'array',
        'generated_images' => 'array',
        'is_saved' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
