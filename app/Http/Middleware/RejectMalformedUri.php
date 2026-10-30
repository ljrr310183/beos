<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectMalformedUri
{
    /**
     * Reject API requests whose request-target carries a fragment delimiter.
     *
     * Symfony normalises REQUEST_URI when it builds the ServerBag, dropping
     * everything from "#" onwards, so /api/products/#junk/prices reaches the
     * router as /api/products/ and silently matches the listing route. The raw
     * superglobal still holds the untouched value, so the check reads $_SERVER.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $rawUri = (string) ($_SERVER['REQUEST_URI'] ?? '');

        if (str_starts_with($rawUri, '/api/') && str_contains($rawUri, '#')) {
            return response()->json([
                'message' => 'URI malformada.',
                'error' => 'BAD_REQUEST',
            ], Response::HTTP_BAD_REQUEST);
        }

        return $next($request);
    }
}
