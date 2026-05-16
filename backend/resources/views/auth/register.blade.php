<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Smart AI Recruitment System</title>
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
    <div class="absolute top-1/4 left-1/6 text-6xl animate-float opacity-20">✨</div>
    <div class="absolute bottom-1/4 right-1/6 text-5xl animate-float-delayed opacity-20">🚀</div>
    
    <div class="w-full max-w-lg relative z-10">
        <!-- Logo -->
        <div class="text-center mb-6">
            <a href="/" class="inline-flex items-center gap-3">
                <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center shadow-xl">
                    <span class="text-3xl">🤖</span>
                </div>
                <span class="text-3xl font-bold text-white drop-shadow-lg">Smart AI Recruitment System</span>
            </a>
        </div>
        
        <!-- Register Card -->
        <div class="glass rounded-3xl shadow-2xl p-8">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Tạo tài khoản mới 🎉</h1>
                <p class="text-gray-500 mt-2">Bắt đầu hành trình tìm việc hoặc tuyển dụng</p>
            </div>
            
            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <ul class="space-y-1">
                        @foreach($errors->all() as $error)
                            <li class="flex items-center gap-2 text-red-700 text-sm">
                                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <form method="POST" action="{{ url('/register') }}" class="space-y-5" id="registerForm">
                @csrf

                <!-- Role Selection -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Bạn muốn</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @php
                            $selectedRole = old('role', 'candidate');
                        @endphp

                        <label class="relative cursor-pointer">
                            <input type="radio" name="role" value="candidate" class="peer sr-only" {{ $selectedRole === 'candidate' ? 'checked' : '' }} required>
                            <div class="p-4 rounded-2xl border-2 border-gray-200 bg-white transition-all peer-checked:border-indigo-500 peer-checked:ring-4 peer-checked:ring-indigo-100">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-xl">👤</div>
                                    <div>
                                        <div class="font-semibold text-gray-800">Ứng tuyển</div>
                                        <div class="text-sm text-gray-500 mt-0.5">Tạo hồ sơ và ứng tuyển việc làm</div>
                                    </div>
                                </div>
                            </div>
                        </label>

                        <label class="relative cursor-pointer">
                            <input type="radio" name="role" value="recruiter" class="peer sr-only" {{ $selectedRole === 'recruiter' ? 'checked' : '' }} required>
                            <div class="p-4 rounded-2xl border-2 border-gray-200 bg-white transition-all peer-checked:border-indigo-500 peer-checked:ring-4 peer-checked:ring-indigo-100">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-xl">🏢</div>
                                    <div>
                                        <div class="font-semibold text-gray-800">Tuyển dụng</div>
                                        <div class="text-sm text-gray-500 mt-0.5">Tạo công ty và đăng tin tuyển dụng</div>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Họ và tên</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </span>
                        <input 
                            type="text" 
                            name="name" 
                            value="{{ old('name') }}"
                            class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none input-focus transition-all"
                            placeholder="Nguyễn Văn A"
                            required
                        >
                    </div>
                </div>
                
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
                
                <!-- Phone -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Số điện thoại <span class="text-gray-400 font-normal">(tùy chọn)</span></label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </span>
                        <input 
                            type="tel" 
                            name="phone" 
                            value="{{ old('phone') }}"
                            class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none input-focus transition-all"
                            placeholder="0901234567"
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
                            class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none input-focus transition-all"
                            placeholder="Ít nhất 8 ký tự"
                            required
                        >
                    </div>
                </div>
                
                <!-- Confirm Password -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Xác nhận mật khẩu</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </span>
                        <input 
                            type="password" 
                            name="password_confirmation" 
                            class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none input-focus transition-all"
                            placeholder="Nhập lại mật khẩu"
                            required
                        >
                    </div>
                </div>
                
                <!-- Terms -->
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="terms" required class="w-5 h-5 mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-600">
                        Tôi đồng ý với <a href="#" class="text-indigo-600 hover:underline">Điều khoản sử dụng</a> 
                        và <a href="#" class="text-indigo-600 hover:underline">Chính sách bảo mật</a>
                    </span>
                </label>
                
                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 transform hover:scale-[1.02] transition-all shadow-lg hover:shadow-xl"
                >
                    Đăng ký
                </button>
            </form>
            
            <!-- Login Link -->
            <p class="text-center text-gray-600 mt-6">
                Đã có tài khoản? 
                <a href="{{ url('/login') }}" class="text-indigo-600 font-semibold hover:text-indigo-700">Đăng nhập ngay</a>
            </p>
        </div>
        
        <!-- Footer -->
        <p class="text-center text-white/70 text-sm mt-6">
            © 2024 Smart AI Recruitment System. Tất cả quyền được bảo lưu.
        </p>
    </div>
    
</body>
</html>
