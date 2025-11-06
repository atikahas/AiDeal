<?php

namespace App\Services;

use App\Models\AiActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiActivityLogger
{
    public static function log(
        string $activityType,
        ?string $model = null,
        ?string $prompt = null,
        ?string $output = null,
        int $tokenCount = 0,
        string $status = 'success',
        ?string $errorMessage = null,
        int $latencyMs = 0,
        int $costCents = 0,
        ?array $meta = null,
        ?Request $request = null
    ): AiActivityLog {
        $request = $request ?? request();
        
        return AiActivityLog::create([
            'user_id' => Auth::id(),
            'activity_type' => $activityType,
            'model' => $model,
            'prompt' => $prompt,
            'output' => $output,
            'token_count' => $tokenCount,
            'status' => $status,
            'error_message' => $errorMessage,
            'latency_ms' => $latencyMs,
            'cost_cents' => $costCents,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'meta' => $meta,
        ]);
    }
}
