<?php

namespace App\Http\Middleware;

use App\Support\FixMode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! FixMode::enabled()) {
            return $next($request);
        }

        $expected = (string) config('lab.webhook_token');
        $provided = (string) $request->header('X-Webhook-Token', '');

        if ($provided === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['ok' => false, 'error' => 'missing_or_invalid_token'], 401);
        }

        return $next($request);
    }
}
