<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Smart AI Recruitment System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }
        
        @keyframes gradient {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-float-delayed { animation: float 6s ease-in-out infinite; animation-delay: 2s; }
        
        .gradient-animate {
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #f5576c);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }
        
        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .input-focus:focus {
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
        }
    </style>
</head>
<body class="min-h-screen gradient-animate flex items-center justify-center p-4 relative overflow-hidden">
    <!-- Decorative Elements -->
    <div class="absolute top-20 left-10 w-72 h-72 bg-white/10 rounded-full blur-3xl animate-float"></div>
    <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-300/20 rounded-full blur-3xl animate-float-delayed"></div>
    
    <!-- Floating Icons -->
    <div class="absolute top-1/4 left-1/6 text-6xl animate-float opacity-20">💼</div>
    <div class="absolute bottom-1/4 right-1/6 text-5xl animate-float-delayed opacity-20">🎯</div>
    
    <div class="w-full max-w-md relative z-10">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="/" class="inline-flex items-center gap-3">
                <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center shadow-xl">
                    <span class="text-3xl">🤖</span>
                </div>
                <span class="text-3xl font-bold text-white drop-shadow-lg">Smart AI Recruitment System</span>
            </a>
        </div>
        
        <!-- Login Card -->
        <div class="glass rounded-3xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Chào mừng trở lại! 👋</h1>
                <p class="text-gray-500 mt-2">Đăng nhập để tiếp tục hành trình</p>
            </div>
            
            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <div class="flex items-center gap-2 text-red-700">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm font-medium">{{ $errors->first() }}</span>
                    </div>
                </div>
            @endif
            
            <form method="POST" action="{{ url('/login') }}" class="space-y-5">
                @csrf
                
                <!-- Email -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        <input 
                            type="email" 
                            name="email" 
                            value="{{ old('email') }}"
                            class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none input-focus transition-all"
                            placeholder="your@email.com"
                            required
                        >
                    </div>
                </div>
                
                <!-- Password -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Mật khẩu</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                        <input 
                            type="password" 
                            name="password" 
                            value="12345678"
                            class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none input-focus transition-all"
                            placeholder="••••••••"
                            required
                        >
                    </div>
                </div>
                
                <!-- Remember & Forgot -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-600">Ghi nhớ đăng nhập</span>
                    </label>
                    <a href="{{ route('password.forgot') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">Quên mật khẩu?</a>
                </div>
                
                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 transform hover:scale-[1.02] transition-all shadow-lg hover:shadow-xl"
                >
                    Đăng nhập
                </button>
            </form>
            
            <!-- Register Link -->
            <p class="text-center text-gray-600 mt-6">
                Chưa có tài khoản? 
                <a href="{{ url('/register') }}" class="text-indigo-600 font-semibold hover:text-indigo-700">Đăng ký ngay</a>
            </p>
        </div>
        
        <!-- Footer -->
        <p class="text-center text-white/70 text-sm mt-6">
            © 2024 Smart AI Recruitment System. Tất cả quyền được bảo lưu.
        </p>
    </div>
</body>
</html>
