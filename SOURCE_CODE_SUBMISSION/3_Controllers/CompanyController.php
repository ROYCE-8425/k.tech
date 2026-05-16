<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = Company::query()->orderByDesc('id');
        if ($user?->role === 'recruiter') {
            $query->where('user_id', $user->id);
        }

        $companies = $query->get();

        return view('admin.companies.index', compact('companies'));
    }

    public function create()
    {
        return view('admin.companies.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:5120'],
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('company-logos', 'public');
        }

        Company::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'website' => $validated['website'] ?? null,
            'address' => $validated['address'] ?? null,
            'description' => $validated['description'] ?? null,
            'logo_path' => $logoPath,
        ]);

        return redirect()->route('admin.companies.index')->with('status', 'Đã tạo công ty thành công.');
    }

    public function edit(Company $company)
    {
        $this->authorizeCompany($company);

        return view('admin.companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $this->authorizeCompany($company);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('logo')) {
            if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $company->logo_path = $request->file('logo')->store('company-logos', 'public');
        }

        $company->fill([
            'name' => $validated['name'],
            'website' => $validated['website'] ?? null,
            'address' => $validated['address'] ?? null,
            'description' => $validated['description'] ?? null,
        ])->save();

        return redirect()->route('admin.companies.index')->with('status', 'Đã cập nhật công ty.');
    }

    private function authorizeCompany(Company $company): void
    {
        $user = Auth::user();
        if ($user?->role === 'admin') {
            return;
        }

        if ($user?->role === 'recruiter' && (int) $company->user_id === (int) $user->id) {
            return;
        }

        abort(403);
    }
}
