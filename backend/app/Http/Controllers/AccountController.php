<?php

namespace App\Http\Controllers;

use App\Mail\TwoFactorCodeMail;
use App\Models\TwoFactorCode;
use App\Services\ITSoloLevelingSecurity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    /**
     * Show account settings page
     */
    public function index()
    {
        $user = Auth::user();
        return view('account.settings', compact('user'));
    }

    /**
     * Update basic profile info
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ], [
            'name.required' => 'Vui lòng nhập họ tên.',
            'name.max' => 'Họ tên không được quá 255 ký tự.',
        ]);

        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->save();

        return back()->with('status', 'Cập nhật thông tin thành công!');
    }

    /**
     * Change password
     */
    public function changePassword(Request $request, ITSoloLevelingSecurity $security)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
            'password.min' => 'Mật khẩu mới phải có ít nhất 8 ký tự.',
        ]);

        // Verify current password
        $storedHash = (string) $user->password;
        $inputPassword = (string) $request->current_password;
        $authenticated = false;

        // Check with security service
        if ($storedHash !== '' && strlen($storedHash) === 64 && ctype_xdigit($storedHash)) {
            try {
                $authenticated = $security->authenticate($inputPassword, $storedHash);
            } catch (\Throwable $e) {
                $authenticated = false;
            }
        }

        if (!$authenticated && $storedHash !== '' && str_starts_with($storedHash, '$argon2id$')) {
            try {
                $authenticated = $security->authenticate($inputPassword, $storedHash);
            } catch (\Throwable $e) {
                $authenticated = false;
            }
        }

        if (!$authenticated && Hash::check($inputPassword, $storedHash)) {
            $authenticated = true;
        }

        if (!$authenticated) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng.']);
        }

        // Update password
        try {
            $user->password = $security->protect($request->password);
            $user->save();
        } catch (\Throwable $e) {
            return back()->with('error', 'Có lỗi xảy ra. Vui lòng thử lại.');
        }

        return back()->with('password_status', 'Đổi mật khẩu thành công!');
    }

    /**
     * Toggle 2FA - Step 1: Send verification code
     */
    public function toggle2FA(Request $request)
    {
        $user = Auth::user();

        // If turning OFF 2FA
        if ($user->two_factor_enabled) {
            $user->two_factor_enabled = false;
            $user->save();
            
            // Delete any existing codes
            TwoFactorCode::where('user_id', $user->id)->delete();

            return back()->with('2fa_status', 'Đã tắt xác thực 2 yếu tố.');
        }

        // If turning ON 2FA - send verification code first
        $throttleKey = '2fa-enable:' . $user->id;
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->with('error', 'Vui lòng thử lại sau ' . $seconds . ' giây.');
        }

        // Generate and send code
        $twoFactorCode = TwoFactorCode::generateFor($user);

        try {
            Mail::to($user->email)->send(new TwoFactorCodeMail($user, $twoFactorCode->code));
        } catch (\Throwable $e) {
            \Log::error('[2FA Enable] Failed to send email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Không thể gửi email xác thực. Vui lòng thử lại.');
        }

        RateLimiter::hit($throttleKey, 60);

        // Store in session that we're in the process of enabling 2FA
        session(['2fa:enabling' => true]);

        return back()->with('2fa_verify', 'Mã xác thực đã được gửi đến email của bạn. Vui lòng nhập mã để bật 2FA.');
    }

    /**
     * Verify code to enable 2FA
     */
    public function verify2FA(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ], [
            'code.required' => 'Vui lòng nhập mã xác thực.',
            'code.size' => 'Mã xác thực phải có 6 chữ số.',
        ]);

        $user = Auth::user();

        if (!session('2fa:enabling')) {
            return back()->with('error', 'Phiên đã hết hạn. Vui lòng thử lại.');
        }

        // Rate limit
        $throttleKey = '2fa-verify-enable:' . $user->id;
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors(['code' => 'Vui lòng thử lại sau ' . $seconds . ' giây.']);
        }

        // Verify code
        if (!TwoFactorCode::verify($user, $request->code)) {
            RateLimiter::hit($throttleKey, 60);
            return back()->withErrors(['code' => 'Mã xác thực không đúng hoặc đã hết hạn.']);
        }

        // Enable 2FA
        $user->two_factor_enabled = true;
        $user->save();

        RateLimiter::clear($throttleKey);
        session()->forget('2fa:enabling');

        return back()->with('2fa_status', 'Đã bật xác thực 2 yếu tố thành công! Từ giờ mỗi lần đăng nhập bạn sẽ cần nhập mã OTP từ email.');
    }

    /**
     * Resend 2FA enable code
     */
    public function resend2FACode(Request $request)
    {
        $user = Auth::user();

        if (!session('2fa:enabling')) {
            return back()->with('error', 'Phiên đã hết hạn. Vui lòng thử lại.');
        }

        $throttleKey = '2fa-resend-enable:' . $user->id;
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->with('error', 'Vui lòng thử lại sau ' . $seconds . ' giây.');
        }

        $twoFactorCode = TwoFactorCode::generateFor($user);

        try {
            Mail::to($user->email)->send(new TwoFactorCodeMail($user, $twoFactorCode->code));
        } catch (\Throwable $e) {
            return back()->with('error', 'Không thể gửi email. Vui lòng thử lại.');
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->with('2fa_verify', 'Mã xác thực mới đã được gửi.');
    }

    /**
     * Cancel 2FA enabling process
     */
    public function cancel2FA()
    {
        $user = Auth::user();
        
        TwoFactorCode::where('user_id', $user->id)->delete();
        session()->forget('2fa:enabling');

        return back();
    }
}
