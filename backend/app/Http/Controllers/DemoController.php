<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoController extends Controller
{
    /**
     * Demo landing page — role selection for visitors.
     * Only accessible when DEMO_MODE=true.
     */
    public function landing()
    {
        $this->ensureDemoMode();

        // If already authenticated, allow them to see the landing (for role switching)
        return view('demo.landing');
    }

    /**
     * Auto-login as the seeded demo candidate account.
     */
    public function enterAsCandidate(Request $request)
    {
        $this->ensureDemoMode();

        // Logout current session if any (for role switching)
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $user = User::where('email', 'demo-candidate@smartcv.demo')->first();

        if (!$user) {
            abort(503, 'Demo data not seeded. Run: php artisan db:seed --class=DemoSeeder');
        }

        Auth::login($user);
        $request->session()->regenerate();
        session(['2fa:verified' => true]); // Skip 2FA for demo

        return redirect()->route('home')
            ->with('status', '👋 Chào mừng bạn đến Demo! Hãy chọn một công việc và upload CV để trải nghiệm AI phân tích.');
    }

    /**
     * Auto-login as the seeded demo recruiter account.
     */
    public function enterAsRecruiter(Request $request)
    {
        $this->ensureDemoMode();

        // Logout current session if any (for role switching)
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $user = User::where('email', 'demo-recruiter@smartcv.demo')->first();

        if (!$user) {
            abort(503, 'Demo data not seeded. Run: php artisan db:seed --class=DemoSeeder');
        }

        Auth::login($user);
        $request->session()->regenerate();
        session(['2fa:verified' => true]); // Skip 2FA for demo

        return redirect()->route('admin.dashboard')
            ->with('status', '👋 Chào mừng Nhà tuyển dụng Demo! Xem AI Shortlist để đánh giá ứng viên.');
    }

    /**
     * Guard: abort 404 if demo mode is disabled.
     */
    private function ensureDemoMode(): void
    {
        if (!config('app.demo_mode')) {
            abort(404);
        }
    }
}
