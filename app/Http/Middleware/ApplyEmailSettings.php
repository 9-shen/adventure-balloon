<?php

namespace App\Http\Middleware;

use App\Support\MailConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyEmailSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        MailConfig::applyFromDatabase();

        return $next($request);
    }
}

