<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info("LogRequests: ISTEK GELDI -> {$request->method()} {$request->path()}");   // $next() ONCESI

        $response = $next($request);   // pipeline'da sonraki katmana / controller'a gec

        Log::info("LogRequests: RESPONSE GIDIYOR -> {$response->getStatusCode()}");   // $next() SONRASI

        return $response;
    }
}
