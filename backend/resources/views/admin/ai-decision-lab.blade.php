<x-layouts.app title="AI Decision Lab — {{ $candidate->name ?? 'Ứng viên' }}">
@php
    $canonicalScore = $aiResult['fit_score'] ?? null;
    $rankLabels = ['high_fit'=>['label'=>'Phù hợp cao','bg'=>'bg-emerald-100','text'=>'text-emerald-700'],'medium_fit'=>['label'=>'Phù hợp vừa','bg'=>'bg-amber-100','text'=>'text-amber-700'],'low_fit'=>['label'=>'Phù hợp thấp','bg'=>'bg-red-100','text'=>'text-red-700']];
    $confLabels = ['high'=>['label'=>'Tin cậy cao','icon'=>'🟢'],'medium'=>['label'=>'Tin cậy TB','icon'=>'🟡'],'low'=>['label'=>'Tin cậy thấp','icon'=>'🔴']];

    $modes = $comparison['modes'] ?? [];
    $deltas = $comparison['deltas'] ?? [];
    $candidateSkills = $comparison['candidate_skills'] ?? [];
    $jobRequiredSkills = $comparison['job_required_skills'] ?? [];

    $modeColors = [
        'baseline' => ['gradient' => 'from-gray-500 to-gray-600', 'bg' => 'bg-gray-50', 'border' => 'border-gray-200', 'badge' => 'bg-gray-100 text-gray-700', 'icon' => '📋'],
        'graph_1hop' => ['gradient' => 'from-blue-500 to-indigo-600', 'bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'badge' => 'bg-blue-100 text-blue-700', 'icon' => '🔗'],
        'graph_2hop' => ['gradient' => 'from-violet-500 to-purple-600', 'bg' => 'bg-violet-50', 'border' => 'border-violet-200', 'badge' => 'bg-violet-100 text-violet-700', 'icon' => '🌐'],
        'feedback_aware' => ['gradient' => 'from-teal-500 to-emerald-600', 'bg' => 'bg-teal-50', 'border' => 'border-teal-200', 'badge' => 'bg-teal-100 text-teal-700', 'icon' => '🧠'],
    ];
@endphp

<style>
    @keyframes labFadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    .lab-card { animation: labFadeIn 0.4s ease-out backwards; }
    .lab-card:nth-child(1) { animation-delay: 0.05s; }
    .lab-card:nth-child(2) { animation-delay: 0.15s; }
    .lab-card:nth-child(3) { animation-delay: 0.25s; }
    .lab-card:nth-child(4) { animation-delay: 0.35s; }
    .score-donut {
        width: 72px; height: 72px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 1.1rem;
    }
    .delta-pill { display: inline-flex; align-items: center; gap: 4px; padding: 2px 10px; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; }
    .delta-plus { background: #d1fae5; color: #065f46; }
    .delta-zero { background: #f3f4f6; color: #6b7280; }
    .delta-minus { background: #fee2e2; color: #991b1b; }
</style>

<!-- Header -->
<div class="mb-8">
    <a href="{{ route('admin.jobs.ai-shortlist', $job->id) }}" class="inline-flex items-center text-gray-500 hover:text-teal-600 mb-6 group transition-colors">
        <div class="w-10 h-10 rounded-xl bg-gray-100 group-hover:bg-teal-100 flex items-center justify-center mr-3 transition-colors">
            <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </div>
        <span class="font-medium">Quay lại AI Shortlist</span>
    </a>
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-4 mb-2">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-500 to-emerald-600 flex items-center justify-center shadow-xl">
                    <span class="text-2xl">🧪</span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">AI Decision Lab</h1>
                    <p class="text-gray-500 text-sm">{{ $candidate->name ?? 'Ứng viên' }} → {{ $job->title }}</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            @if($canonicalScore !== null)
                <span class="inline-flex items-center px-4 py-1.5 rounded-full {{ $canonicalScore >= 80 ? 'bg-emerald-100 text-emerald-700' : ($canonicalScore >= 60 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }} text-sm font-bold">
                    Canonical: {{ $canonicalScore }}
                </span>
            @endif
            <a href="{{ route('admin.applications.ai-xray', $application->id) }}"
               class="inline-flex items-center px-4 py-1.5 rounded-full bg-indigo-50 text-indigo-700 text-sm font-bold hover:bg-indigo-600 hover:text-white transition-all duration-200">
                🔬 X-Ray
            </a>
        </div>
    </div>
</div>

{{-- Error State --}}
@if($comparisonError)
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 mb-6">
        <div class="flex items-start gap-3">
            <span class="text-2xl">⚠️</span>
            <div>
                <h3 class="font-bold text-amber-800 mb-1">So sánh live không khả dụng</h3>
                <p class="text-amber-700 text-sm">{{ $comparisonError }}</p>
                @if($canonicalScore !== null)
                    <p class="text-amber-600 text-sm mt-2">Kết quả canonical đã lưu ({{ $canonicalScore }} điểm) vẫn hiển thị bên dưới.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Show persisted canonical result only --}}
    @if($canonicalScore !== null)
        <div class="glass-panel rounded-2xl p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">📊 Kết quả AI đã lưu (Canonical)</h2>
            <div class="grid grid-cols-3 gap-4 text-center">
                <div class="p-4 bg-gray-50 rounded-xl">
                    <div class="text-3xl font-extrabold {{ $canonicalScore >= 80 ? 'text-emerald-600' : ($canonicalScore >= 60 ? 'text-amber-600' : 'text-red-600') }}">{{ $canonicalScore }}</div>
                    <div class="text-sm text-gray-500 mt-1">Fit Score</div>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl">
                    <div class="text-lg font-bold text-gray-700">{{ count($aiResult['matched_skills'] ?? []) }}</div>
                    <div class="text-sm text-gray-500 mt-1">Kỹ năng phù hợp</div>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl">
                    <div class="text-lg font-bold text-gray-700">{{ count($aiResult['missing_skills'] ?? []) }}</div>
                    <div class="text-sm text-gray-500 mt-1">Kỹ năng thiếu</div>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-4 text-center">Decision Lab so sánh cần AI service đang chạy. Kết quả này từ lần phân tích trước.</p>
        </div>
    @endif
@endif

{{-- Comparison Results --}}
@if(!empty($modes))
    {{-- WHY THIS MATTERS --}}
    <div class="bg-gradient-to-r from-teal-50 to-emerald-50 border border-teal-200 rounded-2xl p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-2">💡 Tại sao hệ thống này hơn keyword matching?</h2>
        <p class="text-gray-700 text-sm leading-relaxed">
            Bảng bên dưới cho thấy cùng một ứng viên — cùng một JD — nhưng khi hệ thống bật thêm <strong>graph reasoning</strong>
            (hiểu quan hệ giữa kỹ năng) và <strong>feedback reranking</strong> (học từ phản hồi recruiter), điểm phù hợp thay đổi.
            <br>
            <span class="text-teal-700 font-semibold">Sự khác biệt giữa Baseline và Full Pipeline chính là giá trị AI mang lại.</span>
        </p>
    </div>

    {{-- COMPARISON CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
        @foreach($modes as $i => $mode)
            @php
                $modeKey = $mode['mode'];
                $colors = $modeColors[$modeKey] ?? $modeColors['baseline'];
                $delta = $deltas[$i] ?? ['delta' => 0, 'explanation' => ''];
                $deltaVal = $delta['delta'];
                $deltaClass = $deltaVal > 0 ? 'delta-plus' : ($deltaVal < 0 ? 'delta-minus' : 'delta-zero');
                $scoreVal = $mode['fit_score'];
                $scoreRingColor = $scoreVal >= 80 ? '#10b981' : ($scoreVal >= 60 ? '#f59e0b' : '#ef4444');
                $scorePercent = min(100, $scoreVal);
                $circumference = 2 * 3.14159 * 28;
                $dashOffset = $circumference * (1 - $scorePercent / 100);
                $rankInfo = $rankLabels[$mode['rank_label'] ?? 'low_fit'] ?? ['label'=>'—','bg'=>'bg-gray-100','text'=>'text-gray-600'];
                $confInfo = $confLabels[$mode['confidence_label'] ?? 'low'] ?? ['label'=>'—','icon'=>'🔴'];
                $isCanonical = ($modeKey === 'graph_2hop');
            @endphp
            <div class="lab-card bg-white rounded-2xl shadow-lg shadow-gray-200/50 border {{ $colors['border'] }} overflow-hidden {{ $isCanonical ? 'ring-2 ring-violet-300 ring-offset-2' : '' }}">
                {{-- Mode Header --}}
                <div class="bg-gradient-to-r {{ $colors['gradient'] }} px-5 py-3 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">{{ $colors['icon'] }}</span>
                            <span class="font-bold text-sm">{{ $mode['label'] }}</span>
                        </div>
                        @if($i > 0)
                            <span class="delta-pill {{ $deltaClass }}">
                                {{ $deltaVal > 0 ? '+' : '' }}{{ $deltaVal }}
                            </span>
                        @else
                            <span class="delta-pill delta-zero">BASE</span>
                        @endif
                    </div>
                    @if($isCanonical)
                        <div class="text-[10px] mt-1 text-white/80 font-semibold">≈ CANONICAL PIPELINE</div>
                    @endif
                </div>

                {{-- Score --}}
                <div class="px-5 pt-5 pb-3 flex items-center gap-4">
                    <div class="relative">
                        <svg width="72" height="72" viewBox="0 0 64 64">
                            <circle cx="32" cy="32" r="28" fill="none" stroke="#e5e7eb" stroke-width="5"/>
                            <circle cx="32" cy="32" r="28" fill="none" stroke="{{ $scoreRingColor }}" stroke-width="5"
                                    stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $dashOffset }}"
                                    stroke-linecap="round" transform="rotate(-90 32 32)"
                                    style="transition: stroke-dashoffset 0.8s ease;"/>
                            <text x="32" y="36" text-anchor="middle" fill="{{ $scoreRingColor }}" font-size="16" font-weight="800">{{ $scoreVal }}</text>
                        </svg>
                    </div>
                    <div>
                        <span class="{{ $rankInfo['bg'] }} {{ $rankInfo['text'] }} px-3 py-1 rounded-full text-xs font-bold">{{ $rankInfo['label'] }}</span>
                        <div class="text-xs text-gray-500 mt-1.5">{{ $confInfo['icon'] }} {{ $confInfo['label'] }}</div>
                    </div>
                </div>

                {{-- Skills Summary --}}
                <div class="px-5 pb-3">
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="p-2 {{ $colors['bg'] }} rounded-lg">
                            <div class="text-lg font-extrabold text-gray-800">{{ count($mode['matched_skills']) }}</div>
                            <div class="text-[10px] text-gray-500 leading-tight">Phù hợp</div>
                        </div>
                        <div class="p-2 bg-red-50 rounded-lg">
                            <div class="text-lg font-extrabold text-red-600">{{ count($mode['missing_skills']) }}</div>
                            <div class="text-[10px] text-gray-500 leading-tight">Thiếu</div>
                        </div>
                        <div class="p-2 bg-blue-50 rounded-lg">
                            <div class="text-lg font-extrabold text-blue-600">{{ $mode['related_matches_count'] }}</div>
                            <div class="text-[10px] text-gray-500 leading-tight">Liên quan</div>
                        </div>
                    </div>
                    @if($mode['one_hop_count'] > 0 || $mode['two_hop_count'] > 0)
                        <div class="text-[10px] text-gray-400 mt-1.5 text-center">
                            {{ $mode['one_hop_count'] }}× one-hop
                            @if($mode['two_hop_count'] > 0)
                                + {{ $mode['two_hop_count'] }}× two-hop
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Score Breakdown Mini --}}
                <div class="px-5 pb-3">
                    @foreach($mode['score_breakdown'] as $key => $comp)
                        @if($key !== 'feedback_adjustment')
                            @php
                                $shortLabels = [
                                    'required_skill_coverage' => 'Required',
                                    'preferred_skill_coverage' => 'Preferred',
                                    'experience_fit' => 'Experience',
                                    'seniority_fit' => 'Seniority',
                                    'domain_relevance' => 'Domain',
                                    'confidence_adjustment' => 'Confidence',
                                ];
                                $w = ($comp['weighted'] ?? 0);
                                $barW = min(100, max(2, $w));
                                $barColor = $w >= 70 * ($comp['weight'] ?? 0.1) ? 'bg-emerald-400' : ($w >= 40 * ($comp['weight'] ?? 0.1) ? 'bg-amber-400' : 'bg-red-400');
                            @endphp
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-[10px] text-gray-500 w-16 truncate">{{ $shortLabels[$key] ?? $key }}</span>
                                <div class="flex-1 bg-gray-100 rounded-full h-1.5">
                                    <div class="h-full rounded-full {{ $barColor }}" style="width: {{ $barW }}%"></div>
                                </div>
                                <span class="text-[10px] font-bold text-gray-600 w-8 text-right">{{ round($w, 1) }}</span>
                            </div>
                        @endif
                    @endforeach
                    @if(isset($mode['score_breakdown']['feedback_adjustment']))
                        @php $fb = $mode['score_breakdown']['feedback_adjustment']; @endphp
                        <div class="flex items-center gap-2 mt-1 pt-1 border-t border-gray-100">
                            <span class="text-[10px] text-teal-600 font-semibold">Feedback</span>
                            <span class="text-[10px] font-bold {{ ($fb['points'] ?? 0) > 0 ? 'text-emerald-600' : (($fb['points'] ?? 0) < 0 ? 'text-red-600' : 'text-gray-500') }}">
                                {{ ($fb['points'] ?? 0) > 0 ? '+' : '' }}{{ $fb['points'] ?? 0 }}pts
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Description --}}
                <div class="px-5 pb-4 border-t border-gray-100 pt-3">
                    <p class="text-[11px] text-gray-500 leading-relaxed">{{ $mode['description'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- DELTA EXPLANATION TABLE --}}
    <div class="glass-panel rounded-2xl p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-xl">📈</span>
            Phân tích ảnh hưởng từng lớp AI
        </h2>
        <div class="space-y-3">
            @foreach($deltas as $i => $d)
                @php
                    $modeKey = $d['mode'];
                    $colors = $modeColors[$modeKey] ?? $modeColors['baseline'];
                    $deltaVal = $d['delta'];
                @endphp
                <div class="flex items-start gap-4 p-4 rounded-xl {{ $colors['bg'] }} border {{ $colors['border'] }}">
                    <div class="flex-shrink-0">
                        <span class="text-lg">{{ $colors['icon'] }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 mb-1">
                            <span class="font-bold text-sm text-gray-800">{{ $modes[$i]['label'] ?? $modeKey }}</span>
                            @if($i > 0)
                                <span class="delta-pill {{ $deltaVal > 0 ? 'delta-plus' : ($deltaVal < 0 ? 'delta-minus' : 'delta-zero') }}">
                                    {{ $deltaVal > 0 ? '+' : '' }}{{ $deltaVal }} điểm
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600">{{ $d['explanation'] }}</p>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <div class="text-2xl font-extrabold {{ $modes[$i]['fit_score'] >= 80 ? 'text-emerald-600' : ($modes[$i]['fit_score'] >= 60 ? 'text-amber-600' : 'text-red-600') }}">
                            {{ $modes[$i]['fit_score'] ?? '—' }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- CONTEXT INFO --}}
    <div class="glass-panel rounded-2xl p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-xl">🔍</span>
            Bối cảnh phân tích
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="font-semibold text-gray-800 text-sm mb-2">Kỹ năng ứng viên (extracted)</h3>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($candidateSkills as $skill)
                        <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-medium">{{ $skill }}</span>
                    @endforeach
                    @if(empty($candidateSkills))
                        <span class="text-xs text-gray-400">Không có dữ liệu</span>
                    @endif
                </div>
            </div>
            <div>
                <h3 class="font-semibold text-gray-800 text-sm mb-2">Kỹ năng yêu cầu (JD)</h3>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($jobRequiredSkills as $skill)
                        <span class="px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-medium">{{ $skill }}</span>
                    @endforeach
                    @if(empty($jobRequiredSkills))
                        <span class="text-xs text-gray-400">Không có dữ liệu</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-2 md:grid-cols-4 gap-3 text-center text-xs">
            <div class="p-2 bg-gray-50 rounded-lg">
                <div class="font-bold text-gray-700">{{ $comparison['extraction_method'] ?? '—' }}</div>
                <div class="text-gray-400">Extraction</div>
            </div>
            <div class="p-2 bg-gray-50 rounded-lg">
                <div class="font-bold text-gray-700">{{ $comparison['extraction_confidence'] ?? '—' }}</div>
                <div class="text-gray-400">Confidence</div>
            </div>
            <div class="p-2 bg-gray-50 rounded-lg">
                <div class="font-bold text-gray-700">{{ $comparison['retrieval_method'] ?? '—' }}</div>
                <div class="text-gray-400">Retrieval</div>
            </div>
            <div class="p-2 bg-gray-50 rounded-lg">
                @if($canonicalScore !== null)
                    <div class="font-bold text-gray-700">{{ $canonicalScore }}</div>
                @else
                    <div class="font-bold text-gray-400">—</div>
                @endif
                <div class="text-gray-400">Canonical Score</div>
            </div>
        </div>
    </div>

    {{-- IMPORTANT NOTE --}}
    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 mb-6">
        <div class="flex items-start gap-3">
            <span class="text-lg">ℹ️</span>
            <div>
                <p class="text-sm text-gray-600">
                    <strong class="text-gray-800">Lưu ý:</strong> Các điểm số ở đây là <strong>so sánh chẩn đoán</strong> —
                    canonical fit_score đã lưu (<strong>{{ $canonicalScore ?? '—' }}</strong> điểm) <strong>không bị thay đổi</strong>.
                    Decision Lab giúp hiểu tại sao hệ thống AI đánh giá khác so với keyword matching đơn giản.
                </p>
            </div>
        </div>
    </div>
@elseif(!$comparisonError)
    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-8 text-center">
        <span class="text-4xl mb-4 block">🧪</span>
        <h3 class="font-bold text-gray-700 mb-2">Chưa có dữ liệu so sánh</h3>
        <p class="text-gray-500 text-sm">Hãy đảm bảo AI service đang chạy và thử lại.</p>
    </div>
@endif

</x-layouts.app>
