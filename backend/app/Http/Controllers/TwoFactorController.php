<?php

namespace App\Http\Controllers;

use App\Mail\TwoFactorCodeMail;
use App\Models\TwoFactorCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class TwoFactorController extends Controller
{
    /**
     * Show the OTP verification form
     */
    public function show()
    {
        // If user is not in 2FA pending state, redirect
        if (!session('2fa:user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    /**
     * Verify the OTP code
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ], [
            'code.required' => 'Vui lòng nhập mã xác thực.',
            'code.size' => 'Mã xác thực phải có 6 chữ số.',
        ]);

        $userId = session('2fa:user_id');
        $remember = session('2fa:remember', false);

        if (!$userId) {
            return redirect()->route('login')
                ->with('error', 'Phiên xác thực đã hết hạn. Vui lòng đăng nhập lại.');
        }

        $user = \App\Models\User::find($userId);

        if (!$user) {
            session()->forget(['2fa:user_id', '2fa:remember']);
            return redirect()->route('login')
                ->with('error', 'Không tìm thấy tài khoản.');
        }

        // Rate limit OTP attempts
        $throttleKey = '2fa-verify:' . $userId;
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'code' => 'Bạn đã thử quá nhiều lần. Vui lòng thử lại sau ' . $seconds . ' giây.',
            ]);
        }

        // Verify the code
        if (!TwoFactorCode::verify($user, $request->code)) {
            RateLimiter::hit($throttleKey, 60);
            return back()->withErrors([
                'code' => 'Mã xác thực không đúng hoặc đã hết hạn.',
            ]);
        }

        // Clear rate limiter and session
        RateLimiter::clear($throttleKey);
        session()->forget(['2fa:user_id', '2fa:remember']);

        // Mark 2FA as verified in session
        session(['2fa:verified' => true]);

        // Login the user
        Auth::login($user, $remember);
        $request->session()->regenerate();

        // Redirect based on role
        if ($user->role === 'admin' || $user->role === 'recruiter') {
            return redirect()->intended('/admin/dashboard')
                ->with('status', 'Đăng nhập thành công!');
        }

        return redirect()->intended('/')
            ->with('status', 'Đăng nhập thành công!');
    }

    /**
     * Resend OTP code
     */
    public function resend(Request $request)
    {
        $userId = session('2fa:user_id');

        if (!$userId) {
            return redirect()->route('login')
                ->with('error', 'Phiên xác thực đã hết hạn. Vui lòng đăng nhập lại.');
        }

        // Rate limit resend attempts
        $throttleKey = '2fa-resend:' . $userId;
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->with('error', 'Bạn đã yêu cầu gửi lại quá nhiều lần. Vui lòng thử lại sau ' . $seconds . ' giây.');
        }

        $user = \App\Models\User::find($userId);

        if (!$user) {
            session()->forget(['2fa:user_id', '2fa:remember']);
            return redirect()->route('login')
                ->with('error', 'Không tìm thấy tài khoản.');
        }

        // Generate new code and send
        $twoFactorCode = TwoFactorCode::generateFor($user);
        
        try {
            Mail::to($user->email)->send(new TwoFactorCodeMail($user, $twoFactorCode->code));
        } catch (\Throwable $e) {
            \Log::error('[2FA] Failed to send OTP email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Không thể gửi email. Vui lòng thử lại sau.');
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->with('status', 'Mã xác thực mới đã được gửi đến email của bạn.');
    }

    /**
     * Cancel 2FA and return to login
     */
    public function cancel()
    {
        $userId = session('2fa:user_id');
        
        if ($userId) {
            // Delete any pending codes
            TwoFactorCode::where('user_id', $userId)->delete();
        }

        session()->forget(['2fa:user_id', '2fa:remember']);

        return redirect()->route('login')
            ->with('status', 'Đã hủy đăng nhập.');
    }
}
