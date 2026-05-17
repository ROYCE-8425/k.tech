<x-layouts.app title="Demo — Smart CV Matcher">
    <div class="max-w-2xl mx-auto py-4">

        {{-- Product identity --}}
        <div class="text-center mb-8 animate-slide-up">
            <div class="w-16 h-16 rounded-2xl bg-gradient-brand flex items-center justify-center text-white mx-auto mb-4 shadow-xl shadow-indigo-500/30">
                <svg class="w-8 h-8 animate-[spin_3s_linear_infinite]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Smart CV Matcher</h1>
            <p class="text-gray-500 text-sm max-w-md mx-auto">
                {{ __('AI tự động phân tích CV ứng viên, so khớp với Job Description, và xếp hạng cho nhà tuyển dụng.') }}
            </p>
        </div>

        {{-- How it works (compact) --}}
        <div class="flex items-center justify-center gap-3 mb-8 animate-fade-in" style="animation-delay: 0.15s;">
            <div class="flex items-center gap-1.5 px-3 py-1.5 bg-white rounded-full border border-gray-200 text-xs text-gray-600">
                <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[10px] font-bold">1</span>
                {{ __('Ứng viên nộp CV') }}
            </div>
            <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            <div class="flex items-center gap-1.5 px-3 py-1.5 bg-white rounded-full border border-gray-200 text-xs text-gray-600">
                <span class="w-5 h-5 rounded-full bg-violet-100 text-violet-600 flex items-center justify-center text-[10px] font-bold">2</span>
                {{ __('AI phân tích') }}
            </div>
            <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            <div class="flex items-center gap-1.5 px-3 py-1.5 bg-white rounded-full border border-gray-200 text-xs text-gray-600">
                <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-[10px] font-bold">3</span>
                {{ __('Xếp hạng ứng viên') }}
            </div>
        </div>

        {{-- Flash messages --}}
        @if(session('status'))
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 mb-4 text-center animate-fade-in">
                <p class="text-emerald-800 font-semibold text-sm">{{ session('status') }}</p>
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-4 text-center animate-fade-in">
                <p class="text-red-800 font-semibold text-sm">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Demo badge --}}
        <div class="bg-violet-50 border border-violet-200 rounded-xl px-4 py-3 mb-6 text-center animate-fade-in" style="animation-delay: 0.2s;">
            <p class="text-violet-800 font-bold text-sm flex items-center justify-center gap-1.5"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg> {{ __('Chế độ Demo — Dữ liệu đã chuẩn bị sẵn, chọn vai trò để bắt đầu') }}</p>
        </div>

        {{-- Role selection — THE primary interaction --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6 animate-slide-up" style="animation-delay: 0.25s;">

            {{-- Candidate entry --}}
            <form action="{{ route('demo.enter-candidate') }}" method="POST">
                @csrf
                <button type="submit" id="btn-enter-candidate"
                    class="w-full bg-white rounded-2xl border-2 border-gray-200 hover:border-indigo-500 hover:shadow-xl hover:shadow-indigo-100/50 p-6 text-left transition-all duration-300 group cursor-pointer">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 rounded-xl bg-indigo-100 group-hover:bg-indigo-500 flex items-center justify-center transition-colors">
                            <svg class="w-7 h-7 group-hover:scale-110 transition-transform text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">{{ __('Vào vai Ứng viên') }}</h2>
                            <p class="text-xs text-gray-500">{{ __('Xem job → Ứng tuyển → AI phân tích CV') }}</p>
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3 text-xs text-gray-500 space-y-1 mb-3">
                        <p class="font-semibold text-gray-700">Tài khoản demo:</p>
                        <p>Nguyễn Văn Demo · Backend Developer</p>
                        <p class="text-green-600">✓ Hồ sơ & CV có sẵn · 2 đơn đã nộp</p>
                    </div>
                    <div class="text-sm font-bold text-indigo-600 group-hover:text-indigo-700 flex items-center gap-1">
                        {{ __('Bắt đầu') }}
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </div>
                </button>
            </form>

            {{-- Recruiter entry --}}
            <form action="{{ route('demo.enter-recruiter') }}" method="POST">
                @csrf
                <button type="submit" id="btn-enter-recruiter"
                    class="w-full bg-white rounded-2xl border-2 border-gray-200 hover:border-purple-500 hover:shadow-xl hover:shadow-purple-100/50 p-6 text-left transition-all duration-300 group cursor-pointer">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 rounded-xl bg-purple-100 group-hover:bg-purple-500 flex items-center justify-center transition-colors">
                            <svg class="w-7 h-7 group-hover:scale-110 transition-transform text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">{{ __('Vào vai Nhà tuyển dụng') }}</h2>
                            <p class="text-xs text-gray-500">{{ __('Xem AI Shortlist → Đánh giá ứng viên') }}</p>
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3 text-xs text-gray-500 space-y-1 mb-3">
                        <p class="font-semibold text-gray-700">Tài khoản demo:</p>
                        <p>Demo Recruiter · KTC Demo Corp</p>
                        <p class="text-green-600">✓ 4 job đã đăng · Có đơn ứng tuyển sẵn</p>
                    </div>
                    <div class="text-sm font-bold text-purple-600 group-hover:text-purple-700 flex items-center gap-1">
                        {{ __('Bắt đầu') }}
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </div>
                </button>
            </form>
        </div>

        {{-- Quick test guide --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-5 animate-fade-in" style="animation-delay: 0.35s;">
            <h3 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg> Hướng dẫn demo <span class="text-gray-400 font-normal">(~2 phút)</span>
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-indigo-50/50 rounded-xl p-3">
                    <p class="font-bold text-indigo-700 text-xs mb-2 flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg> Ứng viên</p>
                    <ol class="list-decimal list-inside text-xs text-gray-600 space-y-1.5">
                        <li>Xem danh sách — <strong>Backend</strong> đã apply + có AI</li>
                        <li>Mở <strong>Data Analyst</strong> hoặc <strong>AI/ML</strong> → thử apply mới</li>
                        <li>Upload CV hoặc dùng hồ sơ có sẵn</li>
                        <li>Xem AI advisory ngay sau khi nộp</li>
                    </ol>
                    <div class="mt-2 p-2 bg-white rounded-lg border border-indigo-100 text-[11px] text-indigo-600">
                        <svg class="w-4 h-4 inline-block -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg> Các job đánh dấu <span class="font-semibold text-emerald-600"><svg class="w-3.5 h-3.5 inline-block -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Đã ứng tuyển</span> có sẵn kết quả AI
                    </div>
                </div>
                <div class="bg-purple-50/50 rounded-xl p-3">
                    <p class="font-bold text-purple-700 text-xs mb-2 flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg> Nhà tuyển dụng</p>
                    <ol class="list-decimal list-inside text-xs text-gray-600 space-y-1.5">
                        <li>Dashboard → chọn job có badge <strong><svg class="w-3.5 h-3.5 inline-block -mt-0.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg> AI sẵn</strong></li>
                        <li>Nhấn "AI Shortlist" → xem xếp hạng ứng viên</li>
                        <li>Thử "Tính lại AI" để chạy pipeline thật</li>
                        <li>Tạo job mới + kiểm tra JD quality</li>
                    </ol>
                    <div class="mt-2 p-2 bg-white rounded-lg border border-purple-100 text-[11px] text-purple-600">
                        <svg class="w-4 h-4 inline-block -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg> Job <strong>Backend</strong> có AI sẵn · <strong>Frontend</strong> cần chấm AI mới
                    </div>
                </div>
            </div>
        </div>

        {{-- Reset Demo button --}}
        <div class="mt-6 text-center animate-fade-in" style="animation-delay: 0.45s;">
            <form action="{{ route('demo.reset') }}" method="POST" onsubmit="return confirm('⚠️ Reset sẽ xoá toàn bộ dữ liệu và khôi phục về trạng thái ban đầu. Bạn chắc chắn?');">
                @csrf
                <button type="submit" id="btn-reset-demo"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-gray-500 hover:text-red-600 bg-gray-50 hover:bg-red-50 border border-gray-200 hover:border-red-300 rounded-xl transition-all duration-300 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Reset Demo về dữ liệu gốc
                </button>
            </form>
            <p class="text-[11px] text-gray-400 mt-1">Xoá sạch và khôi phục 4 jobs + 4 applications demo</p>
        </div>

    </div>
</x-layouts.app>
