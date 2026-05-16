<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRecruiterHasCompany
{
    /**
     * Redirect recruiters without a company to company onboarding.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'recruiter') {
            return $next($request);
        }

        if ($request->routeIs('admin.companies.*')) {
            return $next($request);
        }

        $hasCompany = Company::where('user_id', $user->id)->exists();
        if (!$hasCompany) {
            return redirect()->route('admin.companies.create')
                ->with('status', 'Hãy tạo thông tin công ty trước khi tiếp tục.');
        }

        return $next($request);
    }
}
