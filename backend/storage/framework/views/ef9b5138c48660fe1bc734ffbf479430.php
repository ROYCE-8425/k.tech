<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e($title ?? 'Smart CV Matcher — AI Recruitment Demo'); ?></title>
    <meta name="description" content="AI-powered CV matching system for recruitment. Analyze CVs, match with job descriptions, and rank candidates automatically.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc',
                            400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca',
                            800: '#3730a3', 900: '#312e81',
                        }
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'fade-in': 'fadeIn 0.6s ease-out',
                    },
                    keyframes: {
                        float: { '0%, 100%': { transform: 'translateY(0px)' }, '50%': { transform: 'translateY(-20px)' } },
                        slideUp: { '0%': { opacity: '0', transform: 'translateY(30px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } }
                    }
                }
            }
        }
    </script>
    <style>
        .gradient-text {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -12px rgba(99, 102, 241, 0.15); }
        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 40px rgba(99, 102, 241, 0.4); }
        .shine { position: relative; overflow: hidden; }
        .shine::before {
            content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s; pointer-events: none;
        }
        .shine:hover::before { left: 100%; }
    </style>
</head>
<body class="font-sans antialiased bg-gradient-to-br from-slate-50 via-white to-indigo-50/30 min-h-screen">
    
    <?php if(config('app.demo_mode')): ?>
    <div class="fixed top-0 left-0 right-0 z-[60] bg-slate-900/95 backdrop-blur-xl border-b border-white/5 text-white" id="demo-banner">
        <div class="max-w-7xl mx-auto px-4 py-1.5 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-indigo-500/20 text-indigo-300 font-bold tracking-wide">
                    <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-indigo-400"></span></span>
                    DEMO
                </span>
                <?php if(auth()->guard()->check()): ?>
                    <span class="text-slate-400">
                        <?php echo e(Auth::user()->role === 'candidate' ? '👤 Ứng viên' : '🏢 Nhà tuyển dụng'); ?>:
                        <strong class="text-white/80"><?php echo e(Auth::user()->name); ?></strong>
                    </span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-1.5">
                <a href="<?php echo e(route('demo.landing')); ?>" class="px-2.5 py-1 rounded-md bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white font-medium transition-all text-xs">
                    🏠 Demo Home
                </a>
                <?php if(auth()->guard()->check()): ?>
                    <?php if(Auth::user()->role !== 'candidate'): ?>
                        <form action="<?php echo e(route('demo.enter-candidate')); ?>" method="POST" class="inline">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="px-2.5 py-1 rounded-md bg-white/5 hover:bg-indigo-500/20 text-slate-400 hover:text-indigo-300 font-medium transition-all text-xs">
                                → Ứng viên
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if(Auth::user()->role !== 'recruiter'): ?>
                        <form action="<?php echo e(route('demo.enter-recruiter')); ?>" method="POST" class="inline">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="px-2.5 py-1 rounded-md bg-white/5 hover:bg-purple-500/20 text-slate-400 hover:text-purple-300 font-medium transition-all text-xs">
                                → Tuyển dụng
                            </button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?php echo e(route('demo.landing')); ?>" class="px-2.5 py-1 rounded-md bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-300 font-medium transition-all text-xs">
                        Chọn vai trò
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    
    <nav class="fixed left-0 right-0 z-50 glass border-b border-white/20 shadow-sm <?php echo e(config('app.demo_mode') ? 'top-7' : 'top-0'); ?>">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                
                <a href="<?php echo e(route('home')); ?>" class="flex items-center space-x-2.5 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center shadow-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-lg font-extrabold gradient-text leading-tight">Smart CV Matcher</span>
                        <span class="text-[10px] text-gray-400 font-medium -mt-0.5">AI Recruitment Demo</span>
                    </div>
                </a>

                
                <div class="hidden md:flex items-center space-x-1">
                    <a href="<?php echo e(route('home')); ?>" class="px-4 py-2 rounded-lg text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 font-medium text-sm transition-all">
                        Việc làm
                    </a>

                    <?php if(auth()->guard()->check()): ?>
                        <?php if(Auth::user()->role === 'candidate'): ?>
                            <a href="<?php echo e(route('candidate.applications')); ?>" class="px-4 py-2 rounded-lg text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 font-medium text-sm transition-all">
                                Đơn đã nộp
                            </a>
                            <a href="<?php echo e(route('candidate.profile')); ?>" class="px-4 py-2 rounded-lg text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 font-medium text-sm transition-all">
                                Hồ sơ
                            </a>
                        <?php else: ?>
                            <a href="<?php echo e(route('admin.dashboard')); ?>" class="px-4 py-2 rounded-lg text-gray-600 hover:text-purple-600 hover:bg-purple-50 font-medium text-sm transition-all">
                                Dashboard
                            </a>
                            <a href="<?php echo e(route('admin.jobs.create')); ?>" class="px-4 py-2 rounded-lg text-gray-600 hover:text-purple-600 hover:bg-purple-50 font-medium text-sm transition-all">
                                + Đăng tuyển
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                
                <div class="flex items-center space-x-3">
                    <?php if(auth()->guard()->check()): ?>
                        
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 px-3 py-1.5 rounded-xl hover:bg-gray-50 transition-all">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-sm font-bold">
                                    <?php echo e(substr(Auth::user()->name, 0, 1)); ?>

                                </div>
                                <div class="hidden sm:block text-left">
                                    <p class="text-sm font-semibold text-gray-800 leading-tight"><?php echo e(Auth::user()->name); ?></p>
                                    <p class="text-[10px] text-gray-400">
                                        <?php if(Auth::user()->role === 'candidate'): ?> Ứng viên
                                        <?php else: ?> Nhà tuyển dụng
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>

                            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 py-1.5 bg-white rounded-xl shadow-xl border border-gray-100 text-sm">
                                <?php if(Auth::user()->role === 'candidate'): ?>
                                    <a href="<?php echo e(route('candidate.profile')); ?>" class="flex items-center px-4 py-2 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">Hồ sơ của tôi</a>
                                    <a href="<?php echo e(route('candidate.applications')); ?>" class="flex items-center px-4 py-2 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">Đơn đã nộp</a>
                                <?php else: ?>
                                    <a href="<?php echo e(route('admin.dashboard')); ?>" class="flex items-center px-4 py-2 text-gray-700 hover:bg-purple-50 hover:text-purple-600">Dashboard</a>
                                    <a href="<?php echo e(route('admin.jobs.create')); ?>" class="flex items-center px-4 py-2 text-gray-700 hover:bg-purple-50 hover:text-purple-600">Đăng tuyển mới</a>
                                <?php endif; ?>
                                <hr class="my-1.5 border-gray-100">
                                <a href="<?php echo e(route('account.settings')); ?>" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50">Cài đặt</a>
                                <form action="<?php echo e(route('logout')); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="flex items-center w-full px-4 py-2 text-red-600 hover:bg-red-50">Đăng xuất</button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if(config('app.demo_mode')): ?>
                            <a href="<?php echo e(route('demo.landing')); ?>" class="inline-flex items-center px-5 py-2.5 rounded-xl font-bold text-white shadow-lg hover:shadow-xl transition-all btn-primary shine text-sm">
                                🧪 Trải nghiệm Demo
                            </a>
                        <?php else: ?>
                            <a href="<?php echo e(route('login')); ?>" class="px-4 py-2 rounded-xl text-gray-700 hover:text-indigo-600 hover:bg-indigo-50 font-semibold transition-all text-sm">Đăng nhập</a>
                            <a href="<?php echo e(route('register')); ?>" class="inline-flex items-center px-5 py-2.5 rounded-xl font-bold text-white shadow-lg hover:shadow-xl transition-all btn-primary shine text-sm">Đăng ký</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    
    <main class="<?php echo e(config('app.demo_mode') ? 'pt-28' : 'pt-24'); ?> pb-12 min-h-screen">
        
        <?php if(session('status')): ?>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6 animate-slide-up">
                <div class="flex items-center p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    <p class="font-medium text-sm"><?php echo e(session('status')); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if(session('error')): ?>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6 animate-slide-up">
                <div class="flex items-center p-4 rounded-xl bg-red-50 border border-red-200 text-red-800">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                    <p class="font-medium text-sm"><?php echo e(session('error')); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <?php echo e($slot); ?>

        </div>
    </main>

    
    <footer class="bg-slate-900 text-white">
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
                <p class="text-slate-500 text-xs">© <?php echo e(date('Y')); ?> · Laravel + FastAPI + Multi-Agent AI</p>
            </div>
        </div>
    </footer>
</body>
</html>
<?php /**PATH D:\web\cpanel_public_html\backend\resources\views/components/layouts/app.blade.php ENDPATH**/ ?>