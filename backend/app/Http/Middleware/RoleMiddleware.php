<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            \Log::warning('[RoleMiddleware] Auth check failed', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'session_id' => session()->getId(),
                'has_session' => $request->hasSession(),
            ]);
            return redirect('/login')->with('error', 'Vui lòng đăng nhập để tiếp tục.');
        }

        $user = Auth::user();
        
        if (!in_array($user->role, $roles)) {
            if ($user->role === 'candidate') {
                return redirect('/')->with('error', 'Bạn không có quyền truy cập trang này.');
            }
            return redirect('/admin/dashboard')->with('error', 'Bạn không có quyền truy cập trang này.');
        }

        return $next($request);
    }
}
