<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $provided = $request->header('X-Webhook-Secret');
        $expected = config('services.webhook.secret');

        if ($expected === null || $provided === null || ! hash_equals($expected, $provided)) {
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid webhook signature.');
        }

        return $next($request);
    }
}