<x-layouts.app title="Đặt lại mật khẩu - IT Solo Leveling">
    <div class="min-h-[70vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <!-- Card -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl shadow-indigo-500/10 p-8 border border-white/20">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="mx-auto w-20 h-20 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center mb-6 shadow-xl shadow-emerald-500/30">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold gradient-text mb-2">Đặt mật khẩu mới</h2>
                    <p class="text-gray-600">
                        Nhập mật khẩu mới cho tài khoản của bạn.
                    </p>
                </div>

                <!-- Status Messages -->
                @if(session('error'))
                    <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-red-700 text-sm font-medium">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                <!-- Form -->
                <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
                    @csrf

                    <!-- New Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                            Mật khẩu mới
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <input 
                                type="password" 
                                name="password" 
                                id="password" 
                                class="w-full pl-12 pr-4 py-4 rounded-xl input-modern @error('password') border-red-400 @enderror"
                                placeholder="••••••••"
                                autofocus
                                required
                            >
                        </div>
                        @error('password')
                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">
                            Xác nhận mật khẩu
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <input 
                                type="password" 
                                name="password_confirmation" 
                                id="password_confirmation" 
                                class="w-full pl-12 pr-4 py-4 rounded-xl input-modern"
                                placeholder="••••••••"
                                required
                            >
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full py-4 px-6 rounded-xl font-bold text-white bg-gradient-to-r from-emerald-500 to-teal-600 shadow-xl hover:shadow-2xl hover:shadow-emerald-500/30 transition-all duration-300 flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Đặt lại mật khẩu</span>
                    </button>
                </form>

                <!-- Password Requirements -->
                <div class="mt-6 p-4 rounded-xl bg-gray-50 border border-gray-200">
                    <p class="text-sm font-medium text-gray-700 mb-2">Yêu cầu mật khẩu:</p>
                    <ul class="text-sm text-gray-500 space-y-1">
                        <li class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path>
                            </svg>
                            Ít nhất 8 ký tự
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
