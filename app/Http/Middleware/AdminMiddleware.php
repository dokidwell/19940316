<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // 检查用户是否为管理员
        if ($user->role !== 'admin') {
            abort(403, '权限不足：需要管理员权限');
        }

        return $next($request);
    }
}