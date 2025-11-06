<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'label',
        'secret',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'secret' => 'encrypted',
    ];

    public function scopeForUser($query, ?int $userId)
    {
        return $query->where(function ($scope) use ($userId) {
            $scope->whereNull('user_id');

            if ($userId !== null) {
                $scope->orWhere('user_id', $userId);
            }
        });
    }
}
