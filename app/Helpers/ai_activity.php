<?php

use App\Services\AiActivityLogger;
use Illuminate\Http\Request;

if (! function_exists('log_ai_activity')) {
    function log_ai_activity(
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
    ) {
        return AiActivityLogger::log(
            $activityType,
            $model,
            $prompt,
            $output,
            $tokenCount,
            $status,
            $errorMessage,
            $latencyMs,
            $costCents,
            $meta,
            $request
        );
    }
}
