<x-layouts.app title="Xác thực 2 yếu tố - IT Solo Leveling">
    <div class="min-h-[70vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <!-- Card -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl shadow-indigo-500/10 p-8 border border-white/20">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="mx-auto w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center mb-6 shadow-xl shadow-indigo-500/30">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold gradient-text mb-2">Xác thực 2 yếu tố</h2>
                    <p class="text-gray-600">
                        Chúng tôi đã gửi mã xác thực 6 số đến email của bạn.<br>
                        Vui lòng nhập mã để hoàn tất đăng nhập.
                    </p>
                </div>

                <!-- Status Messages -->
                @if(session('status'))
                    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-emerald-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-emerald-700 text-sm font-medium">{{ session('status') }}</p>
                        </div>
                    </div>
                @endif

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
                <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-6">
                    @csrf

                    <!-- OTP Input -->
                    <div>
                        <label for="code" class="block text-sm font-semibold text-gray-700 mb-2">
                            Mã xác thực
                        </label>
                        <input 
                            type="text" 
                            name="code" 
                            id="code" 
                            maxlength="6"
                            pattern="[0-9]{6}"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            class="w-full px-6 py-4 text-center text-3xl font-bold tracking-[0.5em] rounded-xl input-modern @error('code') border-red-400 @enderror"
                            placeholder="000000"
                            autofocus
                            required
                        >
                        @error('code')
                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full py-4 px-6 rounded-xl font-bold text-white btn-primary shadow-xl hover:shadow-2xl hover:shadow-indigo-500/30 transition-all duration-300 flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Xác nhận</span>
                    </button>
                </form>

                <!-- Actions -->
                <div class="mt-6 flex items-center justify-between text-sm">
                    <form method="POST" action="{{ route('two-factor.resend') }}">
                        @csrf
                        <button type="submit" class="text-indigo-600 hover:text-indigo-800 font-medium transition-colors flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Gửi lại mã
                        </button>
                    </form>

                    <a href="{{ route('two-factor.cancel') }}" class="text-gray-500 hover:text-gray-700 font-medium transition-colors flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Hủy đăng nhập
                    </a>
                </div>

                <!-- Help Text -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <div class="flex items-start space-x-3 text-sm text-gray-500">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p>
                            Mã có hiệu lực trong <strong>10 phút</strong>. Nếu không nhận được email, hãy kiểm tra hộp thư Spam hoặc nhấn "Gửi lại mã".
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
