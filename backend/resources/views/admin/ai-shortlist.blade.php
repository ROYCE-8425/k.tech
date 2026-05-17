<x-layouts.app title="AI Shortlist - {{ $job->title }}">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center text-gray-500 hover:text-indigo-600 mb-6 group transition-colors">
            <div class="w-10 h-10 rounded-xl bg-gray-100 group-hover:bg-indigo-100 flex items-center justify-center mr-3 transition-colors">
                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </div>
            <span class="font-medium">Quay lại Dashboard</span>
        </a>

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <div class="flex items-center space-x-4 mb-3">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-xl">
                        <span class="text-2xl">🤖</span>
                    </div>
                    <div>
                        <p class="text-violet-600 font-semibold">{{ $job->company->name ?? 'Công ty' }}</p>
                        <h1 class="text-3xl font-bold text-gray-900">AI Shortlist</h1>
                        <p class="text-gray-500 text-sm mt-1">{{ $job->title }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4 text-gray-500">
                    <span class="inline-flex items-center">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        {{ count($shortlist) }} ứng viên được xếp hạng
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Score Legend -->
    <div class="glass-panel rounded-2xl p-4 mb-6">
        <div class="flex flex-wrap items-center justify-center gap-6 text-sm">
            <span class="text-gray-500 font-medium">AI Fit Score:</span>
            <div class="flex items-center">
                <span class="w-3 h-3 rounded-full bg-emerald-500 mr-2"></span>
                <span class="text-gray-600">Phù hợp cao (≥80)</span>
            </div>
            <div class="flex items-center">
                <span class="w-3 h-3 rounded-full bg-amber-500 mr-2"></span>
                <span class="text-gray-600">Phù hợp vừa (60–79)</span>
            </div>
            <div class="flex items-center">
                <span class="w-3 h-3 rounded-full bg-red-500 mr-2"></span>
                <span class="text-gray-600">Phù hợp thấp (&lt;60)</span>
            </div>
            <div class="flex items-center">
                <span class="w-3 h-3 rounded-full bg-gray-300 mr-2"></span>
                <span class="text-gray-600">Lỗi / Chưa có</span>
            </div>
            <div class="flex items-center border-l border-gray-300 pl-4 ml-2">
                <span class="inline-block w-3 h-3 rounded-full bg-violet-200 border-2 border-violet-400 mr-2"></span>
                <span class="text-gray-600">Mới tính</span>
            </div>
            <div class="flex items-center">
                <span class="inline-block w-3 h-3 rounded-full bg-orange-200 border-2 border-orange-400 mr-2"></span>
                <span class="text-gray-600">Cũ (>7 ngày)</span>
            </div>
            <div class="flex items-center border-l border-gray-300 pl-4 ml-2">
                <span class="text-gray-500 font-medium">💬 Phản hồi:</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-[10px]">✅</span>
                <span class="px-1.5 py-0.5 rounded bg-red-100 text-[10px]">❌</span>
                <span class="px-1.5 py-0.5 rounded bg-amber-100 text-[10px]">⚠️</span>
                <span class="px-1.5 py-0.5 rounded bg-indigo-100 text-[10px]">📝</span>
            </div>
        </div>
    </div>

    {{-- Demo seed-aware context bar --}}
    @if(config('app.demo_mode'))
        @php
            $seededCount = collect($shortlist)->filter(fn($item) => !$item['fresh'] && !$item['error'] && $item['persisted'])->count();
            $freshCount = collect($shortlist)->filter(fn($item) => $item['fresh'])->count();
            $errorCount = collect($shortlist)->filter(fn($item) => $item['error'])->count();
        @endphp
        @if(count($shortlist) > 0)
            <div class="flex items-center gap-3 p-3 rounded-2xl bg-purple-50 border border-purple-200 mb-6 animate-fade-in">
                <span class="text-lg">💡</span>
                <div class="text-sm text-purple-700">
                    @if($seededCount > 0 && $freshCount === 0)
                        <span class="font-semibold">Dữ liệu AI đã có sẵn từ demo seed.</span>
                        Nhấn "Tính lại AI" trên mỗi ứng viên để xem pipeline AI hoạt động thật.
                    @elseif($freshCount > 0 && $seededCount === 0)
                        <span class="font-semibold">Tất cả kết quả AI vừa được tính mới.</span>
                        Dữ liệu này phản ánh pipeline AI thật.
                    @elseif($seededCount > 0 && $freshCount > 0)
                        <span class="font-semibold">{{ $seededCount }} kết quả từ seed + {{ $freshCount }} mới tính.</span>
                        Nhấn "Tính lại AI" để cập nhật kết quả seed.
                    @endif
                    @if($errorCount > 0)
                        · <span class="text-amber-600 font-medium">{{ $errorCount }} ứng viên bị lỗi — kiểm tra AI service.</span>
                    @endif
                </div>
            </div>
        @else
            <div class="flex items-center gap-3 p-3 rounded-2xl bg-gray-50 border border-gray-200 mb-6 animate-fade-in">
                <span class="text-lg">📋</span>
                <div class="text-sm text-gray-600">
                    <span class="font-semibold">Chưa có ứng viên nào cho vị trí này.</span>
                    Phù hợp để test luồng apply mới từ phía ứng viên.
                </div>
            </div>
        @endif
    @endif

    <!-- Flash Messages -->
    @if(session('status'))
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl px-5 py-4 mb-6 flex items-center gap-3 animate-fade-in">
            <svg class="w-6 h-6 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-emerald-800 font-medium">{{ session('status') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-2xl px-5 py-4 mb-6 flex items-center gap-3 animate-fade-in">
            <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-red-800 font-medium">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Shortlist -->
    @if(count($shortlist) > 0)
        <div class="space-y-4">
            @foreach($shortlist as $index => $item)
                @php
                    $score = $item['fit_score'];
                    $scoreColor = $score === null ? 'gray' : ($score >= 80 ? 'emerald' : ($score >= 60 ? 'amber' : 'red'));
                    $scoreBg = [
                        'gray' => 'from-gray-400 to-gray-500',
                        'emerald' => 'from-emerald-400 to-teal-500',
                        'amber' => 'from-amber-400 to-orange-500',
                        'red' => 'from-red-400 to-pink-500',
                    ][$scoreColor];

                    $rankLabels = [
                        'high_fit' => ['label' => 'Phù hợp cao', 'bg' => 'bg-emerald-100', 'text' => 'text-emerald-700'],
                        'medium_fit' => ['label' => 'Phù hợp vừa', 'bg' => 'bg-amber-100', 'text' => 'text-amber-700'],
                        'low_fit' => ['label' => 'Phù hợp thấp', 'bg' => 'bg-red-100', 'text' => 'text-red-700'],
                        'error' => ['label' => 'Lỗi', 'bg' => 'bg-gray-100', 'text' => 'text-gray-700'],
                        'unknown' => ['label' => 'Chưa rõ', 'bg' => 'bg-gray-100', 'text' => 'text-gray-700'],
                    ];
                    $rank = $rankLabels[$item['rank_label']] ?? $rankLabels['unknown'];

                    $confLabels = [
                        'high' => ['label' => 'Tin cậy cao', 'bg' => 'bg-green-50', 'text' => 'text-green-700'],
                        'medium' => ['label' => 'Tin cậy TB', 'bg' => 'bg-yellow-50', 'text' => 'text-yellow-700'],
                        'low' => ['label' => 'Tin cậy thấp', 'bg' => 'bg-red-50', 'text' => 'text-red-700'],
                    ];
                    $conf = $confLabels[$item['confidence_label']] ?? $confLabels['low'];
                @endphp

                <div class="glass-card rounded-2xl overflow-hidden animate-fade-in {{ $index === 0 ? 'ring-2 ring-violet-200' : '' }}" style="animation-delay: {{ $index * 0.03 }}s;"
                     x-data="{ expanded: {{ $index === 0 ? 'true' : 'false' }} }">

                    <!-- Main Row -->
                    <div class="flex flex-col md:flex-row md:items-center p-5 gap-4 cursor-pointer hover:bg-gray-50/50 transition-colors"
                         @click="expanded = !expanded">

                        <!-- Rank # + Avatar -->
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br {{ $scoreBg }} flex items-center justify-center text-white font-bold text-lg shadow-md">
                                {{ $index + 1 }}
                            </div>
                            <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center">
                                <span class="text-lg font-bold text-indigo-600">
                                    {{ strtoupper(substr($item['candidate_name'], 0, 2)) }}
                                </span>
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-bold text-gray-900 truncate">{{ $item['candidate_name'] }}</h3>
                                <div class="flex items-center gap-2 text-xs text-gray-500 mt-0.5">
                                    @if($item['fresh'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-violet-100 text-violet-700 font-medium">Mới tính</span>
                                    @elseif($item['stale'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 font-medium">Cũ</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-medium">Đã lưu</span>
                                    @endif

                                    @if($item['generated_at'])
                                        <span>{{ \Carbon\Carbon::parse($item['generated_at'])->format('d/m/Y H:i') }}</span>
                                    @endif

                                    {{-- Compact feedback badge on collapsed row --}}
                                    @if(!empty($feedbackMap[$item['application_id']]))
                                        @php $fbRow = $feedbackMap[$item['application_id']]; @endphp
                                        @if($fbRow['type'] === 'agree')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-medium">✅</span>
                                        @elseif($fbRow['type'] === 'disagree')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-medium">❌</span>
                                        @elseif($fbRow['type'] === 'flag')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-medium">⚠️</span>
                                        @elseif($fbRow['type'] === 'note')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 font-medium">📝</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Score + Rank + Confidence -->
                        <div class="flex items-center gap-3 md:ml-auto">
                            <span class="{{ $rank['bg'] }} {{ $rank['text'] }} px-3 py-1 rounded-full text-xs font-semibold">
                                {{ $rank['label'] }}
                            </span>
                            <span class="{{ $conf['bg'] }} {{ $conf['text'] }} px-3 py-1 rounded-full text-xs font-semibold">
                                {{ $conf['label'] }}
                            </span>
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br {{ $scoreBg }} flex flex-col items-center justify-center text-white shadow-md">
                                @if($score !== null)
                                    <span class="text-xl font-bold">{{ number_format($score, 0) }}</span>
                                    <span class="text-[10px] opacity-80">điểm</span>
                                @else
                                    <span class="text-lg font-bold">--</span>
                                @endif
                            </div>

                            <!-- Expand indicator -->
                            <div class="flex flex-col items-center">
                                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                                <span class="text-[9px] text-gray-400 mt-0.5" x-show="!expanded">chi tiết</span>
                            </div>
                        </div>
                    </div>

                    <!-- Expanded Details -->
                    <div x-show="expanded" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak
                         class="border-t border-gray-100 px-5 pb-5">

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-5">
                            <!-- Left: Skills -->
                            <div class="space-y-4">
                                <!-- Matched Skills -->
                                @if(!empty($item['matched_skills']))
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                                            <span class="text-emerald-500">✓</span> Kỹ năng phù hợp ({{ count($item['matched_skills']) }})
                                        </h4>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach($item['matched_skills'] as $skill)
                                                <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-medium border border-emerald-200">{{ $skill }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Missing Required Skills -->
                                @if(!empty($item['missing_skills']))
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                                            <span class="text-red-500">✗</span> Thiếu kỹ năng bắt buộc ({{ count($item['missing_skills']) }})
                                        </h4>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach($item['missing_skills'] as $skill)
                                                <span class="px-2.5 py-1 rounded-lg bg-red-50 text-red-700 text-xs font-medium border border-red-200">{{ $skill }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Missing Preferred Skills -->
                                @if(!empty($item['missing_preferred_skills']))
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                                            <span class="text-amber-500">~</span> Thiếu kỹ năng ưu tiên ({{ count($item['missing_preferred_skills']) }})
                                        </h4>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach($item['missing_preferred_skills'] as $skill)
                                                <span class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 text-xs font-medium border border-amber-200">{{ $skill }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Right: Risk Flags + Score Breakdown -->
                            <div class="space-y-4">
                                <!-- Risk Flags -->
                                @if(!empty($item['risk_flags']))
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                                            <span class="text-orange-500">⚠</span> Cảnh báo rủi ro
                                        </h4>
                                        <div class="space-y-1.5">
                                            @foreach($item['risk_flags'] as $flag)
                                                <div class="flex items-start gap-2 text-sm text-orange-700 bg-orange-50 rounded-lg px-3 py-2 border border-orange-100">
                                                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                    </svg>
                                                    <span>{{ $flag }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Score Breakdown -->
                                @if(!empty($item['score_breakdown']))
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                                            <span class="text-indigo-500">📊</span> Chi tiết điểm
                                        </h4>
                                        <div class="bg-gray-50 rounded-xl p-3 space-y-2">
                                            @foreach($item['score_breakdown'] as $key => $breakdown)
                                                @php
                                                    $componentLabels = [
                                                        'required_skill_coverage' => 'Kỹ năng bắt buộc',
                                                        'preferred_skill_coverage' => 'Kỹ năng ưu tiên',
                                                        'experience_fit' => 'Kinh nghiệm',
                                                        'seniority_fit' => 'Cấp bậc',
                                                        'domain_relevance' => 'Lĩnh vực',
                                                        'confidence_adjustment' => 'Độ tin cậy',
                                                    ];
                                                    $label = $componentLabels[$key] ?? $key;
                                                    $rawScore = is_array($breakdown) ? ($breakdown['score'] ?? 0) : 0;
                                                    $weight = is_array($breakdown) ? ($breakdown['weight'] ?? 0) : 0;
                                                    $weighted = is_array($breakdown) ? ($breakdown['weighted'] ?? 0) : 0;
                                                    $detail = is_array($breakdown) ? ($breakdown['detail'] ?? '') : '';
                                                    $barWidth = min(100, max(0, $rawScore * 100));
                                                @endphp
                                                <div>
                                                    <div class="flex items-center justify-between text-xs mb-1">
                                                        <span class="font-medium text-gray-700">{{ $label }} <span class="text-gray-400">({{ $weight * 100 }}%)</span></span>
                                                        <span class="font-semibold text-gray-800">{{ number_format($weighted, 1) }}pt</span>
                                                    </div>
                                                    <div class="w-full h-1.5 bg-gray-200 rounded-full">
                                                        <div class="h-full rounded-full {{ $rawScore >= 0.7 ? 'bg-emerald-400' : ($rawScore >= 0.4 ? 'bg-amber-400' : 'bg-red-400') }}" style="width: {{ $barWidth }}%"></div>
                                                    </div>
                                                    @if($detail)
                                                        <p class="text-[11px] text-gray-500 mt-0.5">{{ $detail }}</p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Metadata + Actions -->
                                <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                                    <div class="text-xs text-gray-400 space-y-0.5">
                                        <div>Pipeline: {{ $item['pipeline_version'] }}</div>
                                        <div>Retrieval: {{ $item['retrieval_method'] }}</div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        @if(!$item['error'] && $item['persisted'])
                                            <a href="{{ route('admin.applications.ai-xray', $item['application_id']) }}"
                                               class="inline-flex items-center px-4 py-2 rounded-xl bg-indigo-50 text-indigo-700 text-sm font-semibold hover:bg-indigo-600 hover:text-white transition-all duration-200">
                                                🔬 X-Ray
                                            </a>
                                            <a href="{{ route('admin.applications.ai-decision-lab', $item['application_id']) }}"
                                               class="inline-flex items-center px-4 py-2 rounded-xl bg-teal-50 text-teal-700 text-sm font-semibold hover:bg-teal-600 hover:text-white transition-all duration-200">
                                                🧪 Decision Lab
                                            </a>
                                        @endif

                                        <form action="{{ route('admin.applications.ai-refresh', $item['application_id']) }}" method="POST"
                                              onsubmit="return confirm('Tính lại kết quả AI cho ứng viên này?')">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-xl bg-violet-50 text-violet-700 text-sm font-semibold hover:bg-violet-600 hover:text-white transition-all duration-200">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                                Tính lại AI
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                {{-- ═══ Phase 12: Recruiter Feedback ═══ --}}
                                @php $existingFb = $feedbackMap[$item['application_id']] ?? null; @endphp
                                <div class="pt-3 mt-3 border-t border-gray-100"
                                     x-data="{
                                         fbType: '{{ $existingFb['type'] ?? '' }}',
                                         fbNote: '{{ addslashes($existingFb['note'] ?? '') }}',
                                         fbTime: '{{ $existingFb['updated_at'] ?? '' }}',
                                         showNote: false,
                                         saving: false,
                                         async sendFeedback(type, note) {
                                             this.saving = true;
                                             try {
                                                 const body = new FormData();
                                                 body.append('feedback_type', type);
                                                 if (note) body.append('feedback_note', note);
                                                 const resp = await fetch('{{ route('admin.applications.ai-feedback', $item['application_id']) }}', {
                                                     method: 'POST',
                                                     headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                                     body
                                                 });
                                                 const data = await resp.json();
                                                 if (data.success) {
                                                     this.fbType = data.feedback.type;
                                                     this.fbNote = data.feedback.note || '';
                                                     this.fbTime = data.feedback.updated_at;
                                                     this.showNote = false;
                                                 }
                                             } catch (e) { alert('Không thể gửi phản hồi — vui lòng thử lại.'); console.error('Feedback failed', e); }
                                             this.saving = false;
                                         }
                                     }">

                                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                        {{-- Label --}}
                                        <span class="text-xs font-semibold text-gray-500 flex items-center gap-1 whitespace-nowrap">
                                            💬 Phản hồi:
                                        </span>

                                        {{-- Quick action buttons --}}
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <button @click="sendFeedback('agree')" :disabled="saving"
                                                    :class="fbType === 'agree' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-white text-gray-600 border-gray-200 hover:border-emerald-400 hover:text-emerald-700'"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border text-xs font-semibold transition-all duration-200">
                                                👍 Đồng ý
                                            </button>
                                            <button @click="sendFeedback('disagree')" :disabled="saving"
                                                    :class="fbType === 'disagree' ? 'bg-red-600 text-white border-red-600 shadow-sm' : 'bg-white text-gray-600 border-gray-200 hover:border-red-400 hover:text-red-700'"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border text-xs font-semibold transition-all duration-200">
                                                👎 Không đồng ý
                                            </button>
                                            <button @click="sendFeedback('flag')" :disabled="saving"
                                                    :class="fbType === 'flag' ? 'bg-amber-500 text-white border-amber-500 shadow-sm' : 'bg-white text-gray-600 border-gray-200 hover:border-amber-400 hover:text-amber-700'"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border text-xs font-semibold transition-all duration-200">
                                                ⚠️ Cần xem lại
                                            </button>
                                            <button @click="showNote = !showNote" :disabled="saving"
                                                    :class="fbType === 'note' ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white text-gray-600 border-gray-200 hover:border-indigo-400 hover:text-indigo-700'"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border text-xs font-semibold transition-all duration-200">
                                                📝 Ghi chú
                                            </button>
                                        </div>

                                        {{-- Existing feedback indicator --}}
                                        <template x-if="fbType && !showNote">
                                            <div class="flex items-center gap-2 text-xs text-gray-500 ml-auto">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-medium">
                                                    <template x-if="fbType === 'agree'"><span>✅ Đã đồng ý</span></template>
                                                    <template x-if="fbType === 'disagree'"><span>❌ Đã phản đối</span></template>
                                                    <template x-if="fbType === 'flag'"><span>⚠️ Đã đánh dấu</span></template>
                                                    <template x-if="fbType === 'note'"><span>📝 Có ghi chú</span></template>
                                                </span>
                                                <span x-text="fbTime" class="text-gray-400"></span>
                                            </div>
                                        </template>
                                    </div>

                                    {{-- Note input area --}}
                                    <div x-show="showNote" x-transition class="mt-3">
                                        <div class="flex gap-2">
                                            <input type="text" x-model="fbNote" maxlength="500" placeholder="VD: Score chưa thuyết phục, cần phỏng vấn thêm..."
                                                   class="flex-1 px-3 py-2 text-sm border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none transition-all">
                                            <button @click="sendFeedback('note', fbNote)" :disabled="saving || !fbNote.trim()"
                                                    class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50 transition-all whitespace-nowrap">
                                                Lưu
                                            </button>
                                        </div>
                                        {{-- Quick flag presets --}}
                                        <div class="flex flex-wrap gap-1.5 mt-2">
                                            <button type="button" @click="fbNote = 'Thiếu dữ liệu CV'" class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600 text-[11px] font-medium hover:bg-gray-200 transition-all">Thiếu dữ liệu</button>
                                            <button type="button" @click="fbNote = 'Score chưa thuyết phục'" class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600 text-[11px] font-medium hover:bg-gray-200 transition-all">Score chưa thuyết phục</button>
                                            <button type="button" @click="fbNote = 'Cần phỏng vấn kiểm tra thêm'" class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600 text-[11px] font-medium hover:bg-gray-200 transition-all">Cần phỏng vấn thêm</button>
                                            <button type="button" @click="fbNote = 'Ứng viên tiềm năng'" class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600 text-[11px] font-medium hover:bg-gray-200 transition-all">Ứng viên tiềm năng</button>
                                        </div>
                                        {{-- Show existing note if present --}}
                                        <template x-if="fbType === 'note' && fbNote">
                                            <p class="mt-2 text-xs text-indigo-600 italic" x-text="'Ghi chú hiện tại: ' + fbNote"></p>
                                        </template>
                                    </div>

                                    {{-- Saving indicator --}}
                                    <template x-if="saving">
                                        <div class="mt-2 flex items-center gap-2 text-xs text-gray-400">
                                            <div class="w-3 h-3 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                                            Đang lưu...
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <div class="glass-panel rounded-3xl p-16 text-center">
            <div class="relative inline-block mb-8">
                <div class="absolute inset-0 bg-violet-100 rounded-full blur-2xl opacity-60"></div>
                <div class="relative w-32 h-32 rounded-full bg-gradient-to-br from-violet-100 to-purple-100 flex items-center justify-center">
                    <span class="text-5xl">🤖</span>
                </div>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-3">Chưa có ứng viên nào nộp đơn</h3>
            <p class="text-gray-500 max-w-md mx-auto mb-6">Vị trí này chưa nhận được đơn ứng tuyển. AI Shortlist sẽ tự động xếp hạng khi có ứng viên.</p>
            <a href="{{ route('admin.jobs.applications', $job->id) }}" class="inline-flex items-center px-5 py-2.5 rounded-xl bg-violet-100 text-violet-700 font-semibold hover:bg-violet-200 transition-colors">
                ← Quay lại danh sách
            </a>
        </div>
    @endif

    <style>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fade-in 0.4s ease-out both;
        }
    </style>
</x-layouts.app>
