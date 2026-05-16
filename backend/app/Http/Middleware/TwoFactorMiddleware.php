<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * This middleware ensures that users with 2FA enabled have completed
     * the two-factor authentication before accessing protected routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // If not authenticated, let other middleware handle it
        if (!$user) {
            return $next($request);
        }

        // If user has 2FA enabled and hasn't verified yet in this session
        if ($user->two_factor_enabled && !session('2fa:verified')) {
            // Logout and redirect to 2FA verification
            Auth::logout();
            
            return redirect()->route('login')
                ->with('error', 'Vui lòng hoàn tất xác thực 2 yếu tố.');
        }

        return $next($request);
    }
}
