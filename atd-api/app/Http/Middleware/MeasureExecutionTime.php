<?php

namespace App\Http\Middleware;

use Closure;

class MeasureExecutionTime
{
    public function handle($request, Closure $next)
    {
        $start_time = microtime(true);

        $response = $next($request);

        $end_time = microtime(true);

        $execution_time = $end_time - $start_time;

        $response->headers->add(['X-Execution-Time' => $execution_time]);

        return $response;
    }
}
