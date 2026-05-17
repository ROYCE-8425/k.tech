<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Smart AI Recruitment System - Tuyển dụng thông minh' }}</title>
    <script src="https://cdn.tailwindcss.com?v=3.4.1"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Fallback nếu CDN fail -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const testEl = document.createElement('div');
            testEl.className = 'hidden';
            document.body.appendChild(testEl);
            const computed = window.getComputedStyle(testEl);
            if (computed.display !== 'none') {
                console.error('Tailwind CDN failed to load!');
                alert('⚠️ CSS không load được. Vui lòng:\n1. Kiểm tra kết nối internet\n2. Tắt ad blocker\n3. Hard refresh (Ctrl+Shift+R)');
            }
            document.body.removeChild(testEl);
        });
    </script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-50" x-data="{ mobileOpen: false, adminOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center gap-8">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center transform group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <span class="text-xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Smart AI Recruitment System</span>
                    </a>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center gap-1">
                        <a href="{{ route('home') }}" class="px-4 py-2 rounded-lg font-medium transition-colors {{ request()->is('/') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                {{ __('Việc làm') }}
                            </div>
                        </a>

                        @auth
                            @if(Auth::user()->role === 'admin' || Auth::user()->role === 'recruiter')
                                <!-- Admin Dropdown -->
                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open" class="px-4 py-2 rounded-lg font-medium transition-colors flex items-center gap-2 {{ request()->is('admin*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                        {{ __('Quản lý') }}
                                        <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    
                                    <div x-show="open" 
                                         @click.away="open = false"
                                         x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0 scale-95"
                                         x-transition:enter-end="opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-150"
                                         x-transition:leave-start="opacity-100 scale-100"
                                         x-transition:leave-end="opacity-0 scale-95"
                                         class="absolute left-0 mt-2 w-56 rounded-2xl bg-white shadow-2xl border border-gray-100 py-2 z-50"
                                         x-cloak>
                                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors {{ request()->is('admin/dashboard') ? 'bg-indigo-50 text-indigo-700 font-semibold' : '' }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                            </svg>
                                            {{ __('Dashboard') }}
                                        </a>
                                        <a href="{{ route('admin.companies.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors {{ request()->is('admin/companies*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : '' }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                            {{ __('Công ty') }}
                                        </a>
                                        <a href="{{ route('admin.jobs.create') }}" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            {{ __('Đăng tin tuyển dụng') }}
                                        </a>
                                        <a href="{{ route('admin.interviews') }}" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors {{ request()->is('admin/interviews*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : '' }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            {{ __('Lịch phỏng vấn') }}
                                        </a>
                                    </div>
                                </div>
                            @else
                                <!-- Candidate Links -->
                                <a href="{{ route('candidate.dashboard') }}" class="px-4 py-2 rounded-lg font-medium transition-colors {{ request()->is('candidate/dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                        </svg>
                                        Dashboard
                                    </div>
                                </a>
                                <a href="{{ route('candidate.applications') }}" class="px-4 py-2 rounded-lg font-medium transition-colors {{ request()->is('candidate/applications') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Đơn ứng tuyển
                                    </div>
                                </a>

                            @endif
                        @endauth
                    </div>
                </div>

                <!-- Right Side -->
                <div class="hidden md:flex items-center gap-3">
                    <!-- Language Switcher -->
                    <div class="relative" x-data="{ langOpen: false }">
                        <button @click="langOpen = !langOpen" class="flex items-center gap-2 px-3 py-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium transition-colors">
                            @php $currentLocale = session('locale', 'en'); @endphp
                            @if($currentLocale === 'vi') 🇻🇳 VI
                            @elseif($currentLocale === 'ko') 🇰🇷 KO
                            @else 🇺🇸 EN @endif
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="langOpen" @click.away="langOpen = false"
                             class="absolute right-0 mt-2 w-32 rounded-xl bg-white shadow-lg border border-gray-100 py-1 z-50" x-cloak>
                            <a href="{{ route('lang.switch', 'en') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700">🇺🇸 English</a>
                            <a href="{{ route('lang.switch', 'vi') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700">🇻🇳 Tiếng Việt</a>
                            <a href="{{ route('lang.switch', 'ko') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700">🇰🇷 한국어</a>
                        </div>
                    </div>

                    @auth
                        <!-- User Menu -->
                        <div class="flex items-center gap-3 px-3 py-2 rounded-lg bg-gray-50">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-sm font-bold">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <div class="text-sm">
                                <div class="font-semibold text-gray-900">{{ Auth::user()->name }}</div>
                                <div class="text-gray-500 text-xs">
                                    @if(Auth::user()->role === 'admin')
                                        Administrator
                                    @elseif(Auth::user()->role === 'recruiter')
                                        Nhà tuyển dụng
                                    @else
                                        Ứng viên
                                    @endif
                                </div>
                            </div>
                        </div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="px-4 py-2 rounded-lg text-gray-600 hover:text-red-600 hover:bg-red-50 font-medium transition-colors flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                {{ __('Đăng xuất') }}
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                            {{ __('Đăng nhập') }}
                        </a>
                        <a href="{{ route('register') }}" class="px-5 py-2 rounded-lg bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-medium hover:shadow-lg transition-all">
                            {{ __('Đăng ký ngay') }}
                        </a>
                    @endauth
                </div>

                <!-- Mobile menu button -->
                <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100">
                    <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <svg x-show="mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Mobile Navigation -->
            <div x-show="mobileOpen" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="md:hidden py-4 border-t border-gray-100"
                 x-cloak>
                <div class="space-y-1">
                    <a href="{{ route('home') }}" class="block px-4 py-3 rounded-lg font-medium {{ request()->is('/') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                        Việc làm
                    </a>
                    
                    @auth
                        @if(Auth::user()->role === 'admin' || Auth::user()->role === 'recruiter')
                            <a href="{{ route('admin.dashboard') }}" class="block px-4 py-3 rounded-lg font-medium {{ request()->is('admin/dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                                Dashboard
                            </a>
                            <a href="{{ route('admin.companies.index') }}" class="block px-4 py-3 rounded-lg font-medium {{ request()->is('admin/companies*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                                Công ty
                            </a>
                            <a href="{{ route('admin.jobs.create') }}" class="block px-4 py-3 rounded-lg font-medium text-gray-700 hover:bg-gray-50">
                                Đăng tin tuyển dụng
                            </a>
                            <a href="{{ route('admin.interviews') }}" class="block px-4 py-3 rounded-lg font-medium {{ request()->is('admin/interviews*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                                Lịch phỏng vấn
                            </a>
                        @else
                            <a href="{{ route('candidate.dashboard') }}" class="block px-4 py-3 rounded-lg font-medium {{ request()->is('candidate/dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                                Dashboard
                            </a>
                            <a href="{{ route('candidate.applications') }}" class="block px-4 py-3 rounded-lg font-medium {{ request()->is('candidate/applications') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                                Đơn ứng tuyển
                            </a>

                        @endif
                        
                        <!-- Mobile Language Switcher -->
                        <div class="px-4 py-3 mt-2 border-t border-gray-100">
                            <p class="text-xs font-semibold text-gray-500 mb-2 uppercase">{{ __('Ngôn ngữ') }}</p>
                            <div class="flex gap-2">
                                <a href="{{ route('lang.switch', 'en') }}" class="flex-1 py-2 text-center rounded-lg border {{ session('locale', 'en') === 'en' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 font-bold' : 'border-gray-200 text-gray-600' }}">🇺🇸 EN</a>
                                <a href="{{ route('lang.switch', 'vi') }}" class="flex-1 py-2 text-center rounded-lg border {{ session('locale', 'en') === 'vi' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 font-bold' : 'border-gray-200 text-gray-600' }}">🇻🇳 VI</a>
                                <a href="{{ route('lang.switch', 'ko') }}" class="flex-1 py-2 text-center rounded-lg border {{ session('locale', 'en') === 'ko' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 font-bold' : 'border-gray-200 text-gray-600' }}">🇰🇷 KO</a>
                            </div>
                        </div>

                        <div class="px-4 py-3 bg-gray-50 rounded-lg mt-2">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold">
                                    {{ substr(Auth::user()->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900">{{ Auth::user()->name }}</div>
                                    <div class="text-gray-500 text-sm">{{ Auth::user()->email }}</div>
                                </div>
                            </div>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 font-medium transition-colors">
                                    Đăng xuất
                                </button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="block px-4 py-3 rounded-lg font-medium text-gray-700 hover:bg-gray-50">
                            Đăng nhập
                        </a>
                        <a href="{{ route('register') }}" class="block px-4 py-3 rounded-lg font-medium bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-center">
                            Đăng ký ngay
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Demo role-switching banner — only visible when DEMO_MODE=true and authenticated --}}
    @if(config('app.demo_mode') && Auth::check())
        <div class="bg-amber-50 border-b border-amber-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-400 text-amber-900">DEMO</span>
                    <span class="text-sm text-amber-800">
                        Đang vào vai:
                        <strong>
                            @if(Auth::user()->role === 'recruiter' || Auth::user()->role === 'admin')
                                <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg> Nhà tuyển dụng
                            @else
                                <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg> Ứng viên
                            @endif
                        </strong>
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    @if(Auth::user()->role === 'recruiter' || Auth::user()->role === 'admin')
                        <form action="{{ route('demo.enter-candidate') }}" method="POST">
                            @csrf
                            <button type="submit" class="text-xs px-3 py-1.5 rounded-lg bg-white border border-amber-300 text-amber-800 hover:bg-amber-100 font-medium transition-colors">
                                <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg> Chuyển sang Ứng viên
                            </button>
                        </form>
                    @else
                        <form action="{{ route('demo.enter-recruiter') }}" method="POST">
                            @csrf
                            <button type="submit" class="text-xs px-3 py-1.5 rounded-lg bg-white border border-amber-300 text-amber-800 hover:bg-amber-100 font-medium transition-colors">
                                <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg> Chuyển sang NTD
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('demo.landing') }}" class="text-xs px-3 py-1.5 rounded-lg text-amber-600 hover:text-amber-800 hover:underline transition-colors">
                        ← Về Demo Landing
                    </a>
                </div>
            </div>
        </div>
    @endif

    <main class="max-w-7xl mx-auto p-6">
        @if(session('status'))
            <div class="mb-4 p-4 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 rounded-xl bg-red-50 text-red-700 border border-red-200">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-4 rounded-xl bg-amber-50 text-amber-800 border border-amber-200">
                <div class="font-semibold mb-1">Vui lòng kiểm tra lại:</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
        @isset($slot)
            {{ $slot }}
        @endisset
    </main>
</body>
</html>
