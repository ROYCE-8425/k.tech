<x-layouts.app title="Cài đặt tài khoản - IT Solo Leveling">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold gradient-text">Cài đặt tài khoản</h1>
            <p class="text-gray-600 mt-2">Quản lý thông tin và bảo mật tài khoản của bạn</p>
        </div>

        <div class="space-y-6">
            <!-- Basic Info Card -->
            <div class="bg-white/80 backdrop-blur-xl rounded-2xl shadow-xl shadow-indigo-500/5 border border-white/20 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-indigo-500 to-purple-600" style="background: linear-gradient(to right, #6366f1, #9333ea);">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Thông tin cá nhân
                    </h2>
                </div>
                <div class="p-6">
                    @if(session('status'))
                        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-emerald-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-emerald-700 font-medium">{{ session('status') }}</p>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('account.update-profile') }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Họ và tên</label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" 
                                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none transition-all @error('name') border-red-400 @enderror">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                                <input type="email" value="{{ $user->email }}" disabled 
                                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 bg-gray-50 text-gray-500 cursor-not-allowed">
                                <p class="mt-1 text-xs text-gray-500">Email không thể thay đổi</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Số điện thoại</label>
                                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" 
                                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none transition-all"
                                    placeholder="0912 345 678">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Vai trò</label>
                                <input type="text" value="{{ $user->role === 'admin' ? 'Quản trị viên' : ($user->role === 'recruiter' ? 'Nhà tuyển dụng' : 'Ứng viên') }}" disabled 
                                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 bg-gray-50 text-gray-500 cursor-not-allowed">
                            </div>
                        </div>

                        <div class="pt-4">
                            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:shadow-lg hover:shadow-indigo-500/30 transition-all" style="background: linear-gradient(to right, #4f46e5, #9333ea);">
                                Lưu thay đổi
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Password Card -->
            <div class="bg-white/80 backdrop-blur-xl rounded-2xl shadow-xl shadow-indigo-500/5 border border-white/20 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-orange-500 to-red-600" style="background: linear-gradient(to right, #f97316, #dc2626);">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                        Đổi mật khẩu
                    </h2>
                </div>
                <div class="p-6">
                    @if(session('password_status'))
                        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-emerald-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-emerald-700 font-medium">{{ session('password_status') }}</p>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('account.change-password') }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Mật khẩu hiện tại</label>
                            <input type="password" name="current_password" 
                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-orange-500 focus:outline-none transition-all @error('current_password') border-red-400 @enderror"
                                placeholder="••••••••">
                            @error('current_password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Mật khẩu mới</label>
                                <input type="password" name="password" 
                                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-orange-500 focus:outline-none transition-all @error('password') border-red-400 @enderror"
                                    placeholder="••••••••">
                                @error('password')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Xác nhận mật khẩu mới</label>
                                <input type="password" name="password_confirmation" 
                                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-orange-500 focus:outline-none transition-all"
                                    placeholder="••••••••">
                            </div>
                        </div>

                        <div class="pt-4">
                            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-orange-500 to-red-600 text-white font-semibold rounded-xl hover:shadow-lg hover:shadow-orange-500/30 transition-all" style="background: linear-gradient(to right, #f97316, #dc2626);">
                                Đổi mật khẩu
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 2FA Card -->
            <div class="bg-white/80 backdrop-blur-xl rounded-2xl shadow-xl shadow-indigo-500/5 border border-white/20 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-emerald-500 to-teal-600" style="background: linear-gradient(to right, #10b981, #0d9488);">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        Xác thực 2 yếu tố (2FA)
                    </h2>
                </div>
                <div class="p-6">
                    @if(session('2fa_status'))
                        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-emerald-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-emerald-700 font-medium">{{ session('2fa_status') }}</p>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-red-700 font-medium">{{ session('error') }}</p>
                            </div>
                        </div>
                    @endif

                    <!-- Current Status -->
                    <div class="flex items-center justify-between p-4 rounded-xl {{ $user->two_factor_enabled ? 'bg-emerald-50 border border-emerald-200' : 'bg-gray-50 border border-gray-200' }}">
                        <div class="flex items-center">
                            @if($user->two_factor_enabled)
                                <div class="w-12 h-12 rounded-xl bg-emerald-500 flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-bold text-emerald-800">Đã bật</p>
                                    <p class="text-sm text-emerald-600">Tài khoản được bảo vệ bằng xác thực 2 yếu tố</p>
                                </div>
                            @else
                                <div class="w-12 h-12 rounded-xl bg-gray-400 flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-800">Chưa bật</p>
                                    <p class="text-sm text-gray-600">Bật 2FA để tăng cường bảo mật tài khoản</p>
                                </div>
                            @endif
                        </div>

                        @if(!session('2fa:enabling'))
                            <form method="POST" action="{{ route('account.toggle-2fa') }}">
                                @csrf
                                @if($user->two_factor_enabled)
                                    <button type="submit" class="px-5 py-2.5 bg-red-100 text-red-700 font-semibold rounded-xl hover:bg-red-200 transition-all" onclick="return confirm('Bạn có chắc muốn tắt xác thực 2 yếu tố?')">
                                        Tắt 2FA
                                    </button>
                                @else
                                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 transition-all">
                                        Bật 2FA
                                    </button>
                                @endif
                            </form>
                        @endif
                    </div>

                    <!-- 2FA Verification Form (when enabling) -->
                    @if(session('2fa:enabling') || session('2fa_verify'))
                        <div class="mt-6 p-6 rounded-xl bg-blue-50 border border-blue-200">
                            <div class="flex items-start mb-4">
                                <svg class="w-6 h-6 text-blue-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <div>
                                    <p class="font-bold text-blue-800">Xác thực email</p>
                                    <p class="text-sm text-blue-600">{{ session('2fa_verify') ?? 'Nhập mã 6 số đã gửi đến email của bạn để bật 2FA.' }}</p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('account.verify-2fa') }}" class="space-y-4">
                                @csrf
                                <div>
                                    <input type="text" name="code" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code"
                                        class="w-full px-6 py-4 text-center text-2xl font-bold tracking-[0.5em] rounded-xl border-2 border-blue-300 focus:border-blue-500 focus:outline-none @error('code') border-red-400 @enderror"
                                        placeholder="000000" autofocus>
                                    @error('code')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="flex items-center justify-between">
                                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all">
                                        Xác nhận bật 2FA
                                    </button>

                                    <div class="flex items-center space-x-3">
                                        <form method="POST" action="{{ route('account.resend-2fa') }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                                Gửi lại mã
                                            </button>
                                        </form>
                                        <a href="{{ route('account.cancel-2fa') }}" class="text-gray-500 hover:text-gray-700 font-medium text-sm">
                                            Hủy
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endif

                    <!-- 2FA Info -->
                    <div class="mt-6 p-4 rounded-xl bg-amber-50 border border-amber-200">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-amber-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="text-sm text-amber-800">
                                <p class="font-semibold mb-1">Xác thực 2 yếu tố hoạt động như thế nào?</p>
                                <ul class="list-disc list-inside space-y-1 text-amber-700">
                                    <li>Sau khi đăng nhập bằng email và mật khẩu, hệ thống sẽ gửi mã OTP 6 số đến email của bạn</li>
                                    <li>Bạn cần nhập đúng mã OTP để hoàn tất đăng nhập</li>
                                    <li>Mã OTP có hiệu lực trong 10 phút</li>
                                    <li>Đây là lớp bảo mật bổ sung giúp bảo vệ tài khoản ngay cả khi mật khẩu bị lộ</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="bg-white/80 backdrop-blur-xl rounded-2xl shadow-xl shadow-indigo-500/5 border border-red-200 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-red-600 to-pink-600" style="background: linear-gradient(to right, #dc2626, #db2777);">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        Vùng nguy hiểm
                    </h2>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-bold text-gray-800">Đăng xuất khỏi tất cả thiết bị</p>
                            <p class="text-sm text-gray-600">Đăng xuất khỏi tất cả các phiên đăng nhập khác (ngoại trừ phiên hiện tại)</p>
                        </div>
                        <button type="button" class="px-5 py-2.5 bg-red-100 text-red-700 font-semibold rounded-xl hover:bg-red-200 transition-all" disabled>
                            Sắp ra mắt
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
