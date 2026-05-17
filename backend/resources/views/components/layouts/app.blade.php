<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Smart CV Matcher — AI Recruitment Demo' }}</title>
    <meta name="description" content="AI-powered CV matching system for recruitment. Analyze CVs, match with job descriptions, and rank candidates automatically.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@200;300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    <style>
        .glass-panel { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.5); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); }
        .glass-card { background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border: 1px solid rgba(255, 255, 255, 0.4); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); transition: all 0.3s ease; }
        .glass-card:hover { background: rgba(255, 255, 255, 0.8); transform: translateY(-2px); }
        .glass-header { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border-bottom: 1px solid rgba(255, 255, 255, 0.6); }
        
        ::selection { background-color: rgba(99, 102, 241, 0.2); color: inherit; }
        ::-moz-selection { background-color: rgba(99, 102, 241, 0.2); color: inherit; }

        /* Force Premium Typography System-Wide */
        body { font-family: 'Plus Jakarta Sans', sans-serif; }

        .btn-primary {
            background: linear-gradient(135deg, #1856FF 0%, #3A344E 100%);
            transition: all 0.3s ease;
        }

        /* Bulletproof Gradients for UI Elements to avoid Tailwind CDN vs Vite Variable Clashes */
        .bg-gradient-brand { background: linear-gradient(135deg, #1856FF 0%, #3A344E 100%); }
        .bg-gradient-emerald { background: linear-gradient(135deg, #34d399 0%, #0d9488 100%); color: white; }
        .bg-gradient-indigo { background: linear-gradient(135deg, #818cf8 0%, #c084fc 100%); color: white; }
        .bg-gradient-amber { background: linear-gradient(135deg, #fbbf24 0%, #ea580c 100%); color: white; }
        .bg-gradient-red { background: linear-gradient(135deg, #fb7185 0%, #e11d48 100%); color: white; }
        .bg-gradient-gray { background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%); color: white; }
        .text-gradient-primary { background: linear-gradient(to right, #4338ca, #9333ea); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 40px rgba(24, 86, 255, 0.4); }
        .shine { position: relative; overflow: hidden; }
        .shine::before {
            content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s; pointer-events: none;
        }
        .shine:hover::before { left: 100%; }
        
        .gradient-text {
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-image: linear-gradient(to right, #4f46e5, #9333ea);
        }
    </style>
</head>
<body class="font-sans antialiased text-[#141414] bg-gradient-to-br from-[#f8fafc] via-[#eef2ff] to-[#e0e7ff] min-h-screen">
    {{-- ═══════════════════ DEMO BANNER ═══════════════════ --}}
    @if(config('app.demo_mode'))
    <div class="fixed top-0 left-0 right-0 z-[60] bg-slate-900/95 backdrop-blur-md border-b border-white/10 shadow-lg text-white" id="demo-banner">
        <div class="max-w-7xl mx-auto px-4 py-2 flex items-center justify-between text-sm">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-indigo-500/20 text-indigo-300 font-bold tracking-wide text-xs border border-indigo-500/30">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-400"></span>
                    </span>
                    DEMO MODE
                </span>
                @auth
                    <span class="text-slate-300 flex items-center gap-3">
                        <span class="w-px h-4 bg-white/20 hidden sm:block"></span>
                        <span class="font-medium hidden sm:inline-block">
                            @if(Auth::user()->role === 'candidate')
                                <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg> Ứng viên
                            @else
                                <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg> Nhà tuyển dụng
                            @endif:
                            <strong class="text-white ml-1">{{ Auth::user()->name }}</strong>
                        </span>
                    </span>
                @endauth
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('demo.landing') }}" class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white font-medium transition-all border border-transparent hover:border-white/10 flex items-center gap-1.5 text-xs sm:text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    <span class="hidden sm:inline">Trang Demo</span>
                </a>
                @auth
                    @if(Auth::user()->role !== 'candidate')
                        <form action="{{ route('demo.enter-candidate') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-300 hover:text-indigo-200 font-medium transition-all border border-indigo-500/20 hover:border-indigo-500/40 flex items-center gap-1.5 text-xs sm:text-sm">
                                <span class="hidden sm:inline">Sang</span> Ứng viên 
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </button>
                        </form>
                    @endif
                    @if(Auth::user()->role !== 'recruiter')
                        <form action="{{ route('demo.enter-recruiter') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-purple-500/10 hover:bg-purple-500/20 text-purple-300 hover:text-purple-200 font-medium transition-all border border-purple-500/20 hover:border-purple-500/40 flex items-center gap-1.5 text-xs sm:text-sm">
                                <span class="hidden sm:inline">Sang</span> Tuyển dụng
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </button>
                        </form>
                    @endif
                @else
                    <a href="{{ route('demo.landing') }}" class="px-3 py-1.5 rounded-lg bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-300 font-medium transition-all text-xs sm:text-sm border border-indigo-500/30">
                        Chọn vai trò
                    </a>
                @endauth
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════ NAVIGATION ═══════════════════ --}}
    <nav class="fixed left-0 right-0 z-50 glass-header {{ config('app.demo_mode') ? 'top-[49px]' : 'top-0' }}">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center space-x-2.5 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-brand flex items-center justify-center shadow-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-lg font-extrabold text-[#1856FF] leading-tight">Smart CV Matcher</span>
                        <span class="text-[10px] text-gray-400 font-medium -mt-0.5">AI Recruitment Demo</span>
                    </div>
                </a>

                {{-- Nav links (role-aware) --}}
                <div class="hidden md:flex items-center space-x-1">
                    <a href="{{ route('home') }}" class="px-4 py-2 rounded-lg text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 font-medium text-sm transition-all">
                        {{ __('Việc làm') }}
                    </a>

                    @auth
                        @if(Auth::user()->role === 'candidate')
                            <a href="{{ route('candidate.applications') }}" class="px-4 py-2 rounded-lg text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 font-medium text-sm transition-all">
                                {{ __('Đơn đã nộp') }}
                            </a>
                        @else
                            <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-lg text-gray-600 hover:text-purple-600 hover:bg-purple-50 font-medium text-sm transition-all">
                                {{ __('Dashboard') }}
                            </a>
                            <a href="{{ route('admin.jobs.create') }}" class="px-4 py-2 rounded-lg text-gray-600 hover:text-purple-600 hover:bg-purple-50 font-medium text-sm transition-all">
                                + {{ __('Đăng tuyển') }}
                            </a>
                        @endif
                    @endauth
                </div>

                {{-- Right side --}}
                <div class="flex items-center space-x-3">
                    {{-- Language Switcher --}}
                    <div class="relative" x-data="{ langOpen: false }">
                        <button @click="langOpen = !langOpen" class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gray-50 border border-gray-200 shadow-sm hover:border-indigo-300 hover:bg-white hover:text-indigo-700 hover:shadow transition-all font-bold text-sm text-gray-800">
                            @php $currentLocale = session('locale', 'en'); @endphp
                            @if($currentLocale === 'vi') <span class="text-base">🇻🇳</span> <span>VI</span>
                            @elseif($currentLocale === 'ko') <span class="text-base">🇰🇷</span> <span>KO</span>
                            @else <span class="text-base">🇺🇸</span> <span>EN</span> @endif
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="langOpen" @click.away="langOpen = false" x-transition 
                             class="absolute right-0 mt-2 w-32 py-1.5 bg-white rounded-xl shadow-xl border border-gray-100 text-sm z-50">
                            <a href="{{ route('lang.switch', 'en') }}" class="block px-4 py-2 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">🇺🇸 English</a>
                            <a href="{{ route('lang.switch', 'vi') }}" class="block px-4 py-2 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">🇻🇳 Tiếng Việt</a>
                            <a href="{{ route('lang.switch', 'ko') }}" class="block px-4 py-2 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">🇰🇷 한국어</a>
                        </div>
                    </div>
                    @auth
                        {{-- User dropdown --}}
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 px-3 py-1.5 rounded-xl hover:bg-gray-50 transition-all">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-sm font-bold" style="background: linear-gradient(to bottom right, #6366f1, #a855f7);">
                                    {{ substr(Auth::user()->name, 0, 1) }}
                                </div>
                                <div class="hidden sm:block text-left">
                                    <p class="text-sm font-semibold text-gray-800 leading-tight">{{ Auth::user()->name }}</p>
                                    <p class="text-[10px] text-gray-400">
                                        @if(Auth::user()->role === 'candidate') {{ __('Ứng viên') }}
                                        @else {{ __('Nhà tuyển dụng') }}
                                        @endif
                                    </p>
                                </div>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>

                            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 py-1.5 bg-white rounded-xl shadow-xl border border-gray-100 text-sm">
                                @if(Auth::user()->role === 'candidate')
                                    <a href="{{ route('candidate.applications') }}" class="flex items-center px-4 py-2 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">{{ __('Đơn đã nộp') }}</a>
                                @else
                                    <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-2 text-gray-700 hover:bg-purple-50 hover:text-purple-600">{{ __('Dashboard') }}</a>
                                    <a href="{{ route('admin.jobs.create') }}" class="flex items-center px-4 py-2 text-gray-700 hover:bg-purple-50 hover:text-purple-600">{{ __('Đăng tuyển') }}</a>
                                @endif
                                <hr class="my-1.5 border-gray-100">
                                <a href="{{ route('account.settings') }}" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50">{{ __('Cài đặt') }}</a>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="flex items-center w-full px-4 py-2 text-red-600 hover:bg-red-50">{{ __('Đăng xuất') }}</button>
                                </form>
                            </div>
                        </div>
                    @else
                        @if(config('app.demo_mode'))
                            <a href="{{ route('demo.landing') }}" class="inline-flex items-center px-5 py-2.5 rounded-xl font-bold text-white shadow-lg hover:shadow-xl transition-all btn-primary shine text-sm">
                                🧪 Trải nghiệm Demo
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl text-gray-700 hover:text-indigo-600 hover:bg-indigo-50 font-semibold transition-all text-sm">{{ __('Đăng nhập') }}</a>
                            <a href="{{ route('register') }}" class="inline-flex items-center px-5 py-2.5 rounded-xl font-bold text-white shadow-lg hover:shadow-xl transition-all btn-primary shine text-sm">{{ __('Đăng ký') }}</a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- ═══════════════════ MAIN CONTENT ═══════════════════ --}}
    <main class="{{ config('app.demo_mode') ? 'pt-28' : 'pt-24' }} pb-12 min-h-screen">
        {{-- Flash Messages --}}
        @if(session('status'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6 animate-slide-up">
                <div class="flex items-center p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    <p class="font-medium text-sm">{{ session('status') }}</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6 animate-slide-up">
                <div class="flex items-center p-4 rounded-xl bg-red-50 border border-red-200 text-red-800">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                    <p class="font-medium text-sm">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{ $slot }}
        </div>
    </main>

    {{-- ═══════════════════ FOOTER ═══════════════════ --}}
    <footer class="bg-[#3A344E] text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <span class="font-bold text-sm">Smart CV Matcher</span>
                    <span class="text-slate-500 text-xs">· AI Recruitment Demo</span>
                </div>
                <p class="text-slate-500 text-xs">© {{ date('Y') }} · Laravel + FastAPI + Multi-Agent AI</p>
            </div>
        </div>
    </footer>
</body>
</html>
