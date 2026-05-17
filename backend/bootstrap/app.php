<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Auth\AuthenticationException;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TwoFactorMiddleware;
use App\Http\Middleware\LocaleMiddleware;

// This project runs with the webroot at the repository root (cpanel_public_html)
// and the Laravel app in the "core" subfolder. Set the public path explicitly.
$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: [
            LocaleMiddleware::class,
        ]);
        
        // Register 2FA middleware alias for use in routes
        $middleware->alias([
            '2fa' => TwoFactorMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle CSRF token mismatch - redirect back with error instead of 419 page
        $exceptions->render(function (TokenMismatchException $e, $request) {
            \Log::warning('[CSRF] Token mismatch', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'session_id' => session()->getId(),
            ]);
            
            if ($request->expectsJson()) {
                return response()->json(['message' => 'CSRF token mismatch. Please refresh the page.'], 419);
            }
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
        });
        
        // Handle authentication exception
        $exceptions->render(function (AuthenticationException $e, $request) {
            \Log::warning('[Auth] Unauthenticated request', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);
            
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            
            return redirect()->route('login')
                ->with('error', 'Vui lòng đăng nhập để tiếp tục.');
        });
    })->create();

if (str_starts_with(__DIR__, '/var/www/html')) {
    $app->usePublicPath(dirname(__DIR__) . '/public');
} else {
    $app->usePublicPath(dirname(__DIR__, 2));
}

return $app;
