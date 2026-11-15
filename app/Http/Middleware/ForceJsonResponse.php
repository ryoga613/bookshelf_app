<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API へのリクエストは Accept ヘッダーに関係なく JSON で応答させる
 * （バリデーションエラー時に 302 リダイレクトではなく 422 の JSON を返すため）
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
