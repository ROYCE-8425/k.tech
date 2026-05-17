<x-layouts.app title="{{ $job->title }} - {{ $job->company->name ?? 'Công ty' }}">
    <div class="max-w-5xl mx-auto">
        <!-- Back Button -->
        <a href="{{ route('home') }}" class="inline-flex items-center text-gray-500 hover:text-indigo-600 mb-8 group transition-colors">
            <div class="w-10 h-10 rounded-xl bg-gray-100 group-hover:bg-indigo-100 flex items-center justify-center mr-3 transition-colors">
                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </div>
            <span class="font-medium">Quay lại danh sách việc làm</span>
        </a>

        {{-- Demo seed-aware context bar --}}
        @if(config('app.demo_mode'))
            @auth
                @if(Auth::user()->role === 'candidate')
                    @if(!empty($alreadyApplied) && !empty($persistedAdvisory))
                        <div class="mb-6 flex items-center gap-3 p-3 rounded-2xl bg-emerald-50 border border-emerald-200 animate-fade-in">
                            <span class="text-lg">✅</span>
                            <div class="text-sm">
                                <span class="font-semibold text-emerald-800">Bạn đã ứng tuyển vị trí này.</span>
                                <span class="text-emerald-600">AI đã phân tích — xem kết quả bên phải hoặc bổ sung thông tin để AI đánh giá lại.</span>
                            </div>
                        </div>
                    @elseif(!empty($alreadyApplied))
                        <div class="mb-6 flex items-center gap-3 p-3 rounded-2xl bg-amber-50 border border-amber-200 animate-fade-in">
                            <span class="text-lg">📝</span>
                            <div class="text-sm">
                                <span class="font-semibold text-amber-800">Bạn đã ứng tuyển vị trí này.</span>
                                <span class="text-amber-600">Đơn đang chờ xử lý — nhà tuyển dụng sẽ xem xét sớm.</span>
                            </div>
                        </div>
                    @else
                        <div class="mb-6 flex items-center gap-3 p-3 rounded-2xl bg-blue-50 border border-blue-200 animate-fade-in">
                            <span class="text-lg">🆕</span>
                            <div class="text-sm">
                                <span class="font-semibold text-blue-800">Bạn chưa ứng tuyển vị trí này.</span>
                                <span class="text-blue-600">Hãy thử nộp đơn để trải nghiệm AI phân tích CV ngay lập tức.</span>
                            </div>
                        </div>
                    @endif
                @elseif(Auth::user()->role === 'recruiter' || Auth::user()->role === 'admin')
                    @php
                        $jobAppCount = \App\Models\Application::where('job_id', $job->id)->count();
                        $jobAiCount = \App\Models\Application::where('job_id', $job->id)->whereNotNull('ai_match_result')->count();
                    @endphp
                    <div class="mb-6 flex items-center gap-3 p-3 rounded-2xl bg-purple-50 border border-purple-200 animate-fade-in">
                        <span class="text-lg">🏢</span>
                        <div class="text-sm">
                            <span class="font-semibold text-purple-800">Nhà tuyển dụng:</span>
                            @if($jobAppCount > 0 && $jobAiCount > 0)
                                <span class="text-purple-600">{{ $jobAppCount }} ứng viên · {{ $jobAiCount }} có kết quả AI sẵn — nhấn "AI Shortlist" bên phải.</span>
                            @elseif($jobAppCount > 0)
                                <span class="text-purple-600">{{ $jobAppCount }} ứng viên chưa có AI — nhấn "AI Shortlist" để chấm điểm.</span>
                            @else
                                <span class="text-purple-600">Chưa có ứng viên. Phù hợp để test luồng apply mới.</span>
                            @endif
                        </div>
                    </div>
                @endif
            @endauth
        @endif

        <!-- Success Message with AI Advisory -->
        @if(session('status'))
            @php $aiAdvisory = session('ai_advisory'); @endphp
            <div class="mb-6 p-6 rounded-3xl bg-gradient-to-r from-emerald-500 to-teal-500 text-white shadow-2xl shadow-emerald-500/30" style="background: linear-gradient(to right, #10b981, #14b8a6);" 
                 x-data="{ show: true }" 
                 x-show="show"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 x-init="setTimeout(() => show = false, 30000)">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-lg mb-1">✅ Hồ sơ đã được tiếp nhận!</h3>
                        <p class="text-emerald-50 mb-3">🤖 AI đã phân tích CV của bạn cho vị trí: <strong>{{ $job->title }}</strong></p>
                        
                        @if($aiAdvisory && isset($aiAdvisory['fit_score']))
                            @php
                                $rawScore = $aiAdvisory['fit_score'];
                                $score = $rawScore > 10 ? $rawScore / 10 : $rawScore;
                                $aiAdvisory['fit_score'] = $score; // update for display
                                
                                if ($score >= 8) { $label = 'Xuất sắc'; $emoji = '🌟'; }
                                elseif ($score >= 6.5) { $label = 'Tốt'; $emoji = '👍'; }
                                elseif ($score >= 5) { $label = 'Khá'; $emoji = '✅'; }
                                else { $label = 'Trung bình'; $emoji = '💪'; }
                            @endphp

                            {{-- Full AI Advisory Panel --}}
                            <div class="mt-4 p-5 rounded-2xl bg-indigo-900/40 border border-indigo-200/30 backdrop-blur-md">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <p class="text-sm text-indigo-50 mb-2 font-semibold">🤖 Điểm phù hợp của bạn:</p>
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-5xl font-black text-white">{{ number_format($score, 1) }}</span>
                                            <span class="text-2xl font-bold text-indigo-200">/10</span>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-5xl mb-2">{{ $emoji }}</div>
                                        <p class="text-lg font-bold bg-white/20 text-white px-3 py-1 rounded-full">{{ $label }}</p>
                                        @if(!empty($aiAdvisory['rank_label']))
                                            <p class="text-xs text-indigo-100 mt-1">{{ $aiAdvisory['rank_label'] }}</p>
                                        @endif
                                    </div>
                                </div>

                                {{-- Matched Skills --}}
                                @if(!empty($aiAdvisory['matched_skills']))
                                    <div class="pt-3 border-t border-white/20">
                                        <p class="text-xs font-bold text-white/90 mb-2">✅ Kỹ năng phù hợp:</p>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach(array_slice($aiAdvisory['matched_skills'], 0, 8) as $skill)
                                                <span class="px-2 py-0.5 rounded-lg bg-emerald-400/30 text-white text-xs font-medium">{{ $skill }}</span>
                                            @endforeach
                                            @if(count($aiAdvisory['matched_skills']) > 8)
                                                <span class="px-2 py-0.5 rounded-lg bg-white/10 text-white/80 text-xs">+{{ count($aiAdvisory['matched_skills']) - 8 }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- Missing Skills Advisory --}}
                                @if(!empty($aiAdvisory['missing_skills']))
                                    <div class="pt-3 mt-3 border-t border-white/20">
                                        <p class="text-xs font-bold text-amber-200 mb-2">💡 Có thể bổ sung vào CV:</p>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach(array_slice($aiAdvisory['missing_skills'], 0, 6) as $skill)
                                                <span class="px-2 py-0.5 rounded-lg bg-amber-400/25 text-amber-100 text-xs font-medium">{{ $skill }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Risk Flags --}}
                                @if(!empty($aiAdvisory['risk_flags']))
                                    <div class="pt-3 mt-3 border-t border-white/20">
                                        <p class="text-xs font-bold text-amber-200 mb-2">📋 Lưu ý:</p>
                                        <ul class="text-xs text-white/90 space-y-1">
                                            @foreach(array_slice($aiAdvisory['risk_flags'], 0, 4) as $flag)
                                                <li class="flex items-start gap-1.5">
                                                    <span class="text-amber-300 mt-0.5">•</span>
                                                    <span>{{ $flag }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                {{-- Low Confidence Warning --}}
                                @if(($aiAdvisory['confidence_label'] ?? '') === 'low')
                                    <div class="pt-3 mt-3 border-t border-white/20">
                                        <p class="text-xs text-amber-200 flex items-start gap-1.5">
                                            <span>⚠️</span>
                                            <span>AI chưa đủ thông tin để đánh giá chính xác. Hãy <a href="{{ route('candidate.dashboard') }}" class="underline font-semibold">bổ sung hồ sơ</a> để tăng độ chính xác.</span>
                                        </p>
                                    </div>
                                @endif

                                <div class="pt-3 mt-3 border-t border-white/30">
                                    <p class="text-sm text-white/90 flex items-start gap-2">
                                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span>Kết quả này được AI phân tích tự động. Nếu bạn muốn điểm cao hơn, hãy <a href="{{ route('candidate.dashboard') }}" class="underline font-semibold">cập nhật hồ sơ</a> và thử nộp lại.</span>
                                    </p>
                                </div>
                            </div>
                        @elseif(session('ai_score'))
                            {{-- Legacy score fallback (no full advisory) --}}
                            <div class="mt-4 p-5 rounded-2xl bg-white/20 border-2 border-white/40 backdrop-blur-sm">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <p class="text-sm text-white/90 mb-2 font-semibold">🤖 Điểm phù hợp của bạn:</p>
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-5xl font-black">{{ number_format(session('ai_score'), 1) }}</span>
                                            <span class="text-2xl font-bold text-white/90">/10</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="pt-3 border-t border-white/30">
                                    <p class="text-sm text-white/90">⚠️ Hồ sơ đã tải lên thành công! Để AI tiến hành phân tích chi tiết, vui lòng kiểm tra và bấm <b>"Xác nhận & Nộp hồ sơ"</b> ở khung bên phải.</p>
                                </div>
                            </div>
                        @else
                            {{-- No score available — AI service was unavailable --}}
                            <div class="mt-3 p-4 rounded-xl bg-white/15">
                                <p class="text-sm text-white/90 flex items-center gap-2">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>Hồ sơ đã tải lên thành công! Vui lòng xác nhận thông tin ở khung bên phải để AI bắt đầu chấm điểm.</span>
                                </p>
                            </div>
                        @endif
                    </div>
                    <button @click="show = false" class="w-8 h-8 rounded-lg bg-white/15 hover:bg-white/30 transition-all flex items-center justify-center flex-shrink-0 text-white font-bold">✕</button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-5 rounded-2xl bg-red-50 border-2 border-red-200 text-red-800" 
                 x-data="{ show: true }" 
                 x-show="show"
                 x-transition
                 x-init="setTimeout(() => show = false, 8000)">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="flex-1 font-semibold">{{ session('error') }}</p>
                    <button @click="show = false" class="text-red-600 hover:text-red-800">✕</button>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Job Header Card -->
                <div class="relative bg-white rounded-3xl shadow-xl shadow-gray-200/50 overflow-hidden">
                    <!-- Gradient Header -->
                    <div class="relative h-48 bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500" style="background: linear-gradient(to bottom right, #4f46e5, #9333ea, #ec4899);">
                        <div class="absolute inset-0 bg-black/10"></div>
                        <div class="absolute inset-0 overflow-hidden">
                            <div class="absolute -top-20 -right-20 w-64 h-64 bg-white/10 rounded-full"></div>
                            <div class="absolute -bottom-32 -left-32 w-80 h-80 bg-white/5 rounded-full"></div>
                        </div>
                        
                        <!-- Company Badge -->
                        <div class="absolute -bottom-10 left-8">
                            <div class="w-20 h-20 rounded-2xl bg-white shadow-xl flex items-center justify-center overflow-hidden">
                                @if($job->company && $job->company->logo_path)
                                    <img src="{{ asset('storage/' . $job->company->logo_path) }}" alt="{{ $job->company->name ?? 'Company' }}" class="w-full h-full object-cover">
                                @else
                                    <span class="text-3xl font-bold gradient-text">
                                        {{ strtoupper(substr($job->company->name ?? 'C', 0, 1)) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Job Info -->
                    <div class="p-8 pt-14">
                        <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
                            <div>
                                <p class="text-indigo-600 font-semibold mb-2">{{ $job->company->name ?? 'Công ty' }}</p>
                                <h1 class="text-3xl font-bold text-gray-900">{{ $job->title }}</h1>
                            </div>
                            @if($job->created_at->diffInDays() < 7)
                                <span class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 text-white text-sm font-semibold shadow-sm" style="background: linear-gradient(to right, #10b981, #14b8a6);">
                                    ✨ Mới đăng
                                </span>
                            @endif
                        </div>

                        <!-- Meta Tags -->
                        <div class="flex flex-wrap gap-3 mt-6">
                            @if($job->location)
                                <div class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-100 text-gray-700">
                                    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span class="font-medium">{{ $job->location }}</span>
                                </div>
                            @endif
                            @if($job->salary_min || $job->salary_max)
                                <div class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-100 text-emerald-700">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="font-semibold">
                                        @if($job->salary_min && $job->salary_max)
                                            {{ number_format($job->salary_min) }} - {{ number_format($job->salary_max) }} VNĐ
                                        @elseif($job->salary_min)
                                            Từ {{ number_format($job->salary_min) }} VNĐ
                                        @else
                                            Tới {{ number_format($job->salary_max) }} VNĐ
                                        @endif
                                    </span>
                                </div>
                            @endif
                            <div class="inline-flex items-center px-4 py-2 rounded-xl bg-indigo-100 text-indigo-700">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span class="font-medium">{{ $job->created_at->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Job Description -->
                <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 p-8">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900">Mô tả công việc</h2>
                    </div>
                    <div class="prose prose-lg max-w-none text-gray-600 leading-relaxed">
                        {!! nl2br(e($job->description)) !!}
                    </div>
                </div>

                <!-- Requirements -->
                @if($job->requirements)
                    <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 p-8">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center mr-4">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900">Yêu cầu ứng viên</h2>
                        </div>
                        <div class="prose prose-lg max-w-none text-gray-600 leading-relaxed">
                            {!! nl2br(e($job->requirements)) !!}
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="sticky top-28">
                    @php
                        $company = $job->company;
                        $companyName = $company?->name ?? 'Công ty';
                        $companyAddress = $company?->address;
                        $companyWebsite = $company?->website;
                        $companyDescription = $company?->description;
                        $companyLogoPath = $company?->logo_path;
                    @endphp

                    <!-- Company Card (Candidate-profile style) -->
                    <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 overflow-hidden mb-6" id="company-card">
                        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-6" style="background: linear-gradient(to right, #4f46e5, #9333ea);">
                            <h2 class="text-xl font-bold text-white mb-1">🏢 Thông tin công ty</h2>
                            <p class="text-indigo-100 text-sm">Giới thiệu nhanh về đơn vị tuyển dụng</p>
                        </div>
                        <div class="p-6 space-y-5">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 rounded-2xl bg-white shadow flex items-center justify-center overflow-hidden">
                                    @if($companyLogoPath)
                                        <img src="{{ asset('storage/' . $companyLogoPath) }}" alt="{{ $companyName }}" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-2xl font-bold gradient-text">{{ strtoupper(substr($companyName, 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="text-lg font-bold text-gray-900 truncate">{{ $companyName }}</p>
                                    @if($companyAddress)
                                        <p class="text-sm text-gray-500 truncate">{{ $companyAddress }}</p>
                                    @endif
                                </div>
                            </div>

                            @if($companyWebsite)
                                <a href="{{ $companyWebsite }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center w-full py-3 rounded-xl bg-indigo-50 text-indigo-700 font-semibold hover:bg-indigo-600 hover:text-white transition-all">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 010 5.656m-1.414-1.414a2 2 0 010-2.828m-6.364 6.364a9 9 0 1112.728 0M12 21v-2"></path>
                                    </svg>
                                    Website công ty
                                </a>
                            @endif

                            @if($companyDescription)
                                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-4">
                                    <p class="text-sm font-semibold text-gray-800 mb-2">Giới thiệu</p>
                                    <p class="text-sm text-gray-600 leading-relaxed">{!! nl2br(e($companyDescription)) !!}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if(Auth::check() && (Auth::user()->role === 'admin' || Auth::user()->role === 'recruiter'))
                        <!-- Admin/Recruiter Panel -->
                        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 overflow-hidden" id="admin-panel">
                            <!-- Panel Header -->
                            <div class="bg-gradient-to-r from-violet-600 to-purple-600 p-6" style="background: linear-gradient(to right, #7c3aed, #9333ea);">
                                <h2 class="text-xl font-bold text-white mb-1">🤖 AI Matching</h2>
                                <p class="text-violet-100 text-sm">Xem xếp hạng AI và quản lý ứng viên</p>
                            </div>

                            <div class="p-6 space-y-4">
                                <!-- AI Shortlist (Primary Action) -->
                                <a href="{{ route('admin.jobs.ai-shortlist', $job->id) }}" 
                                   class="flex items-center justify-center w-full py-4 rounded-xl bg-gradient-to-r from-violet-600 to-purple-600 text-white font-bold text-lg hover:shadow-xl hover:shadow-violet-500/30 hover:scale-[1.02] transition-all duration-300" style="background: linear-gradient(to right, #7c3aed, #9333ea);">
                                    <span class="mr-2">🤖</span>
                                    AI Shortlist
                                </a>

                                <!-- View Applications (Secondary) -->
                                <a href="{{ route('admin.jobs.applications', $job->id) }}" 
                                   class="flex items-center justify-center w-full py-3 rounded-xl bg-indigo-50 text-indigo-700 font-semibold hover:bg-indigo-100 transition-all duration-300">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    Danh sách ứng viên
                                </a>

                                <!-- Back to Dashboard -->
                                <a href="{{ route('admin.dashboard') }}" 
                                   class="flex items-center justify-center w-full py-3 rounded-xl bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition-all duration-300">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                    </svg>
                                    Quay lại Dashboard
                                </a>
                            </div>

                            <!-- Job Stats -->
                            <div class="px-6 pb-6">
                                <div class="p-4 rounded-xl bg-gradient-to-r from-gray-50 to-violet-50 border border-violet-100">
                                    <p class="text-sm text-gray-500 mb-2">Thống kê nhanh</p>
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-700 font-medium">Đã đăng</span>
                                        <span class="text-violet-600 font-bold">{{ $job->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        @guest
                            <!-- Guest: Require Login -->
                            <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 overflow-hidden" id="apply-form">
                                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-6" style="background: linear-gradient(to right, #4f46e5, #9333ea);">
                                    <h2 class="text-xl font-bold text-white mb-1">🔐 Đăng nhập để ứng tuyển</h2>
                                    <p class="text-indigo-100 text-sm">Bạn cần đăng nhập tài khoản Ứng tuyển để nộp đơn.</p>
                                </div>
                                <div class="p-6 space-y-4">
                                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center w-full py-4 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold hover:shadow-xl hover:scale-[1.02] transition-all" style="background: linear-gradient(to right, #4f46e5, #9333ea);">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                        </svg>
                                        Đăng nhập ngay
                                    </a>
                                    <p class="text-center text-sm text-gray-500">
                                        Chưa có tài khoản? <a href="{{ route('register') }}" class="text-indigo-600 font-semibold hover:underline">Đăng ký miễn phí</a>
                                    </p>
                                </div>
                            </div>
                        @else
                            @if(!empty($alreadyApplied) && Auth::user()->role === 'candidate')
                                <!-- 3-Step AI CV Review Flow -->
                                <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 overflow-hidden" id="apply-form">
                                    @php
                                        $activeFollowupFields = session('ai_followup_fields', $followupFields ?? []);
                                        $activeAdvisory = session('ai_advisory', $persistedAdvisory ?? null);
                                        $cvExtracted = session('cv_extracted_info');
                                        $isJustApplied = session('status') && $cvExtracted;
                                        
                                        if ($activeAdvisory && isset($activeAdvisory['fit_score'])) {
                                            $rawScore = $activeAdvisory['fit_score'];
                                            $activeAdvisory['fit_score'] = $rawScore > 10 ? $rawScore / 10 : $rawScore;
                                        }

                                        // Mapping follow-up fields to AI questions
                                        $chatQuestions = [
                                            'years_experience' => ['q' => '⏱️ Bạn có bao nhiêu năm kinh nghiệm làm việc trong lĩnh vực này?', 'type' => 'number', 'placeholder' => 'VD: 3'],
                                            'key_skills' => ['q' => '🛠️ Hãy liệt kê vài kỹ năng công nghệ chính của bạn (cách nhau bằng dấu phẩy).', 'type' => 'text', 'placeholder' => 'VD: PHP, Laravel, MySQL'],
                                            'education_level' => ['q' => '🎓 Trình độ học vấn cao nhất của bạn hiện tại là gì?', 'type' => 'select', 'options' => ['THPT', 'Trung cấp', 'Cao đẳng', 'Đại học', 'Thạc sĩ', 'Tiến sĩ', 'Bootcamp/Tự học']],
                                            'primary_role' => ['q' => '💼 Đâu là vai trò chính mà bạn tự tin nhất?', 'type' => 'select', 'options' => ['Backend Developer', 'Frontend Developer', 'Fullstack Developer', 'Mobile Developer', 'QA/Tester', 'DevOps Engineer', 'Data Analyst', 'ML Engineer', 'Product/Business Analyst']],
                                            'english_level' => ['q' => '🇬🇧 Khả năng tiếng Anh của bạn đang ở mức nào?', 'type' => 'select', 'options' => ['Cơ bản (A1-A2)', 'Trung cấp (B1-B2)', 'Nâng cao (C1-C2)', 'Bản ngữ / Native']],
                                            'phone' => ['q' => '📱 Số điện thoại liên hệ?', 'type' => 'text', 'placeholder' => 'VD: 0912 345 678'],
                                        ];
                                        $activeQuestions = [];
                                        foreach($activeFollowupFields as $field) {
                                            if(isset($chatQuestions[$field])) {
                                                $activeQuestions[] = array_merge(['id' => $field], $chatQuestions[$field]);
                                            }
                                        }
                                        
                                        // Determine initial step
                                        $initialStep = 3; // default: show result
                                        if ($isJustApplied && $cvExtracted) {
                                            $initialStep = 1; // just applied: show CV confirmation
                                        } elseif ($isJustApplied && !empty($activeFollowupFields)) {
                                            $initialStep = 2; // has follow-up questions
                                        }
                                    @endphp

                                    <div x-data="{
                                        step: {{ $initialStep }},
                                        cvInfo: {{ json_encode($cvExtracted ?? []) }},
                                        editMode: false,
                                        questions: {{ json_encode($activeQuestions) }},
                                        currentQIndex: 0,
                                        messages: [],
                                        currentInput: '',
                                        isSubmitting: false,

                                        initChat() {
                                            if (this.questions.length === 0) {
                                                this.step = 3;
                                                return;
                                            }
                                            this.messages = [
                                                { type: 'ai', text: '🤖 Xin chào! Tôi cần hỏi thêm <strong>' + this.questions.length + ' câu</strong> để đánh giá chính xác hơn.' }
                                            ];
                                            setTimeout(() => this.askNextQuestion(), 500);
                                        },

                                        askNextQuestion() {
                                            if (this.currentQIndex < this.questions.length) {
                                                const q = this.questions[this.currentQIndex];
                                                this.messages.push({ type: 'ai', text: q.q, field: q.id });
                                                this.scrollChat();
                                            } else {
                                                this.messages.push({ type: 'ai', text: '✅ Cảm ơn! Đang gửi thông tin cho AI chấm điểm...' });
                                                this.scrollChat();
                                                setTimeout(() => this.submitFollowup(), 500);
                                            }
                                        },

                                        answer(text, value = null) {
                                            if (!text) return;
                                            const val = value !== null ? value : text;
                                            this.messages.push({ type: 'user', text: text });
                                            const fieldId = this.questions[this.currentQIndex].id;
                                            const inputEl = document.getElementById('followup_' + fieldId);
                                            if (inputEl) inputEl.value = val;
                                            this.currentInput = '';
                                            this.currentQIndex++;
                                            this.scrollChat();
                                            setTimeout(() => this.askNextQuestion(), 400);
                                        },

                                        scrollChat() {
                                            this.$nextTick(() => {
                                                const box = document.getElementById('ai-chat-box');
                                                if (box) box.scrollTop = box.scrollHeight;
                                            });
                                        },

                                        submitFollowup() {
                                            this.isSubmitting = true;
                                            document.getElementById('followupForm_{{ $job->id }}').submit();
                                        },

                                        confirmCv() {
                                            if (this.questions.length > 0) {
                                                this.step = 2;
                                                this.$nextTick(() => this.initChat());
                                            } else {
                                                this.submitFollowup();
                                            }
                                        }
                                    }">
                                        {{-- ═══ STEP INDICATOR ═══ --}}
                                        <div class="px-6 pt-5 pb-3">
                                            <div class="flex items-center justify-center gap-1 text-xs">
                                                <template x-for="s in [1,2,3]" :key="s">
                                                    <div class="flex items-center">
                                                        <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs transition-all duration-300"
                                                             :class="step >= s ? 'text-white' : 'bg-gray-100 text-gray-400'"
                                                             :style="step >= s ? 'background: linear-gradient(135deg, #6366f1, #8b5cf6)' : ''">
                                                            <span x-text="s"></span>
                                                        </div>
                                                        <div x-show="s < 3" class="w-8 h-0.5 mx-1 transition-all duration-300"
                                                             :class="step > s ? 'bg-indigo-400' : 'bg-gray-200'"></div>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="flex justify-between text-[10px] text-gray-400 mt-1 px-1">
                                                <span :class="step === 1 && 'text-indigo-600 font-bold'">Xác nhận CV</span>
                                                <span :class="step === 2 && 'text-indigo-600 font-bold'">Hỏi thêm</span>
                                                <span :class="step === 3 && 'text-indigo-600 font-bold'">Kết quả AI</span>
                                            </div>
                                        </div>

                                        {{-- ═══ STEP 1: CV Confirmation POPUP ═══ --}}
                                        <div x-show="step === 1" x-transition class="px-6 pb-4">
                                            <div class="text-center py-4">
                                                <div class="w-14 h-14 rounded-2xl mx-auto mb-3 flex items-center justify-center" style="background: linear-gradient(135deg, #eef2ff, #e0e7ff);">
                                                    <span class="text-2xl">📄</span>
                                                </div>
                                                <p class="text-sm font-semibold text-gray-700">AI đã đọc CV của bạn</p>
                                                <p class="text-xs text-gray-400 mt-1">Nhấn để xem và xác nhận thông tin</p>
                                                <button @click="$refs.cvModal.showModal()" type="button"
                                                    class="mt-4 w-full py-3 rounded-xl text-white font-bold text-sm transition-all hover:scale-[1.02]"
                                                    style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                                                    👁️ Xem thông tin CV đã trích xuất
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Fullscreen CV Preview Modal --}}
                                        <dialog x-ref="cvModal" class="fixed inset-0 w-full h-full max-w-full max-h-full m-0 p-0 bg-transparent z-50"
                                                style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);"
                                                @click.self="$refs.cvModal.close()">
                                            <div class="flex items-center justify-center min-h-screen p-4">
                                                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
                                                    {{-- Modal Header --}}
                                                    <div class="sticky top-0 z-10 p-5 rounded-t-3xl" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                                                        <div class="flex items-center justify-between">
                                                            <div>
                                                                <h2 class="text-lg font-bold text-white">🤖 AI đã quét CV của bạn</h2>
                                                                <p class="text-indigo-100 text-xs mt-1">Kiểm tra thông tin bên dưới</p>
                                                            </div>
                                                            <button @click="$refs.cvModal.close()" class="w-9 h-9 rounded-xl bg-white/15 text-white hover:bg-white/25 transition-all text-lg">✕</button>
                                                        </div>
                                                    </div>

                                                    {{-- Modal Body --}}
                                                    <div class="p-5 space-y-3">
                                                        <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 border border-gray-100">
                                                            <span class="text-xl">👤</span>
                                                            <div class="flex-1"><p class="text-[10px] text-gray-400 font-semibold uppercase">Họ tên</p><p class="text-base font-bold text-gray-800" x-text="cvInfo.name || 'Không phát hiện'"></p></div>
                                                        </div>
                                                        <div class="grid grid-cols-2 gap-2">
                                                            <div class="p-3 rounded-xl bg-gray-50 border border-gray-100"><span class="text-base">📧</span><p class="text-[10px] text-gray-400 font-semibold uppercase mt-1">Email</p><p class="text-xs font-semibold text-gray-700 truncate" x-text="cvInfo.email || '—'"></p></div>
                                                            <div class="p-3 rounded-xl bg-gray-50 border border-gray-100"><span class="text-base">📱</span><p class="text-[10px] text-gray-400 font-semibold uppercase mt-1">SĐT</p><p class="text-xs font-semibold text-gray-700" x-text="cvInfo.phone || 'Không tìm thấy'"></p></div>
                                                        </div>
                                                        <div class="p-3 rounded-xl bg-gray-50 border border-gray-100">
                                                            <div class="flex items-center gap-2 mb-2"><span class="text-base">🛠️</span><p class="text-[10px] text-gray-400 font-semibold uppercase">Kỹ năng phát hiện từ CV</p></div>
                                                            <div class="flex flex-wrap gap-1.5">
                                                                <template x-if="cvInfo.skills && cvInfo.skills.length > 0"><template x-for="skill in cvInfo.skills" :key="skill"><span class="px-2.5 py-1 rounded-lg text-xs font-semibold" style="background: #ede9fe; color: #6d28d9;" x-text="skill"></span></template></template>
                                                                <template x-if="!cvInfo.skills || cvInfo.skills.length === 0"><span class="text-xs text-gray-400 italic">Không phát hiện kỹ năng từ CV</span></template>
                                                            </div>
                                                        </div>
                                                        <div class="grid grid-cols-2 gap-2">
                                                            <div class="p-3 rounded-xl bg-gray-50 border border-gray-100"><span class="text-lg">📊</span><p class="text-[10px] text-gray-400 font-semibold uppercase mt-1">Kinh nghiệm</p><p class="text-sm font-bold text-gray-800" x-text="cvInfo.experience_years ? cvInfo.experience_years + ' năm' : 'Chưa rõ'"></p></div>
                                                            <div class="p-3 rounded-xl bg-gray-50 border border-gray-100"><span class="text-lg">🎓</span><p class="text-[10px] text-gray-400 font-semibold uppercase mt-1">Học vấn</p><p class="text-sm font-bold text-gray-800 leading-tight" x-text="cvInfo.education || 'Chưa rõ'"></p></div>
                                                        </div>
                                                        <div class="p-3 rounded-xl bg-gray-50 border border-gray-100">
                                                            <p class="text-[10px] text-gray-400 font-semibold uppercase mb-1.5">📝 Nội dung CV trích xuất</p>
                                                            <p class="text-xs text-gray-600 leading-relaxed max-h-32 overflow-y-auto" x-text="cvInfo.summary ? cvInfo.summary + '...' : 'Không trích xuất được'"></p>
                                                        </div>
                                                    </div>

                                                    {{-- Modal Footer --}}
                                                    <div class="sticky bottom-0 p-5 bg-white border-t border-gray-100 rounded-b-3xl">
                                                        <button @click="$refs.cvModal.close(); confirmCv();" type="button"
                                                            class="w-full py-3.5 rounded-xl text-white font-bold text-sm transition-all hover:scale-[1.02] hover:shadow-lg"
                                                            style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                                                            ✅ Xác nhận & tiếp tục chấm điểm AI
                                                        </button>
                                                        <p class="text-center text-[10px] text-gray-400 mt-2">AI sẽ chấm điểm sau khi bạn xác nhận</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </dialog>


                                        {{-- ═══ STEP 2: AI Chat Follow-up ═══ --}}
                                        <div x-show="step === 2" x-transition class="flex flex-col">
                                            {{-- Chat Header --}}
                                            <div class="p-4 flex items-center gap-3" style="background: linear-gradient(to right, #6366f1, #9333ea);">
                                                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-xl">🤖</div>
                                                <div>
                                                    <h2 class="text-lg font-bold text-white leading-tight">Trợ lý AI</h2>
                                                    <p class="text-indigo-100 text-xs">Hỏi thêm thông tin để chấm điểm chính xác</p>
                                                </div>
                                            </div>

                                            {{-- Chat Messages --}}
                                            <div id="ai-chat-box" class="p-5 bg-gray-50/50 space-y-3 max-h-[350px] overflow-y-auto scroll-smooth">
                                                <template x-for="(msg, idx) in messages" :key="idx">
                                                    <div class="flex w-full" :class="msg.type === 'user' ? 'justify-end' : 'justify-start'">
                                                        <template x-if="msg.type === 'ai'">
                                                            <div class="flex gap-2 max-w-[85%]">
                                                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0 mt-1" style="background: linear-gradient(135deg, #818cf8, #a855f7);">AI</div>
                                                                <div class="bg-white px-3 py-2.5 rounded-2xl rounded-tl-sm shadow-sm border border-gray-100 text-sm text-gray-700" x-html="msg.text"></div>
                                                            </div>
                                                        </template>
                                                        <template x-if="msg.type === 'user'">
                                                            <div class="px-3 py-2.5 rounded-2xl rounded-tr-sm shadow-sm text-sm text-white max-w-[85%]" style="background: #6366f1;" x-text="msg.text"></div>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>

                                            {{-- Chat Input --}}
                                            <div class="p-4 bg-white border-t border-gray-100" x-show="currentQIndex < questions.length && !isSubmitting">
                                                <template x-if="questions[currentQIndex]">
                                                    <div>
                                                        <template x-if="questions[currentQIndex].type === 'text' || questions[currentQIndex].type === 'number'">
                                                            <div class="flex gap-2">
                                                                <input :type="questions[currentQIndex].type"
                                                                       x-model="currentInput"
                                                                       @keydown.enter="answer(currentInput)"
                                                                       :placeholder="questions[currentQIndex].placeholder"
                                                                       class="flex-1 px-4 py-2.5 bg-gray-100 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 rounded-xl transition-all text-sm outline-none border border-transparent">
                                                                <button @click="answer(currentInput)" :disabled="!currentInput"
                                                                        class="w-10 h-10 rounded-xl text-white flex items-center justify-center transition-colors disabled:opacity-50"
                                                                        style="background: #6366f1;">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                                                </button>
                                                            </div>
                                                        </template>
                                                        <template x-if="questions[currentQIndex].type === 'select'">
                                                            <div class="flex flex-wrap gap-2">
                                                                <template x-for="opt in questions[currentQIndex].options">
                                                                    <button @click="answer(opt, opt)" class="px-3 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white rounded-xl text-sm font-medium transition-colors border border-indigo-100" x-text="opt"></button>
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>

                                            {{-- Hidden Form --}}
                                            <form action="{{ route('jobs.ai-followup', $job->id) }}" method="POST" id="followupForm_{{ $job->id }}" class="hidden">
                                                @csrf
                                                @foreach(['phone', 'years_experience', 'primary_role', 'key_skills', 'education_level', 'english_level', 'portfolio_url', 'github_url'] as $field)
                                                    <input type="hidden" name="followup_{{ $field }}" id="followup_{{ $field }}">
                                                @endforeach
                                            </form>
                                        </div>

                                        {{-- ═══ STEP 3: AI Result ═══ --}}
                                        <div x-show="step === 3" x-transition>
                                            @if($activeAdvisory && isset($activeAdvisory['fit_score']))
                                                @php
                                                    $score = $activeAdvisory['fit_score'];
                                                    if ($score >= 8) { $label = 'Xuất sắc'; $emoji = '🏆'; $color = '#10b981'; $bg = '#d1fae5'; }
                                                    elseif ($score >= 6.5) { $label = 'Tốt'; $emoji = '👍'; $color = '#3b82f6'; $bg = '#dbeafe'; }
                                                    elseif ($score >= 5) { $label = 'Khá'; $emoji = '✅'; $color = '#f59e0b'; $bg = '#fef3c7'; }
                                                    else { $label = 'Cần cải thiện'; $emoji = '💪'; $color = '#ef4444'; $bg = '#fee2e2'; }
                                                @endphp
                                                <div class="p-6">
                                                    <div class="text-center mb-4">
                                                        <div class="text-4xl mb-2">{{ $emoji }}</div>
                                                        <div class="flex items-baseline justify-center gap-1">
                                                            <span class="text-4xl font-black" style="color: {{ $color }};">{{ number_format($score, 1) }}</span>
                                                            <span class="text-xl font-bold text-gray-400">/10</span>
                                                        </div>
                                                        <p class="text-sm font-bold mt-1" style="color: {{ $color }};">{{ $label }}</p>
                                                    </div>

                                                    @if(!empty($activeAdvisory['matched_skills']))
                                                        <div class="p-3 rounded-xl border border-gray-100 mb-3">
                                                            <p class="text-xs font-semibold text-gray-500 mb-2">✅ Kỹ năng khớp</p>
                                                            <div class="flex flex-wrap gap-1">
                                                                @foreach($activeAdvisory['matched_skills'] as $skill)
                                                                    <span class="px-2 py-0.5 rounded-lg text-xs font-medium" style="background: #d1fae5; color: #065f46;">{{ $skill }}</span>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif

                                                    @if(!empty($activeAdvisory['missing_skills']))
                                                        <div class="p-3 rounded-xl border border-gray-100 mb-3">
                                                            <p class="text-xs font-semibold text-gray-500 mb-2">❌ Kỹ năng thiếu</p>
                                                            <div class="flex flex-wrap gap-1">
                                                                @foreach($activeAdvisory['missing_skills'] as $skill)
                                                                    <span class="px-2 py-0.5 rounded-lg text-xs font-medium" style="background: #fee2e2; color: #991b1b;">{{ $skill }}</span>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="p-6 text-center">
                                                    <div class="text-4xl mb-2">📝</div>
                                                    <p class="text-sm font-semibold text-gray-700">Đơn đã được ghi nhận</p>
                                                    <p class="text-xs text-gray-500 mt-1">Nhà tuyển dụng sẽ xem xét sớm.</p>
                                                </div>
                                            @endif

                                            <div class="px-6 pb-6 space-y-3">
                                                <a href="{{ route('candidate.applications') }}" class="inline-flex items-center justify-center w-full py-3 rounded-xl text-white font-semibold transition-all hover:scale-[1.02]" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                                                    Xem đơn ứng tuyển của tôi
                                                </a>
                                                <a href="{{ route('home') }}" class="inline-flex items-center justify-center w-full py-3 rounded-xl bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition-all">
                                                    Tìm việc khác
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif(Auth::user()->role === 'candidate')
                                <!-- Application Form (Candidate only) -->
                                <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 overflow-hidden" id="apply-form">
                                <!-- Form Header -->
                                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-6" style="background: linear-gradient(to right, #4f46e5, #9333ea);">
                                    <h2 class="text-xl font-bold text-white mb-1">Ứng tuyển ngay</h2>
                                    <p class="text-indigo-100 text-sm">Upload CV của bạn (PDF, DOC, DOCX) — AI sẽ tự động đọc và phân tích</p>
                                </div>

                                <!-- Form Body -->
                                <form action="{{ route('jobs.apply', $job->id) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-5" id="applyForm_{{ $job->id }}">
                                    @csrf

                                    @if($errors->any())
                                        <div class="p-4 bg-red-50 border border-red-200 rounded-2xl">
                                            <p class="text-sm font-semibold text-red-800 mb-2">Vui lòng kiểm tra lại thông tin:</p>
                                            <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                                                @foreach($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <input type="hidden" name="cv_mode" id="cv_mode_{{ $job->id }}" value="{{ old('cv_mode', 'upload') }}">

                                                                <!-- Full Name -->
                                                                <div>
                                                                        <label for="full_name_{{ $job->id }}" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Họ và tên <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           name="full_name" 
                                                                                     id="full_name_{{ $job->id }}" 
                                                                                     value="{{ old('full_name', Auth::user()->name ?? '') }}"
                                           class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all input-modern bg-gray-50 @error('full_name') border-red-400 @enderror"
                                           readonly
                                           required>
                                    @error('full_name')
                                        <p class="mt-2 text-sm text-red-500 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                                    <!-- Email -->
                                    <div>
                                        <label for="email_{{ $job->id }}" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Email <span class="text-red-500">*</span>
                                        </label>
                                        <input type="email" 
                                               name="email" 
                                               id="email_{{ $job->id }}" 
                                               value="{{ old('email', Auth::user()->email ?? '') }}"
                                               readonly
                                               class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all input-modern bg-gray-50 @error('email') border-red-400 @enderror"
                                               required>
                                        @error('email')
                                            <p class="mt-2 text-sm text-red-500 flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                <!-- Phone -->
                                <div>
                                    <label for="phone_{{ $job->id }}" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Số điện thoại
                                    </label>
                                    <input type="tel" 
                                           name="phone" 
                                           id="phone_{{ $job->id }}" 
                                               value="{{ old('phone', $currentCandidate->phone ?? '') }}"
                                           class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all input-modern"
                                           placeholder="0912 345 678">
                                </div>

                                                                                                <!-- CV Upload -->
                                <div id="cv_upload_section_{{ $job->id }}">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        CV của bạn <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative"
                                         x-data="{ dragging: false }"
                                         @dragover.prevent="dragging = true"
                                         @dragleave.prevent="dragging = false"
                                         @drop.prevent="dragging = false; if ($event.dataTransfer.files.length) { document.getElementById('cv_file_{{ $job->id }}').files = $event.dataTransfer.files; document.getElementById('cv_file_{{ $job->id }}').dispatchEvent(new Event('change')); }">
                                        <input type="file" 
                                               name="cv_file" 
                                               id="cv_file_{{ $job->id }}" 
                                               accept=".doc,.docx,.pdf"
                                               class="sr-only">
                                        <label for="cv_file_{{ $job->id }}" 
                                               :class="dragging ? 'border-indigo-500 bg-indigo-50/50 scale-[1.02]' : 'border-gray-300'"
                                               class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed rounded-2xl cursor-pointer hover:border-indigo-500 hover:bg-indigo-50/50 transition-all duration-300 @error('cv_file') border-red-400 @enderror">
                                            <div class="flex flex-col items-center justify-center pt-5 pb-6" id="upload-placeholder_{{ $job->id }}">
                                                <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center mb-3">
                                                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                                    </svg>
                                                </div>
                                                <p class="text-sm text-gray-600 font-medium">Kéo thả hoặc <span class="text-indigo-600">chọn file</span></p>
                                                <p class="text-xs text-gray-400 mt-1">PDF, DOC, DOCX (Max 5MB) — AI sẽ tự đọc nội dung</p>
                                            </div>
                                            <div class="hidden flex-col items-center justify-center pt-5 pb-6" id="upload-success_{{ $job->id }}">
                                                <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center mb-3">
                                                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </div>
                                                <p class="text-sm text-emerald-600 font-medium" id="file-name-display_{{ $job->id }}">File đã chọn</p>
                                            </div>
                                        </label>
                                    </div>
                                    @error('cv_file')
                                        <p class="mt-2 text-sm text-red-500 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                                                    <!-- Submit Button -->
                                    <button type="submit" 
                                            id="submitBtn_{{ $job->id }}"
                                            class="w-full py-4 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold text-lg hover:shadow-xl hover:shadow-indigo-500/30 hover:scale-[1.02] transition-all duration-300 shine flex items-center justify-center" style="background: linear-gradient(to right, #4f46e5, #9333ea);">
                                        <svg class="w-5 h-5 mr-2" id="submitIcon_{{ $job->id }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                        </svg>
                                        <svg class="animate-spin w-5 h-5 mr-2 hidden" id="submitSpinner_{{ $job->id }}" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span id="submitText_{{ $job->id }}">Gửi đơn ứng tuyển</span>
                                    </button>

                                    <!-- Scoring Loading State -->
                                    <div id="scoringProgress_{{ $job->id }}" class="hidden p-4 rounded-2xl bg-gradient-to-r from-purple-50 to-indigo-50 border-2 border-purple-200" style="background: linear-gradient(to right, #faf5ff, #eef2ff);">
                                        <div class="flex items-center gap-3">
                                            <svg class="animate-spin w-6 h-6 text-purple-600" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <div class="flex-1">
                                                <p class="font-bold text-purple-900">🤖 AI đang phân tích CV của bạn...</p>
                                                <p class="text-sm text-purple-700 mt-1">So khớp với yêu cầu công việc</p>
                                            </div>
                                        </div>
                                    </div>

                                <!-- Trust Badges -->
                                <div class="flex items-center justify-center space-x-4 pt-4 border-t border-gray-100">
                                    <div class="flex items-center text-gray-400 text-xs">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Bảo mật
                                    </div>
                                    <div class="flex items-center text-gray-400 text-xs">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        Xác thực
                                    </div>
                                </div>
                                </form>
                            </div>
                            @endif
                        @endguest
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- CV Builder Dialog -->
    <dialog id="cvDialog_{{ $job->id }}" class="rounded-3xl p-0 w-full max-w-2xl">
        <div class="bg-white rounded-3xl overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-5" style="background: linear-gradient(to right, #4f46e5, #9333ea);">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white">Tạo CV nhanh</h3>
                        <p class="text-indigo-100 text-sm">
                            @if($currentCandidate && !empty($currentCandidate->profile_data['cv_quick']))
                                ✅ Đã tự động điền từ hồ sơ của bạn
                            @else
                                Điền thông tin để nộp kèm đơn ứng tuyển
                            @endif
                        </p>
                    </div>
                    <button type="button" onclick="closeCvDialog_{{ $job->id }}()" class="w-10 h-10 rounded-xl bg-white/15 text-white hover:bg-white/25 transition-all">✕</button>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-5">
                    <h4 class="font-bold text-gray-900 mb-1">Thông tin cơ bản</h4>
                    <p class="text-sm text-gray-500 mb-4">Các trường Họ tên/SĐT/Email lấy từ form bên ngoài.</p>

                    <label class="block text-sm font-semibold text-gray-700 mb-2">Mô tả bản thân <span class="text-red-500">*</span></label>
                    <textarea id="selfDescriptionInput_{{ $job->id }}" rows="4" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none transition-all" placeholder="VD: Backend dev 2 năm, mạnh Laravel/MySQL, thích sản phẩm...">{{ old('self_description', $currentCandidate?->profile_data['cv_quick']['self_description'] ?? '') }}</textarea>
                    <p class="text-xs text-gray-500 mt-2">Gợi ý: 2-5 câu, nêu điểm mạnh, công nghệ, mục tiêu.</p>
                </div>

                <!-- Trình độ ngoại ngữ & Chứng chỉ -->
                <div class="rounded-2xl border border-gray-100 bg-blue-50/50 p-5">
                    <h4 class="font-bold text-gray-900 mb-1">📜 Ngoại ngữ & Chứng chỉ</h4>
                    <p class="text-sm text-gray-500 mb-4">Thông tin này giúp hệ thống đánh giá chính xác hơn</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Tiếng Anh -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                🇬🇧 Trình độ tiếng Anh
                            </label>
                            <select id="englishLevel_{{ $job->id }}" data-cert-field="english_level" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:outline-none">
                                <option value="">-- Chưa đánh giá --</option>
                                <option value="basic">Cơ bản (A1-A2)</option>
                                <option value="intermediate">Trung cấp (B1-B2)</option>
                                <option value="advanced">Nâng cao (C1-C2)</option>
                                <option value="native">Bản ngữ / Native</option>
                            </select>
                        </div>

                        <!-- TOEIC Score -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                📊 TOEIC (nếu có)
                            </label>
                            <input type="number" id="toeicScore_{{ $job->id }}" data-cert-field="toeic_score" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:outline-none" placeholder="VD: 850" min="0" max="990">
                            <p class="text-xs text-gray-500 mt-1">Điểm từ 0-990</p>
                        </div>

                        <!-- IELTS Score -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                📊 IELTS (nếu có)
                            </label>
                            <input type="number" id="ieltsScore_{{ $job->id }}" data-cert-field="ielts_score" step="0.5" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:outline-none" placeholder="VD: 7.0" min="0" max="9">
                            <p class="text-xs text-gray-500 mt-1">Điểm từ 0-9</p>
                        </div>

                        <!-- Chứng chỉ chuyên môn -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                🎓 Chứng chỉ chuyên môn
                            </label>
                            <select id="certifications_{{ $job->id }}" data-cert-field="certifications" multiple class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:outline-none" size="3">
                                <optgroup label="IT - Development">
                                    <option value="aws_certified">AWS Certified</option>
                                    <option value="google_cloud">Google Cloud Professional</option>
                                    <option value="microsoft_azure">Microsoft Azure</option>
                                    <option value="cisco_ccna">Cisco CCNA/CCNP</option>
                                    <option value="oracle_java">Oracle Java Certification</option>
                                    <option value="pmp">PMP - Project Management</option>
                                </optgroup>
                                <optgroup label="IT - Security">
                                    <option value="cissp">CISSP</option>
                                    <option value="ceh">CEH - Ethical Hacker</option>
                                    <option value="comptia_security">CompTIA Security+</option>
                                </optgroup>
                                <optgroup label="Marketing & Media">
                                    <option value="google_analytics">Google Analytics IQ</option>
                                    <option value="google_ads">Google Ads Certified</option>
                                    <option value="facebook_blueprint">Facebook Blueprint</option>
                                    <option value="hubspot">HubSpot Certification</option>
                                    <option value="adobe_certified">Adobe Certified Expert</option>
                                </optgroup>
                                <optgroup label="Khác">
                                    <option value="other">Chứng chỉ khác</option>
                                </optgroup>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Giữ Ctrl/Cmd để chọn nhiều</p>
                        </div>
                    </div>

                    <!-- Years of Experience -->
                    <div class="mt-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            ⏱️ Tổng số năm kinh nghiệm trong ngành
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="number" id="yearsOfExperience_{{ $job->id }}" data-cert-field="years_experience" class="flex-1 px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:outline-none" placeholder="VD: 3" min="0" max="50" step="0.5">
                            <span class="text-sm text-gray-600">năm</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Tổng thời gian làm việc thực tế trong lĩnh vực này</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h4 class="font-bold text-gray-900">Học vấn</h4>
                            <p class="text-sm text-gray-500">Nhập trường, loại bằng và năm tốt nghiệp/đạt được.</p>
                        </div>
                        <button type="button" onclick="addEducationRow_{{ $job->id }}()" class="px-4 py-2 rounded-xl bg-gray-900 text-white text-sm font-semibold hover:bg-black transition-all">+ Thêm học vấn</button>
                    </div>

                    <div id="educationRows_{{ $job->id }}" class="space-y-4"></div>
                    <p class="text-xs text-gray-500 mt-3">Ảnh minh chứng: JPG/PNG hoặc PDF (tối đa 5MB).</p>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h4 class="font-bold text-gray-900">Kinh nghiệm làm việc</h4>
                            <p class="text-sm text-gray-500">Tên công ty & vị trí tách riêng, có thể chọn “Đang làm việc tại đây”.</p>
                        </div>
                        <button type="button" onclick="addWorkRow_{{ $job->id }}()" class="px-4 py-2 rounded-xl bg-gray-900 text-white text-sm font-semibold hover:bg-black transition-all">+ Thêm vai trò</button>
                    </div>

                    <div id="workRows_{{ $job->id }}" class="space-y-4"></div>

                    <div class="mt-3 p-4 bg-white rounded-2xl border border-gray-100">
                        <p class="text-sm font-semibold text-gray-800">Gợi ý động từ mạnh</p>
                        <p class="text-xs text-gray-500 mt-1">Nhấn để chèn vào mô tả công việc trong từng vai trò.</p>
                        <div id="workVerbChips_{{ $job->id }}" class="mt-3 flex flex-wrap gap-2"></div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h4 class="font-bold text-gray-900">Kỹ năng</h4>
                            <p class="text-sm text-gray-500">Hard Skills (chuyên môn) & Soft Skills (kỹ năng mềm)</p>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" onclick="addSkillRow_{{ $job->id }}('hard')" class="px-4 py-2 rounded-xl bg-indigo-50 text-indigo-700 text-sm font-semibold hover:bg-indigo-600 hover:text-white transition-all">+ Hard</button>
                            <button type="button" onclick="addSkillRow_{{ $job->id }}('soft')" class="px-4 py-2 rounded-xl bg-purple-50 text-purple-700 text-sm font-semibold hover:bg-purple-600 hover:text-white transition-all">+ Soft</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div class="rounded-2xl bg-white border border-gray-200 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <p class="font-semibold text-gray-900">Hard Skills</p>
                                <span class="text-xs text-gray-500">VD: Laravel, SEO, Photoshop</span>
                            </div>
                            <div id="hardSkillRows_{{ $job->id }}" class="space-y-3"></div>
                            <div class="mt-3">
                                <p class="text-xs font-semibold text-gray-700 mb-2">💡 Gợi ý theo công việc:</p>
                                <div id="hardSkillChips_{{ $job->id }}" class="flex flex-wrap gap-2"></div>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-white border border-gray-200 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <p class="font-semibold text-gray-900">Soft Skills</p>
                                <span class="text-xs text-gray-500">VD: Giao tiếp, Làm việc nhóm</span>
                            </div>
                            <div id="softSkillRows_{{ $job->id }}" class="space-y-3"></div>
                            <div class="mt-3">
                                <p class="text-xs font-semibold text-gray-700 mb-2">💡 Gợi ý theo công việc:</p>
                                <div id="softSkillChips_{{ $job->id }}" class="flex flex-wrap gap-2"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-indigo-50 border border-indigo-200 rounded-xl">
                        <p class="text-sm text-indigo-900 font-semibold mb-1">📊 Mức độ thành thạo:</p>
                        <div class="text-xs text-indigo-700 space-y-1">
                            <div><span class="font-semibold">1 - Cơ bản:</span> Hiểu khái niệm, cần hướng dẫn</div>
                            <div><span class="font-semibold">2 - Khá:</span> Làm việc độc lập với task đơn giản</div>
                            <div><span class="font-semibold">3 - Thành thạo:</span> Xử lý hầu hết tình huống, ít cần hỗ trợ</div>
                            <div><span class="font-semibold">4 - Nâng cao:</span> Giải quyết vấn đề phức tạp, hướng dẫn người khác</div>
                            <div><span class="font-semibold">5 - Chuyên gia:</span> Master, đào tạo, research, đóng góp cộng đồng</div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="button" onclick="closeCvDialog_{{ $job->id }}()" class="px-5 py-3 rounded-xl bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition-all">Đóng</button>
                    <button type="button" onclick="saveCvDialog_{{ $job->id }}()" class="px-5 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold hover:shadow-lg transition-all">Lưu CV</button>
                </div>
            </div>
        </div>
    </dialog>

    <!-- JavaScript for CV mode + file upload -->
    <script>
        const cvDialog = document.getElementById('cvDialog_{{ $job->id }}');
        const applyForm = document.getElementById('applyForm_{{ $job->id }}');
        const cvModeInput = document.getElementById('cv_mode_{{ $job->id }}');
        const cvUploadSection = document.getElementById('cv_upload_section_{{ $job->id }}');
        const cvFormSection = document.getElementById('cv_form_section_{{ $job->id }}');
        const cvFileInput = document.getElementById('cv_file_{{ $job->id }}');

        let educationIndex_{{ $job->id }} = 0;
        let workIndex_{{ $job->id }} = 0;
        let hardSkillIndex_{{ $job->id }} = 0;
        let softSkillIndex_{{ $job->id }} = 0;

        // Handle form submission with loading state
        if (applyForm) {
            applyForm.addEventListener('submit', function(e) {
                const submitBtn = document.getElementById('submitBtn_{{ $job->id }}');
                const submitText = document.getElementById('submitText_{{ $job->id }}');
                const submitIcon = document.getElementById('submitIcon_{{ $job->id }}');
                const submitSpinner = document.getElementById('submitSpinner_{{ $job->id }}');
                const scoringProgress = document.getElementById('scoringProgress_{{ $job->id }}');

                if (submitBtn && submitText && submitIcon && submitSpinner && scoringProgress) {
                    // Disable button
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
                    
                    // Show spinner, hide icon
                    submitIcon.classList.add('hidden');
                    submitSpinner.classList.remove('hidden');
                    
                    // Update text
                    submitText.textContent = 'Đang gửi...';
                    
                    // Show scoring progress
                    scoringProgress.classList.remove('hidden');
                }
            });
        }

        const strongVerbs_{{ $job->id }} = [
            'Chủ trì',
            'Vận hành',
            'Tối ưu hóa',
            'Thiết kế',
            'Xây dựng',
            'Triển khai',
            'Cải tiến',
            'Tích hợp',
            'Tự động hóa',
            'Phân tích',
            'Đo lường',
            'Giám sát',
            'Điều phối',
            'Hướng dẫn',
            'Giải quyết',
            'Chuẩn hóa',
            'Nâng cấp',
        ];

        let lastWorkDescriptionEl_{{ $job->id }} = null;

        let hydratedCvDialog_{{ $job->id }} = false;

        const initialCvMode_{{ $job->id }} = @json(old('cv_mode', 'upload'));
        const hasFormErrors_{{ $job->id }} = @json($errors->has('self_description') || $errors->has('education_json') || $errors->has('education_proofs') || $errors->has('education_proofs.*') || $errors->has('work_experiences_json') || $errors->has('skills_json'));

        function insertAtCursor(textarea, textToInsert) {
            if (!textarea) return;
            textarea.focus();
            const start = textarea.selectionStart ?? textarea.value.length;
            const end = textarea.selectionEnd ?? textarea.value.length;
            const before = textarea.value.substring(0, start);
            const after = textarea.value.substring(end);
            const space = before.endsWith(' ') || before.length === 0 ? '' : ' ';
            textarea.value = before + space + textToInsert + ' ' + after;
            const caret = (before + space + textToInsert + ' ').length;
            textarea.setSelectionRange(caret, caret);
        }

        function renderVerbChips(containerEl, getTargetTextarea) {
            if (!containerEl) return;
            containerEl.innerHTML = '';
            strongVerbs_{{ $job->id }}.forEach((verb) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'px-3 py-1.5 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold hover:bg-indigo-600 hover:text-white transition-all';
                btn.textContent = verb;
                btn.addEventListener('click', () => insertAtCursor(getTargetTextarea?.(), verb));
                containerEl.appendChild(btn);
            });
        }

        function normalizeTitle(title) {
            return String(title ?? '').toLowerCase();
        }

        function getSkillSuggestions_{{ $job->id }}(sourceText) {
            const title = normalizeTitle(sourceText ?? @json($job->title));
            // Default suggestions
            let hard = ['HTML/CSS', 'JavaScript', 'Git', 'Microsoft Office'];
            let soft = ['Giao tiếp', 'Làm việc nhóm', 'Tư duy giải quyết vấn đề', 'Quản lý thời gian'];

            if (title.includes('event') || title.includes('organizer') || title.includes('sự kiện')) {
                hard = ['Quản lý ngân sách', 'Điều phối sự kiện', 'Đàm phán nhà cung cấp', 'Lập kế hoạch', 'Quản lý timeline', 'Tổ chức hậu cần'];
                soft = ['Giao tiếp', 'Đàm phán', 'Quản lý áp lực', 'Làm việc nhóm', 'Giải quyết vấn đề', 'Chủ động'];
            } else if (title.includes('marketing') || title.includes('content')) {
                hard = ['Content Planning', 'SEO/SEM', 'Google Analytics', 'Facebook Ads', 'Email Marketing', 'Copywriting'];
                soft = ['Sáng tạo', 'Giao tiếp', 'Tư duy dữ liệu', 'Chủ động', 'Làm việc nhóm'];
            } else if (title.includes('backend') || title.includes('php') || title.includes('laravel')) {
                hard = ['PHP', 'Laravel', 'REST API', 'MySQL', 'Redis', 'Docker', 'Git', 'Nginx'];
                soft = ['Tư duy phân tích', 'Giao tiếp', 'Làm việc nhóm', 'Chủ động', 'Tư duy hệ thống'];
            } else if (title.includes('frontend') || title.includes('react') || title.includes('vue')) {
                hard = ['JavaScript', 'TypeScript', 'React', 'Vue.js', 'HTML/CSS', 'Tailwind CSS', 'Webpack', 'Git'];
                soft = ['Tư duy UX', 'Giao tiếp', 'Làm việc nhóm', 'Chủ động', 'Chú ý chi tiết'];
            } else if (title.includes('fullstack') || title.includes('full-stack')) {
                hard = ['JavaScript', 'Node.js', 'React/Vue', 'MongoDB/MySQL', 'REST API', 'Git', 'Docker'];
                soft = ['Tư duy hệ thống', 'Giao tiếp', 'Làm việc nhóm', 'Tự học', 'Giải quyết vấn đề'];
            } else if (title.includes('mobile') || title.includes('ios') || title.includes('android') || title.includes('flutter')) {
                hard = ['Flutter', 'React Native', 'Swift', 'Kotlin', 'Firebase', 'REST API', 'Git'];
                soft = ['Tư duy UX', 'Chú ý chi tiết', 'Giao tiếp', 'Làm việc nhóm', 'Kiên nhẫn'];
            } else if (title.includes('devops') || title.includes('sysadmin')) {
                hard = ['Docker', 'Kubernetes', 'AWS/GCP', 'CI/CD', 'Linux', 'Terraform', 'Monitoring'];
                soft = ['Tư duy hệ thống', 'Giải quyết vấn đề', 'Làm việc áp lực', 'Giao tiếp', 'Tỉ mỉ'];
            } else if (title.includes('qa') || title.includes('tester') || title.includes('test')) {
                hard = ['Manual Testing', 'Automation Testing', 'Selenium', 'API Testing', 'SQL', 'JIRA', 'Test Case'];
                soft = ['Tỉ mỉ', 'Kiên nhẫn', 'Tư duy logic', 'Giao tiếp', 'Làm việc nhóm'];
            } else if (title.includes('data') || title.includes('analyst')) {
                hard = ['SQL', 'Python', 'Excel', 'Power BI', 'Tableau', 'Data Analysis', 'Statistics'];
                soft = ['Tư duy phân tích', 'Giao tiếp', 'Tỉ mỉ', 'Tư duy logic', 'Trình bày'];
            } else if (title.includes('machine learning') || title.includes('ml')) {
                hard = ['Python', 'TensorFlow', 'PyTorch', 'Machine Learning', 'Deep Learning', 'NLP', 'Computer Vision'];
                soft = ['Tư duy nghiên cứu', 'Kiên nhẫn', 'Tự học', 'Giao tiếp', 'Tư duy logic'];
            } else if (title.includes('designer') || title.includes('ui') || title.includes('ux')) {
                hard = ['Figma', 'Adobe XD', 'Sketch', 'Photoshop', 'UI Design', 'UX Research', 'Prototyping'];
                soft = ['Sáng tạo', 'Giao tiếp', 'Đồng cảm', 'Chú ý chi tiết', 'Tư duy UX'];
            } else if (title.includes('graphic') || title.includes('design')) {
                hard = ['Photoshop', 'Illustrator', 'InDesign', 'After Effects', 'CorelDRAW', 'Canva'];
                soft = ['Sáng tạo', 'Chú ý chi tiết', 'Quản lý thời gian', 'Giao tiếp', 'Làm việc áp lực'];
            } else if (title.includes('social media') || title.includes('community')) {
                hard = ['Facebook Ads Manager', 'Content Planning', 'Canva', 'Analytics', 'Community Management'];
                soft = ['Giao tiếp', 'Sáng tạo', 'Đồng cảm', 'Chủ động', 'Xử lý tình huống'];
            } else if (title.includes('seo')) {
                hard = ['SEO On-page', 'SEO Off-page', 'Google Analytics', 'Google Search Console', 'Keyword Research', 'Link Building'];
                soft = ['Tư duy phân tích', 'Kiên nhẫn', 'Tỉ mỉ', 'Tự học', 'Giao tiếp'];
            } else if (title.includes('video') || title.includes('editor')) {
                hard = ['Premiere Pro', 'After Effects', 'Final Cut Pro', 'DaVinci Resolve', 'Video Editing', 'Color Grading'];
                soft = ['Sáng tạo', 'Chú ý chi tiết', 'Kiên nhẫn', 'Quản lý thời gian', 'Giao tiếp'];
            } else if (title.includes('pr') || title.includes('public relations')) {
                hard = ['Press Release', 'Media Relations', 'Crisis Management', 'Event Management', 'Content Writing'];
                soft = ['Giao tiếp', 'Đàm phán', 'Xử lý tình huống', 'Làm việc áp lực', 'Networking'];
            }

            return { hard, soft };
        }

        function openCvDialog_{{ $job->id }}() {
            if (cvModeInput) cvModeInput.value = 'form';
            if (cvUploadSection) cvUploadSection.classList.add('hidden');
            if (cvFormSection) cvFormSection.classList.remove('hidden');
            if (cvDialog && typeof cvDialog.showModal === 'function') {
                cvDialog.showModal();
            }

            if (!hydratedCvDialog_{{ $job->id }}) {
                hydrateCvDialogFromHidden_{{ $job->id }}();
                hydratedCvDialog_{{ $job->id }} = true;
            }

            if (document.getElementById('educationRows_{{ $job->id }}')?.children?.length === 0) addEducationRow_{{ $job->id }}();
            if (document.getElementById('workRows_{{ $job->id }}')?.children?.length === 0) addWorkRow_{{ $job->id }}();
            if (document.getElementById('hardSkillRows_{{ $job->id }}')?.children?.length === 0) addSkillRow_{{ $job->id }}('hard');
            if (document.getElementById('softSkillRows_{{ $job->id }}')?.children?.length === 0) addSkillRow_{{ $job->id }}('soft');
        }

        function safeJsonParse_{{ $job->id }}(text, fallback) {
            const raw = (text ?? '').toString().trim();
            if (raw === '') return fallback;
            try {
                return JSON.parse(raw);
            } catch (e) {
                return fallback;
            }
        }

        function hydrateCvDialogFromHidden_{{ $job->id }}() {
            // Self description
            const hiddenSelf = document.getElementById('self_description_{{ $job->id }}')?.value ?? '';
            const selfEl = document.getElementById('selfDescriptionInput_{{ $job->id }}');
            if (selfEl && (selfEl.value ?? '').trim() === '' && (hiddenSelf ?? '').trim() !== '') {
                selfEl.value = hiddenSelf;
            }

            // Certifications & English
            const certHidden = document.getElementById('certifications_json_{{ $job->id }}')?.value ?? '';
            const certData = safeJsonParse_{{ $job->id }}(certHidden, {});
            
            const englishEl = document.getElementById('englishLevel_{{ $job->id }}');
            if (englishEl && certData?.english_level) englishEl.value = certData.english_level;
            
            const toeicEl = document.getElementById('toeicScore_{{ $job->id }}');
            if (toeicEl && certData?.toeic_score) toeicEl.value = certData.toeic_score;
            
            const ieltsEl = document.getElementById('ieltsScore_{{ $job->id }}');
            if (ieltsEl && certData?.ielts_score) ieltsEl.value = certData.ielts_score;
            
            const yearsEl = document.getElementById('yearsOfExperience_{{ $job->id }}');
            if (yearsEl && certData?.years_experience) yearsEl.value = certData.years_experience;
            
            const certsSelect = document.getElementById('certifications_{{ $job->id }}');
            if (certsSelect && Array.isArray(certData?.certifications)) {
                Array.from(certsSelect.options).forEach(opt => {
                    opt.selected = certData.certifications.includes(opt.value);
                });
            }

            // Education
            const eduHidden = document.getElementById('education_json_{{ $job->id }}')?.value ?? '';
            const edu = safeJsonParse_{{ $job->id }}(eduHidden, []);
            const eduContainer = document.getElementById('educationRows_{{ $job->id }}');
            if (eduContainer) {
                eduContainer.innerHTML = '';
                educationIndex_{{ $job->id }} = 0;
                if (Array.isArray(edu) && edu.length > 0) {
                    edu.forEach((item) => {
                        addEducationRow_{{ $job->id }}();
                        const row = eduContainer.lastElementChild;
                        if (!row) return;
                        const setField = (field, value) => {
                            const el = row.querySelector(`[data-field="${field}"]`);
                            if (!el) return;
                            
                            // For select elements, check if value exists in options
                            if (el.tagName === 'SELECT') {
                                const optExists = Array.from(el.options).some(opt => opt.value === value);
                                if (optExists) {
                                    el.value = value ?? '';
                                } else if (value) {
                                    // Value not in options - add it dynamically
                                    const newOpt = document.createElement('option');
                                    newOpt.value = value;
                                    newOpt.textContent = value;
                                    el.appendChild(newOpt);
                                    el.value = value;
                                }
                            } else {
                                el.value = value ?? '';
                            }
                        };
                        setField('school', item?.school ?? '');
                        setField('degree_level', item?.degree_level ?? '');
                        setField('major', item?.major ?? '');
                        setField('graduation_year', item?.graduation_year ?? '');
                    });
                }
            }

            // Work experiences
            const workHidden = document.getElementById('work_experiences_json_{{ $job->id }}')?.value ?? '';
            const work = safeJsonParse_{{ $job->id }}(workHidden, []);
            const workContainer = document.getElementById('workRows_{{ $job->id }}');
            if (workContainer) {
                workContainer.innerHTML = '';
                workIndex_{{ $job->id }} = 0;
                if (Array.isArray(work) && work.length > 0) {
                    work.forEach((item) => {
                        addWorkRow_{{ $job->id }}();
                        const row = workContainer.lastElementChild;
                        if (!row) return;
                        const setWorkField = (field, value) => {
                            const el = row.querySelector(`[data-wfield="${field}"]`);
                            if (!el) return;
                            
                            // For select elements, check if value exists in options
                            if (el.tagName === 'SELECT') {
                                const optExists = Array.from(el.options).some(opt => opt.value === value);
                                if (optExists) {
                                    el.value = value ?? '';
                                } else if (value) {
                                    // Value not in options - add it dynamically
                                    const newOpt = document.createElement('option');
                                    newOpt.value = value;
                                    newOpt.textContent = value;
                                    el.appendChild(newOpt);
                                    el.value = value;
                                }
                            } else {
                                el.value = value ?? '';
                            }
                        };
                        setWorkField('company_name', item?.company_name ?? '');
                        setWorkField('position_title', item?.position_title ?? '');
                        setWorkField('start_date', item?.start_date ?? '');
                        setWorkField('end_date', item?.end_date ?? '');
                        setWorkField('description', item?.description ?? '');

                        const currentCb = row.querySelector('[data-wcurrent]');
                        if (currentCb) {
                            currentCb.checked = !!item?.is_current;
                            currentCb.dispatchEvent(new Event('change'));
                        }
                    });
                }
            }

            // Skills
            const skillsHidden = document.getElementById('skills_json_{{ $job->id }}')?.value ?? '';
            const skills = safeJsonParse_{{ $job->id }}(skillsHidden, { hard: [], soft: [] });
            const hardBox = document.getElementById('hardSkillRows_{{ $job->id }}');
            const softBox = document.getElementById('softSkillRows_{{ $job->id }}');

            if (hardBox) {
                hardBox.innerHTML = '';
                hardSkillIndex_{{ $job->id }} = 0;
                const items = Array.isArray(skills?.hard) ? skills.hard : [];
                items.forEach((s) => addSkillRow_{{ $job->id }}('hard', s?.name ?? '', String(s?.level ?? '3')));
            }
            if (softBox) {
                softBox.innerHTML = '';
                softSkillIndex_{{ $job->id }} = 0;
                const items = Array.isArray(skills?.soft) ? skills.soft : [];
                items.forEach((s) => addSkillRow_{{ $job->id }}('soft', s?.name ?? '', String(s?.level ?? '3')));
            }
        }

        function closeCvDialog_{{ $job->id }}() {
            if (cvDialog && typeof cvDialog.close === 'function') cvDialog.close();
        }

        function setCvMode_{{ $job->id }}(mode) {
            if (!cvModeInput) return;
            cvModeInput.value = mode;
            if (mode === 'upload') {
                if (cvUploadSection) cvUploadSection.classList.remove('hidden');
                if (cvFormSection) cvFormSection.classList.add('hidden');
            } else {
                if (cvUploadSection) cvUploadSection.classList.add('hidden');
                if (cvFormSection) cvFormSection.classList.remove('hidden');
            }
        }

        function addEducationRow_{{ $job->id }}() {
            const container = document.getElementById('educationRows_{{ $job->id }}');
            if (!container) return;
            const idx = educationIndex_{{ $job->id }}++;

            const row = document.createElement('div');
            row.className = 'rounded-2xl bg-white border border-gray-200 p-4';
            row.dataset.idx = String(idx);
            row.innerHTML = `
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Trường <span class="text-red-500">*</span></label>
                            <select class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none" data-field="school">
                                <option value="">-- Chọn trường --</option>
                                <optgroup label="Đại học Công nghệ">
                                    <option value="Đại học Bách Khoa Hà Nội">ĐH Bách Khoa Hà Nội</option>
                                    <option value="Đại học Bách Khoa TP.HCM">ĐH Bách Khoa TP.HCM</option>
                                    <option value="Đại học Công nghệ - ĐHQGHN">ĐH Công nghệ - ĐHQGHN</option>
                                    <option value="Đại học FPT">ĐH FPT</option>
                                    <option value="Đại học RMIT">ĐH RMIT</option>
                                    <option value="Đại học Duy Tân">ĐH Duy Tân</option>
                                    <option value="Đại học Văn Lang">ĐH Văn Lang</option>
                                </optgroup>
                                <optgroup label="Đại học Kinh tế">
                                    <option value="Đại học Kinh tế Quốc dân">ĐH Kinh tế Quốc dân</option>
                                    <option value="Đại học Kinh tế TP.HCM">ĐH Kinh tế TP.HCM</option>
                                    <option value="Đại học Ngoại thương">ĐH Ngoại thương</option>
                                    <option value="Đại học Tôn Đức Thắng">ĐH Tôn Đức Thắng</option>
                                </optgroup>
                                <optgroup label="Đại học Tổng hợp">
                                    <option value="Đại học Quốc gia Hà Nội">ĐH Quốc gia Hà Nội</option>
                                    <option value="Đại học Quốc gia TP.HCM">ĐH Quốc gia TP.HCM</option>
                                    <option value="Đại học Khoa học Tự nhiên">ĐH Khoa học Tự nhiên</option>
                                    <option value="Đại học Khoa học Xã hội & Nhân văn">ĐH KHXH & NV</option>
                                </optgroup>
                                <optgroup label="Đại học Truyền thông">
                                    <option value="Đại học Báo chí & Tuyên truyền">ĐH Báo chí & Tuyên truyền</option>
                                    <option value="Học viện Báo chí & Tuyên truyền">HV Báo chí & Tuyên truyền</option>
                                </optgroup>
                                <optgroup label="Đại học Quốc tế">
                                    <option value="Đại học Quốc gia Singapore (NUS)">NUS Singapore</option>
                                    <option value="Massachusetts Institute of Technology (MIT)">MIT</option>
                                    <option value="Stanford University">Stanford</option>
                                    <option value="University of Cambridge">Cambridge</option>
                                    <option value="University of Oxford">Oxford</option>
                                    <option value="Carnegie Mellon University">CMU</option>
                                    <option value="ETH Zurich">ETH Zurich</option>
                                </optgroup>
                                <optgroup label="Cao đẳng / Khác">
                                    <option value="Cao đẳng FPT Polytechnic">CĐ FPT Polytechnic</option>
                                    <option value="Arena Multimedia">Arena Multimedia</option>
                                    <option value="Trường khác">Trường khác</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Loại bằng <span class="text-red-500">*</span></label>
                            <select class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none" data-field="degree_level">
                                <option value="">-- Chọn bậc học --</option>
                                <option value="trung_cap">Trung cấp</option>
                                <option value="cao_dang">Cao đẳng</option>
                                <option value="cu_nhan">Cử nhân (Đại học)</option>
                                <option value="ky_su">Kỹ sư (Đại học)</option>
                                <option value="thac_si">Thạc sĩ</option>
                                <option value="tien_si">Tiến sĩ</option>
                                <option value="bootcamp">Bootcamp / Tự học</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Ngành / Chuyên ngành <span class="text-red-500">*</span></label>
                            <select class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none" data-field="major">
                                <option value="">-- Chọn ngành --</option>
                                <optgroup label="Công nghệ thông tin">
                                    <option value="Công nghệ thông tin">Công nghệ thông tin</option>
                                    <option value="Khoa học máy tính">Khoa học máy tính</option>
                                    <option value="Kỹ thuật phần mềm">Kỹ thuật phần mềm</option>
                                    <option value="Hệ thống thông tin">Hệ thống thông tin</option>
                                    <option value="An ninh mạng">An ninh mạng</option>
                                    <option value="Trí tuệ nhân tạo">Trí tuệ nhân tạo</option>
                                    <option value="Khoa học dữ liệu">Khoa học dữ liệu</option>
                                </optgroup>
                                <optgroup label="Marketing & Truyền thông">
                                    <option value="Marketing">Marketing</option>
                                    <option value="Marketing số">Marketing số</option>
                                    <option value="Quảng cáo">Quảng cáo</option>
                                    <option value="Quan hệ công chúng (PR)">Quan hệ công chúng (PR)</option>
                                    <option value="Báo chí">Báo chí</option>
                                    <option value="Truyền thông đa phương tiện">Truyền thông đa phương tiện</option>
                                    <option value="Thiết kế đồ họa">Thiết kế đồ họa</option>
                                    <option value="Thiết kế thời trang">Thiết kế thời trang</option>
                                </optgroup>
                                <optgroup label="Kinh tế & Quản trị">
                                    <option value="Quản trị kinh doanh">Quản trị kinh doanh</option>
                                    <option value="Kinh tế">Kinh tế</option>
                                    <option value="Tài chính - Ngân hàng">Tài chính - Ngân hàng</option>
                                    <option value="Kế toán - Kiểm toán">Kế toán - Kiểm toán</option>
                                    <option value="Quản trị nhân lực">Quản trị nhân lực</option>
                                </optgroup>
                                <optgroup label="Khác">
                                    <option value="Ngôn ngữ Anh">Ngôn ngữ Anh</option>
                                    <option value="Khác">Ngành khác</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Năm tốt nghiệp / đạt bằng <span class="text-red-500">*</span></label>
                            <input type="number" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none" placeholder="${new Date().getFullYear()}" data-field="graduation_year" min="1950" max="${new Date().getFullYear() + 10}">
                            <p class="text-xs text-gray-500 mt-1">Năm tốt nghiệp (1950-${new Date().getFullYear() + 10})</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Ảnh minh chứng <span class="text-gray-400 font-normal">(tùy chọn)</span></label>
                            <input type="file" name="education_proofs[]" form="applyForm_{{ $job->id }}" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-600 hover:file:text-white">
                        </div>
                    </div>
                    <button type="button" class="px-3 py-2 rounded-xl bg-gray-100 text-gray-600 hover:bg-red-600 hover:text-white transition-all" onclick="removeEducationRow_{{ $job->id }}(this)">Xóa</button>
                </div>
            `;

            container.appendChild(row);
        }

        function removeEducationRow_{{ $job->id }}(btn) {
            const row = btn?.closest('[data-idx]');
            if (row) row.remove();
        }

        function addWorkRow_{{ $job->id }}() {
            const container = document.getElementById('workRows_{{ $job->id }}');
            if (!container) return;
            const idx = workIndex_{{ $job->id }}++;

            const row = document.createElement('div');
            row.className = 'rounded-2xl bg-white border border-gray-200 p-4';
            row.dataset.widx = String(idx);
            row.innerHTML = `
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tên công ty <span class="text-red-500">*</span></label>
                            <input type="text" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none" placeholder="VD: FPT Software" data-wfield="company_name">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Vị trí / Chức vụ <span class="text-red-500">*</span></label>
                            <select class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none" data-wfield="position_title">
                                <option value="">-- Chọn vị trí --</option>
                                <optgroup label="IT - Development">
                                    <option value="Intern Developer">Intern Developer</option>
                                    <option value="Junior Frontend Developer">Junior Frontend Developer</option>
                                    <option value="Junior Backend Developer">Junior Backend Developer</option>
                                    <option value="Frontend Developer">Frontend Developer</option>
                                    <option value="Backend Developer">Backend Developer</option>
                                    <option value="Full-stack Developer">Full-stack Developer</option>
                                    <option value="Senior Frontend Developer">Senior Frontend Developer</option>
                                    <option value="Senior Backend Developer">Senior Backend Developer</option>
                                    <option value="Senior Full-stack Developer">Senior Full-stack Developer</option>
                                    <option value="Tech Lead">Tech Lead</option>
                                    <option value="Tech Lead / Senior Backend Developer">Tech Lead / Senior Backend Developer</option>
                                    <option value="Tech Lead / Senior Frontend Developer">Tech Lead / Senior Frontend Developer</option>
                                    <option value="Team Leader">Team Leader</option>
                                    <option value="Software Architect">Software Architect</option>
                                    <option value="Engineering Manager">Engineering Manager</option>
                                    <option value="CTO">CTO (Chief Technology Officer)</option>
                                </optgroup>
                                <optgroup label="IT - Mobile & QA">
                                    <option value="Mobile Developer">Mobile Developer</option>
                                    <option value="iOS Developer">iOS Developer</option>
                                    <option value="Android Developer">Android Developer</option>
                                    <option value="QA Engineer">QA Engineer</option>
                                    <option value="QA Tester">QA Tester</option>
                                    <option value="QA Lead">QA Lead</option>
                                </optgroup>
                                <optgroup label="IT - DevOps & Data">
                                    <option value="DevOps Engineer">DevOps Engineer</option>
                                    <option value="System Administrator">System Administrator</option>
                                    <option value="Data Analyst">Data Analyst</option>
                                    <option value="Data Engineer">Data Engineer</option>
                                    <option value="Data Scientist">Data Scientist</option>
                                    <option value="ML Engineer">ML Engineer</option>
                                </optgroup>
                                <optgroup label="IT - Design & Security">
                                    <option value="UI/UX Designer">UI/UX Designer</option>
                                    <option value="Product Designer">Product Designer</option>
                                    <option value="Security Engineer">Security Engineer</option>
                                    <option value="Network Engineer">Network Engineer</option>
                                </optgroup>
                                <optgroup label="Marketing & Content">
                                    <option value="Marketing Intern">Marketing Intern</option>
                                    <option value="Digital Marketing Executive">Digital Marketing Executive</option>
                                    <option value="Digital Marketing Specialist">Digital Marketing Specialist</option>
                                    <option value="Digital Marketing Manager">Digital Marketing Manager</option>
                                    <option value="Content Writer">Content Writer</option>
                                    <option value="Content Marketing Specialist">Content Marketing Specialist</option>
                                    <option value="Content Marketing Manager">Content Marketing Manager</option>
                                    <option value="SEO Specialist">SEO Specialist</option>
                                    <option value="SEO Manager">SEO Manager</option>
                                </optgroup>
                                <optgroup label="Social Media & PR">
                                    <option value="Social Media Executive">Social Media Executive</option>
                                    <option value="Social Media Manager">Social Media Manager</option>
                                    <option value="Community Manager">Community Manager</option>
                                    <option value="PR Executive">PR Executive</option>
                                    <option value="PR Manager">PR Manager</option>
                                    <option value="Communications Manager">Communications Manager</option>
                                </optgroup>
                                <optgroup label="Creative & Design">
                                    <option value="Graphic Designer">Graphic Designer</option>
                                    <option value="Senior Graphic Designer">Senior Graphic Designer</option>
                                    <option value="Art Director">Art Director</option>
                                    <option value="Video Editor">Video Editor</option>
                                    <option value="Motion Graphics Designer">Motion Graphics Designer</option>
                                    <option value="Photographer">Photographer</option>
                                </optgroup>
                                <optgroup label="Business & Management">
                                    <option value="Business Analyst">Business Analyst</option>
                                    <option value="Product Manager">Product Manager</option>
                                    <option value="Project Manager">Project Manager</option>
                                    <option value="Scrum Master">Scrum Master</option>
                                    <option value="Account Manager">Account Manager</option>
                                    <option value="Sales Executive">Sales Executive</option>
                                </optgroup>
                                <optgroup label="Khác">
                                    <option value="Freelancer">Freelancer</option>
                                    <option value="Consultant">Consultant</option>
                                    <option value="Other">Vị trí khác</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Ngày bắt đầu <span class="text-red-500">*</span></label>
                            <input type="date" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none" data-wfield="start_date">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Ngày kết thúc</label>
                            <input type="date" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none" data-wfield="end_date">
                            <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-600">
                                <input type="checkbox" class="w-4 h-4" data-wcurrent>
                                Đang làm việc tại đây
                            </label>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Mô tả công việc / Thành tựu</label>
                            <textarea rows="3" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none" placeholder="VD: Chủ trì vận hành..., tối ưu hóa..." data-wfield="description"></textarea>
                        </div>
                    </div>
                    <button type="button" class="px-3 py-2 rounded-xl bg-gray-100 text-gray-600 hover:bg-red-600 hover:text-white transition-all" onclick="removeWorkRow_{{ $job->id }}(this)">Xóa</button>
                </div>
            `;

            // Track focused description so verb chips insert correctly
            const descEl = row.querySelector('[data-wfield="description"]');
            descEl?.addEventListener('focus', () => {
                lastWorkDescriptionEl_{{ $job->id }} = descEl;
            });
            descEl?.addEventListener('click', () => {
                lastWorkDescriptionEl_{{ $job->id }} = descEl;
            });

            // Update skill suggestion chips based on typed position title
            const positionEl = row.querySelector('[data-wfield="position_title"]');
            const updateSuggestionsFromPosition = () => {
                const text = positionEl?.value ?? '';
                renderSkillChips_{{ $job->id }}(text);
            };
            positionEl?.addEventListener('input', updateSuggestionsFromPosition);
            positionEl?.addEventListener('change', updateSuggestionsFromPosition);

            // Toggle end date disabled
            const endDateInput = row.querySelector('[data-wfield="end_date"]');
            const currentCb = row.querySelector('[data-wcurrent]');
            const applyCurrent = () => {
                const checked = !!currentCb?.checked;
                if (endDateInput) {
                    endDateInput.disabled = checked;
                    if (checked) endDateInput.value = '';
                }
            };
            currentCb?.addEventListener('change', applyCurrent);
            applyCurrent();

            container.appendChild(row);
        }

        function removeWorkRow_{{ $job->id }}(btn) {
            const row = btn?.closest('[data-widx]');
            if (row) row.remove();
        }

        function levelOptionsHtml(selected) {
            const sel = String(selected ?? '3');
            const labels = {
                '1': '1 - Cơ bản',
                '2': '2 - Khá',
                '3': '3 - Thành thạo',
                '4': '4 - Nâng cao',
                '5': '5 - Chuyên gia',
            };
            return ['1','2','3','4','5'].map(v => `<option value="${v}" ${sel === v ? 'selected' : ''}>${labels[v]}</option>`).join('');
        }

        function addSkillRow_{{ $job->id }}(type, presetName = '', presetLevel = '3') {
            const container = document.getElementById((type === 'hard' ? 'hardSkillRows_' : 'softSkillRows_') + '{{ $job->id }}');
            if (!container) return;
            const idx = (type === 'hard') ? (hardSkillIndex_{{ $job->id }}++) : (softSkillIndex_{{ $job->id }}++);

            const row = document.createElement('div');
            row.className = 'flex items-center gap-2';
            row.dataset.stype = type;
            row.dataset.sidx = String(idx);
            row.innerHTML = `
                <input type="text" class="flex-1 px-3 py-2 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none text-sm" placeholder="VD: React, Quản lý ngân sách" data-sfield="name" value="${presetName.replace(/"/g, '&quot;')}">
                <select class="px-3 py-2 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none text-sm" data-sfield="level">
                    ${levelOptionsHtml(presetLevel)}
                </select>
                <button type="button" class="px-3 py-2 rounded-xl bg-gray-100 text-gray-600 hover:bg-red-600 hover:text-white transition-all" onclick="removeSkillRow_{{ $job->id }}(this)">Xóa</button>
            `;
            container.appendChild(row);
        }

        function removeSkillRow_{{ $job->id }}(btn) {
            const row = btn?.closest('[data-sidx]');
            if (row) row.remove();
        }

        function renderSkillChips_{{ $job->id }}(sourceText) {
            const suggestions = getSkillSuggestions_{{ $job->id }}(sourceText);
            const hardBox = document.getElementById('hardSkillChips_{{ $job->id }}');
            const softBox = document.getElementById('softSkillChips_{{ $job->id }}');
            const render = (box, items, type) => {
                if (!box) return;
                box.innerHTML = '';
                items.forEach((name) => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 text-xs font-semibold hover:bg-gray-900 hover:text-white transition-all';
                    btn.textContent = name;
                    btn.addEventListener('click', () => addSkillRow_{{ $job->id }}(type, name, '3'));
                    box.appendChild(btn);
                });
            };
            render(hardBox, suggestions.hard ?? [], 'hard');
            render(softBox, suggestions.soft ?? [], 'soft');
        }

        function saveCvDialog_{{ $job->id }}(closeAfterSave = true) {
            console.log('[saveCvDialog] Function called, closeAfterSave:', closeAfterSave);
            
            const selfDesc = document.getElementById('selfDescriptionInput_{{ $job->id }}')?.value ?? '';
            
            // Certifications - collect from dialog or fallback to hidden input
            const englishLevel = document.getElementById('englishLevel_{{ $job->id }}')?.value ?? '';
            const toeicScore = document.getElementById('toeicScore_{{ $job->id }}')?.value ?? '';
            const ieltsScore = document.getElementById('ieltsScore_{{ $job->id }}')?.value ?? '';
            const yearsExp = document.getElementById('yearsOfExperience_{{ $job->id }}')?.value ?? '';
            const certsSelect = document.getElementById('certifications_{{ $job->id }}');
            const selectedCerts = certsSelect ? Array.from(certsSelect.selectedOptions).map(opt => opt.value) : [];
            
            let certifications = {
                english_level: englishLevel,
                toeic_score: toeicScore !== '' ? parseFloat(toeicScore) : null,
                ielts_score: ieltsScore !== '' ? parseFloat(ieltsScore) : null,
                years_experience: yearsExp !== '' ? parseFloat(yearsExp) : null,
                certifications: selectedCerts,
            };
            
            // If dialog closed and all cert fields are empty, use existing data
            const hasCertData = englishLevel || toeicScore || ieltsScore || yearsExp || selectedCerts.length > 0;
            if (!hasCertData) {
                const existingCerts = document.getElementById('certifications_json_{{ $job->id }}')?.value;
                if (existingCerts && existingCerts !== '{"english_level":null,"toeic_score":null,"ielts_score":null,"years_experience":null,"certifications":[]}') {
                    try {
                        certifications = JSON.parse(existingCerts);
                        console.log('[saveCvDialog] Using existing certifications from hidden input');
                    } catch (e) {
                        console.error('[saveCvDialog] Failed to parse existing certifications:', e);
                    }
                }
            }
            
            console.log('[saveCvDialog] Certifications:', certifications);
            
            const rows = Array.from(document.querySelectorAll('#educationRows_{{ $job->id }} [data-idx]'));
            console.log('[saveCvDialog] Found education rows:', rows.length);

            let education = [];
            if (rows.length > 0) {
                // Dialog is open, collect from DOM
                education = rows.map((row) => {
                    const get = (field) => (row.querySelector(`[data-field="${field}"]`)?.value ?? '').trim();
                    return {
                        school: get('school'),
                        degree_level: get('degree_level'),
                        major: get('major'),
                        graduation_year: get('graduation_year'),
                    };
                });
            } else {
                // Dialog is closed, use existing hidden input data
                const existingEdu = document.getElementById('education_json_{{ $job->id }}')?.value;
                if (existingEdu && existingEdu !== '[]') {
                    try {
                        education = JSON.parse(existingEdu);
                        console.log('[saveCvDialog] Using existing education data from hidden input');
                    } catch (e) {
                        console.error('[saveCvDialog] Failed to parse existing education:', e);
                    }
                }
            }
            console.log('[saveCvDialog] Education data:', education);

            const workRows = Array.from(document.querySelectorAll('#workRows_{{ $job->id }} [data-widx]'));
            console.log('[saveCvDialog] Found work rows:', workRows.length);
            
            let work = [];
            if (workRows.length > 0) {
                // Dialog is open, collect from DOM
                work = workRows.map((row) => {
                    const get = (field) => (row.querySelector(`[data-wfield="${field}"]`)?.value ?? '').trim();
                    const isCurrent = row.querySelector('[data-wcurrent]')?.checked ?? false;
                    return {
                        company_name: get('company_name'),
                        position_title: get('position_title'),
                        start_date: get('start_date'),
                        end_date: isCurrent ? null : get('end_date'),
                        is_current: isCurrent,
                        description: get('description'),
                    };
                });
            } else {
                // Dialog is closed, use existing hidden input data
                const existingWork = document.getElementById('work_experiences_json_{{ $job->id }}')?.value;
                if (existingWork && existingWork !== '[]') {
                    try {
                        work = JSON.parse(existingWork);
                        console.log('[saveCvDialog] Using existing work data from hidden input');
                    } catch (e) {
                        console.error('[saveCvDialog] Failed to parse existing work:', e);
                    }
                }
            }

            const hardSkillRows = Array.from(document.querySelectorAll('#hardSkillRows_{{ $job->id }} [data-sidx]'));
            const softSkillRows = Array.from(document.querySelectorAll('#softSkillRows_{{ $job->id }} [data-sidx]'));
            console.log('[saveCvDialog] Found skill rows - hard:', hardSkillRows.length, 'soft:', softSkillRows.length);
            
            const toSkills = (rows) => rows.map((row) => {
                const name = (row.querySelector('[data-sfield="name"]')?.value ?? '').trim();
                const level = (row.querySelector('[data-sfield="level"]')?.value ?? '').trim();
                return { name, level };
            });

            let skills = {
                hard: toSkills(hardSkillRows),
                soft: toSkills(softSkillRows),
            };
            
            // If dialog is closed and skills are empty, use existing data
            if (skills.hard.length === 0 && skills.soft.length === 0) {
                const existingSkills = document.getElementById('skills_json_{{ $job->id }}')?.value;
                if (existingSkills && existingSkills !== '{"hard":[],"soft":[]}') {
                    try {
                        skills = JSON.parse(existingSkills);
                        console.log('[saveCvDialog] Using existing skills data from hidden input');
                    } catch (e) {
                        console.error('[saveCvDialog] Failed to parse existing skills:', e);
                    }
                }
            }
            
            console.log('[saveCvDialog] Final data summary:');
            console.log('  - Education:', education.length, 'records');
            console.log('  - Work:', work.length, 'records');
            console.log('  - Hard skills:', skills.hard.length, 'items');
            console.log('  - Soft skills:', skills.soft.length, 'items');
            console.log('  - Certifications:', {
                english: certifications.english_level,
                toeic: certifications.toeic_score,
                ielts: certifications.ielts_score,
                certs: certifications.certifications?.length || 0
            });

            document.getElementById('self_description_{{ $job->id }}').value = selfDesc;
            document.getElementById('education_json_{{ $job->id }}').value = JSON.stringify(education);
            document.getElementById('work_experiences_json_{{ $job->id }}').value = JSON.stringify(work);
            document.getElementById('skills_json_{{ $job->id }}').value = JSON.stringify(skills);
            document.getElementById('certifications_json_{{ $job->id }}').value = JSON.stringify(certifications);

            if (closeAfterSave) {
                closeCvDialog_{{ $job->id }}();
            }
        }

        function highlightCvDialogErrors_{{ $job->id }}() {
            const selfDescEl = document.getElementById('selfDescriptionInput_{{ $job->id }}');
            if (@json($errors->has('self_description'))) {
                selfDescEl?.classList.add('border-red-400');
            }
        }

        // Init chips
        renderVerbChips(document.getElementById('workVerbChips_{{ $job->id }}'), () => {
            return lastWorkDescriptionEl_{{ $job->id }} ?? document.querySelector('#workRows_{{ $job->id }} [data-widx]:last-child [data-wfield="description"]');
        });
        renderSkillChips_{{ $job->id }}(@json($job->title));

        // Set initial mode based on old input
        setCvMode_{{ $job->id }}(initialCvMode_{{ $job->id }});

        // Auto-open CV dialog if user chose CV nhanh and there are validation errors
        if (initialCvMode_{{ $job->id }} === 'form' && hasFormErrors_{{ $job->id }}) {
            openCvDialog_{{ $job->id }}();
            highlightCvDialogErrors_{{ $job->id }}();
        }

        // Auto-sync dialog -> hidden fields on submit (so user doesn't need to click "Lưu CV")
        if (applyForm) {
            applyForm.addEventListener('submit', function (e) {
                if (cvModeInput && cvModeInput.value === 'form') {
                    console.log('[Submit Event] CV form mode detected, saving data...');
                    saveCvDialog_{{ $job->id }}(false);
                    
                    // Debug: Log what was saved
                    setTimeout(() => {
                        const eduData = document.getElementById('education_json_{{ $job->id }}')?.value;
                        const workData = document.getElementById('work_experiences_json_{{ $job->id }}')?.value;
                        const skillsData = document.getElementById('skills_json_{{ $job->id }}')?.value;
                        
                        console.log('[Submit Event] Education JSON length:', eduData?.length || 0);
                        console.log('[Submit Event] Work JSON length:', workData?.length || 0);
                        console.log('[Submit Event] Skills JSON length:', skillsData?.length || 0);
                        
                        if (eduData === '[]' || !eduData) {
                            console.error('[Submit Event] WARNING: Education data is EMPTY!');
                        }
                        if (workData === '[]' || !workData) {
                            console.error('[Submit Event] WARNING: Work data is EMPTY!');
                        }
                    }, 100);
                }

                // Enhanced loading state for demo
                const submitBtn = document.getElementById('submitBtn_{{ $job->id }}');
                const submitIcon = document.getElementById('submitIcon_{{ $job->id }}');
                const submitSpinner = document.getElementById('submitSpinner_{{ $job->id }}');
                const submitText = document.getElementById('submitText_{{ $job->id }}');
                const scoringProgress = document.getElementById('scoringProgress_{{ $job->id }}');

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
                    submitBtn.classList.remove('hover:scale-[1.02]', 'hover:shadow-xl');
                }
                if (submitIcon) submitIcon.classList.add('hidden');
                if (submitSpinner) submitSpinner.classList.remove('hidden');
                if (submitText) submitText.textContent = 'AI đang phân tích CV của bạn...';
                if (scoringProgress) scoringProgress.classList.remove('hidden');
            });
        }

        if (cvFileInput) {
            let cachedFile = null;
            cvFileInput.addEventListener('change', function(e) {
                // If user selected a file, cache it. If they clicked cancel, restore the cached file.
                if (e.target.files && e.target.files.length > 0) {
                    cachedFile = e.target.files[0];
                } else if (cachedFile) {
                    const dt = new DataTransfer();
                    dt.items.add(cachedFile);
                    e.target.files = dt.files;
                }

                const fileName = e.target.files[0]?.name;
                const placeholder = document.getElementById('upload-placeholder_{{ $job->id }}');
                const success = document.getElementById('upload-success_{{ $job->id }}');
                const fileNameDisplay = document.getElementById('file-name-display_{{ $job->id }}');
                
                if (fileName) {
                    placeholder.classList.add('hidden');
                    success.classList.remove('hidden');
                    success.classList.add('flex');
                    fileNameDisplay.textContent = fileName;
                } else {
                    placeholder.classList.remove('hidden');
                    success.classList.add('hidden');
                    success.classList.remove('flex');
                }
            });
        }
        
        // NEW: Submit application form with CV data
        function submitApplicationForm_{{ $job->id }}() {
            const cvMode = cvModeInput?.value;
            
            // If using CV form, save dialog data to hidden inputs BEFORE submitting
            if (cvMode === 'form') {
                console.log('[Application Submit] Saving CV dialog data before submit...');
                saveCvDialog_{{ $job->id }}(false);
                
                // Debug: Check if data was saved
                const eduData = document.getElementById('education_json_{{ $job->id }}')?.value;
                const workData = document.getElementById('work_experiences_json_{{ $job->id }}')?.value;
                console.log('[Application Submit] Education JSON:', eduData?.substring(0, 100));
                console.log('[Application Submit] Work JSON:', workData?.substring(0, 100));
            }
            
            // Submit the form
            const form = document.getElementById('applyForm_{{ $job->id }}');
            if (form) {
                console.log('[Application Submit] Submitting form...');
                form.submit();
            }
        }

        // Global drag and drop prevention to stop browser from opening files if dropped outside the zone
        window.addEventListener('dragover', function(e) {
            e.preventDefault();
        });
        window.addEventListener('drop', function(e) {
            e.preventDefault();
        });
    </script>
</x-layouts.app>
