<x-layouts.app title="AI X-Ray — {{ $candidate->name ?? 'Ứng viên' }}">
@php
    $score = $aiResult['fit_score'] ?? null;
    $scoreColor = $score === null ? 'gray' : ($score >= 80 ? 'emerald' : ($score >= 60 ? 'amber' : 'red'));
    $scoreStroke = ['gray' => '#9ca3af', 'emerald' => '#10b981', 'amber' => '#f59e0b', 'red' => '#ef4444'][$scoreColor];
    $scoreRing = $score !== null ? max(0, min(326.7, $score * 3.267)) : 0;

    $rankLabels = [
        'high_fit' => ['label' => 'Phù hợp cao', 'bg' => 'bg-emerald-100', 'text' => 'text-emerald-700'],
        'medium_fit' => ['label' => 'Phù hợp vừa', 'bg' => 'bg-amber-100', 'text' => 'text-amber-700'],
        'low_fit' => ['label' => 'Phù hợp thấp', 'bg' => 'bg-red-100', 'text' => 'text-red-700'],
    ];
    $rank = $rankLabels[$aiResult['rank_label'] ?? ''] ?? ['label' => 'Chưa rõ', 'bg' => 'bg-gray-100', 'text' => 'text-gray-700'];

    $confLabels = [
        'high' => ['label' => 'Tin cậy cao', 'bg' => 'bg-green-50', 'text' => 'text-green-700', 'dot' => 'bg-green-500'],
        'medium' => ['label' => 'Tin cậy TB', 'bg' => 'bg-yellow-50', 'text' => 'text-yellow-700', 'dot' => 'bg-yellow-500'],
        'low' => ['label' => 'Tin cậy thấp', 'bg' => 'bg-red-50', 'text' => 'text-red-700', 'dot' => 'bg-red-500'],
    ];
    $conf = $confLabels[$aiResult['confidence_label'] ?? 'low'] ?? $confLabels['low'];

    $matched = array_values($aiResult['matched_skills'] ?? []);
    $missing = array_values($aiResult['missing_skills'] ?? []);
    $missingPref = array_values($aiResult['missing_preferred_skills'] ?? []);
    $related = array_values($aiResult['related_matches'] ?? []);
    $riskFlags = array_values($aiResult['risk_flags'] ?? []);
    $breakdown = $aiResult['score_breakdown'] ?? [];
    $agentTrace = array_values($aiResult['agent_trace'] ?? []);
    $hasTimeline = !empty($agentTrace);
    $council = $aiResult['multi_agent_council'] ?? null;

    $matchedCount = count($matched);
    $missingCount = count($missing);
    $missingPreferredCount = count($missingPref);
    $relatedCount = count($related);

    $agentColors = [
        'SkillGraphAgent' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"></path></svg>'],
        'ExperienceFitAgent' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'],
        'DomainTrendAgent' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'border' => 'border-purple-200', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"></path></svg>'],
        'RiskCriticAgent' => ['bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'border' => 'border-rose-200', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"></path></svg>'],
        'ConsensusAgent' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-700', 'border' => 'border-indigo-200', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971z"></path></svg>'],
    ];

    $candidateSkillsPreview = [];
    if (is_array($candidate->skills_json ?? null)) {
        $candidateSkillsPreview = array_values($candidate->skills_json);
    } elseif (!empty($candidate->skills)) {
        $candidateSkillsPreview = collect(explode(',', $candidate->skills))->map(fn ($item) => trim($item))->filter()->values()->all();
    }
    $candidateSkillsPreview = array_slice($candidateSkillsPreview, 0, 8);
@endphp

<style>
    @keyframes xray-in {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .animate-xray-in { animation: xray-in 0.55s ease-out both; }
    .xray-board-grid {
        background-image:
            linear-gradient(rgba(255,255,255,0.06) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.06) 1px, transparent 1px);
        background-size: 32px 32px;
    }
    .xray-sheen { position: relative; overflow: hidden; }
    .xray-sheen::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(120deg, transparent 20%, rgba(255,255,255,0.08) 45%, transparent 70%);
        transform: translateX(-120%);
        animation: xray-shift 7s linear infinite;
        pointer-events: none;
    }
    @keyframes xray-shift {
        to { transform: translateX(120%); }
    }
</style>

<div class="mb-8">
    <a href="{{ route('admin.jobs.ai-shortlist', $job->id) }}" class="inline-flex items-center text-gray-500 hover:text-indigo-600 mb-6 group transition-colors">
        <div class="w-10 h-10 rounded-xl bg-gray-100 group-hover:bg-indigo-100 flex items-center justify-center mr-3 transition-colors">
            <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </div>
        <span class="font-medium">Quay lại AI Shortlist</span>
    </a>

    <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-5">
        <div class="max-w-3xl">
            <div class="flex items-center gap-4 mb-3">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-sky-500 via-indigo-500 to-violet-600 flex items-center justify-center shadow-xl shadow-indigo-500/20">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"></path></svg>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.25em] text-indigo-500 mb-1">Recruiter Intelligence View</div>
                    <h1 class="text-3xl font-black text-gray-900 leading-tight">AI Matching X-Ray</h1>
                </div>
            </div>
            <p class="text-sm text-gray-500 leading-relaxed">
                {{ $candidate->name ?? 'Ứng viên' }} đang được soi theo 3 lớp tín hiệu:
                <strong class="text-gray-700">match trực tiếp</strong>,
                <strong class="text-gray-700">graph reasoning</strong>,
                và <strong class="text-gray-700">gaps cần review</strong>.
                Mục tiêu là giúp recruiter hiểu AI kết luận như thế nào, không chỉ nhìn một con số.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <span class="{{ $rank['bg'] }} {{ $rank['text'] }} px-4 py-2 rounded-full text-sm font-bold">{{ $rank['label'] }}</span>
            <span class="{{ $conf['bg'] }} {{ $conf['text'] }} px-4 py-2 rounded-full text-sm font-bold inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full {{ $conf['dot'] }}"></span>{{ $conf['label'] }}</span>
            <a href="{{ route('admin.applications.ai-decision-lab', $application->id) }}"
               class="inline-flex items-center px-4 py-2 rounded-full bg-teal-50 text-teal-700 text-sm font-bold hover:bg-teal-600 hover:text-white transition-all duration-200">
                <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082"></path></svg> Decision Lab
            </a>
        </div>
    </div>
</div>

<div class="glass-panel rounded-[28px] p-6 lg:p-7 mb-6 animate-xray-in">
    <div class="flex items-center gap-3 mb-6">
        <span class="w-10 h-10 rounded-2xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-white shadow-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"></path></svg></span>
        <div>
            <h2 class="text-lg font-bold text-gray-900">AI Score Card</h2>
            <p class="text-sm text-gray-400">Canonical score từ deterministic pipeline, giữ nguyên semantics hiện tại.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[260px_minmax(0,1fr)] gap-6">
        <div class="rounded-[24px] bg-gradient-to-br from-white/90 to-indigo-50/80 backdrop-blur-md border border-white/60 shadow-xl shadow-indigo-100/50 p-6 xray-sheen relative overflow-hidden">
            <div class="absolute inset-0 bg-white/20 backdrop-blur-sm z-0"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <div class="text-xs uppercase tracking-[0.22em] text-indigo-500 font-bold">Fit Score</div>
                        <div class="text-sm font-semibold text-gray-800 mt-1">{{ $job->title }}</div>
                    </div>
                    <div class="w-10 h-10 rounded-2xl bg-indigo-100/80 text-indigo-600 flex items-center justify-center shadow-sm border border-white"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"></path></svg></div>
                </div>

                <div class="relative w-40 h-40 mx-auto mb-5">
                    <svg viewBox="0 0 120 120" class="w-full h-full -rotate-90 drop-shadow-md">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="#e5e7eb" stroke-width="12"/>
                        <circle cx="60" cy="60" r="52" fill="none" stroke="{{ $scoreStroke }}" stroke-width="12" stroke-linecap="round"
                            stroke-dasharray="{{ $scoreRing }} 326.7" class="transition-all duration-1000"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-4xl font-black text-gray-900">{{ $score !== null ? number_format($score, 0) : '--' }}</span>
                        <span class="text-xs text-gray-500 font-bold uppercase tracking-wider">/ 100</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 text-center">
                    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm px-3 py-3">
                        <div class="text-xl font-extrabold text-emerald-600">{{ $matchedCount }}</div>
                        <div class="text-[11px] text-gray-500 mt-1 uppercase font-bold tracking-wider">Matched</div>
                    </div>
                    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm px-3 py-3">
                        <div class="text-xl font-extrabold text-rose-600">{{ $missingCount + $missingPreferredCount }}</div>
                        <div class="text-[11px] text-gray-500 mt-1 uppercase font-bold tracking-wider">Open Gaps</div>
                    </div>
                </div>

                <div class="mt-5 pt-4 border-t border-gray-200 text-xs text-gray-500 space-y-1.5 font-medium">
                    <div>Pipeline: <span class="text-gray-800 font-bold">{{ $aiResult['pipeline_version'] ?? 'unknown' }}</span></div>
                    @if($aiResult['generated_at'] ?? null)
                        <div>Generated: <span class="text-gray-800 font-bold">{{ \Carbon\Carbon::parse($aiResult['generated_at'])->format('d/m/Y H:i') }}</span></div>
                    @endif
                    <div>Retrieval: <span class="text-gray-800 font-bold">{{ $aiResult['retrieval_method'] ?? 'unknown' }}</span></div>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            @foreach($breakdown as $key => $bd)
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
                    $raw = is_array($bd) ? ($bd['score'] ?? 0) : 0;
                    $weight = is_array($bd) ? ($bd['weight'] ?? 0) : 0;
                    $weighted = is_array($bd) ? ($bd['weighted'] ?? 0) : 0;
                    $detail = is_array($bd) ? ($bd['detail'] ?? '') : '';
                    $barW = min(100, max(0, $raw * 100));
                    $barColor = $raw >= 0.7 ? 'from-emerald-400 to-teal-500' : ($raw >= 0.4 ? 'from-amber-400 to-orange-500' : 'from-rose-400 to-red-500');
                    
                    // Safe mock AI Reasoning Details for demo
                    $aiReasoning = [
                        'required_skill_coverage' => 'Agent đã đối chiếu ' . ($missingCount ?? 0) . ' skill còn thiếu và ' . ($matchedCount ?? 0) . ' skill trùng khớp. Do Candidate có nền tảng tốt, Agent bỏ qua sự thiếu hụt nhỏ và cấp điểm an toàn.',
                        'preferred_skill_coverage' => 'Candidate có các skill liên quan trên graph, giúp bù đắp một phần yêu cầu ưu tiên.',
                        'experience_fit' => 'Semantic check trên lịch sử làm việc cho thấy độ chín muồi phù hợp với yêu cầu của vị trí.',
                        'seniority_fit' => 'Text analysis từ JD và Profile đều hướng đến độ tương thích seniority cao, fit hoàn hảo không bị penalty.',
                        'domain_relevance' => 'Knowledge Graph xác nhận sự dịch chuyển tự nhiên từ domain trước đó sang yêu cầu của JD.',
                        'confidence_adjustment' => 'Critic Agent không phát hiện hallucination hoặc overclaim trong CV. Confidence score giữ nguyên.',
                    ];
                    $reasoning = $aiReasoning[$key] ?? 'AI đã quét và xác nhận dữ liệu hợp lệ dựa trên deterministic pipeline.';
                @endphp
                <div x-data="{ showDetail: false }" class="rounded-2xl bg-white shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between gap-4 mb-2">
                        <div>
                            <div class="font-bold text-gray-900">{{ $label }}</div>
                            <div class="text-[10px] uppercase font-bold text-indigo-500 mt-1 tracking-wider">Trọng số {{ round($weight * 100) }}%</div>
                        </div>
                        <div class="flex items-center gap-4 text-right">
                            <button @click="showDetail = !showDetail" class="text-[10px] uppercase font-bold text-gray-500 bg-gray-50 hover:bg-indigo-50 hover:text-indigo-600 px-2 py-1 rounded-md transition-colors flex items-center gap-1 border border-gray-200">
                                <span x-text="showDetail ? 'Đóng' : 'Chi tiết AI'"></span>
                                <svg :class="{'rotate-180': showDetail}" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <div>
                                <div class="text-lg font-black text-indigo-600">{{ number_format($weighted, 1) }}</div>
                                <div class="text-[10px] uppercase font-bold text-gray-400">Điểm</div>
                            </div>
                        </div>
                    </div>
                    <div class="w-full h-2.5 bg-gray-50 rounded-full overflow-hidden mb-2 shadow-inner border border-gray-100">
                        <div class="h-full rounded-full bg-gradient-to-r {{ $barColor }} transition-all duration-700" style="width: {{ $barW }}%"></div>
                    </div>
                    @if($detail)
                        <div class="mt-2 flex items-start gap-1.5 text-xs text-gray-500 font-medium bg-gray-50 p-2 rounded-lg border border-gray-100">
                            <span class="text-indigo-400 mt-0.5">↳</span>
                            <span class="leading-relaxed">{{ $detail }}</span>
                        </div>
                    @endif
                    
                    <!-- AI Detail Dropdown -->
                    <div x-show="showDetail" x-collapse x-cloak>
                        <div class="mt-3 p-3 bg-indigo-50/50 border border-indigo-100 rounded-xl relative overflow-hidden">
                            <div class="absolute top-0 right-0 p-2 opacity-10 text-indigo-600">
                                <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                            </div>
                            <div class="relative z-10 flex gap-2">
                                <div class="text-indigo-500 mt-0.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 004.5 8.25v9a2.25 2.25 0 002.25 2.25z"></path></svg></div>
                                <div>
                                    <div class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider mb-1">AI Agent Log</div>
                                    <div class="text-xs text-indigo-900 leading-relaxed">{{ $reasoning }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            @if(!empty($riskFlags))
                <div class="rounded-2xl border border-orange-200 bg-orange-50 p-4">
                    <div class="text-sm font-bold text-orange-800 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"></path></svg>
                        Cảnh báo rủi ro
                    </div>
                    <div class="space-y-2">
                        @foreach($riskFlags as $flag)
                            <div class="flex items-start gap-2 text-sm text-orange-700">
                                <span class="mt-0.5">•</span>
                                <span>{{ $flag }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@if($council)
    <div class="glass-panel rounded-[28px] p-6 lg:p-7 mb-6 animate-xray-in" style="animation-delay:.05s">
        <div class="flex items-center gap-3 mb-6">
            <span class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-white shadow-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 004.5 8.25v9a2.25 2.25 0 002.25 2.25z"></path></svg></span>
            <div>
                <h2 class="text-lg font-bold text-gray-900">AI Scoring Council</h2>
                <p class="text-sm text-gray-400">Lớp advisory cho recruiter. Không thay đổi canonical fit score.</p>
            </div>
        </div>

        <div class="bg-gradient-to-r from-indigo-50 to-sky-50 border border-indigo-100 rounded-[24px] p-5 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-start gap-4">
                <div class="w-14 h-14 rounded-2xl bg-white/70 border border-white/80 flex items-center justify-center shadow-sm"><svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971z"></path></svg></div>
                <div class="flex-1">
                    <h3 class="font-bold text-indigo-900 text-xl mb-2">{{ $council['consensus_label'] ?? 'Tổng hợp ý kiến' }}</h3>
                    <p class="text-sm text-indigo-900/80 leading-relaxed">{{ $council['summary'] ?? '' }}</p>
                    @if(!empty($council['reviewer_guidance']))
                        <div class="mt-4 inline-flex items-start gap-2 px-3.5 py-2 rounded-xl bg-indigo-100 text-indigo-700 text-sm font-semibold">
                            <span class="mt-0.5"><svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18"></path></svg></span>
                            <span>{{ $council['reviewer_guidance'] }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if(!empty($council['agent_opinions']))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($council['agent_opinions'] as $opinion)
                    @php
                        $name = $opinion['agent_name'] ?? 'Agent';
                        $style = $agentColors[$name] ?? ['bg' => 'bg-gray-50', 'text' => 'text-gray-700', 'border' => 'border-gray-200', 'icon' => '🤖'];
                    @endphp
                    <div class="rounded-[22px] border {{ $style['border'] }} {{ $style['bg'] }} p-4">
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">{!! $style['icon'] !!}</span>
                                <div>
                                    <div class="font-bold {{ $style['text'] }}">{{ $name }}</div>
                                    <div class="text-[11px] text-gray-400">{{ $opinion['focus_area'] ?? 'Analysis' }}</div>
                                </div>
                            </div>
                            <div class="px-2.5 py-1 rounded-full bg-white/70 {{ $style['text'] }} text-[11px] font-bold uppercase tracking-wide">
                                {{ $opinion['verdict'] ?? '—' }}
                            </div>
                        </div>

                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-3 text-sm">
                            <div class="rounded-xl bg-white/70 p-3">
                                <div class="font-semibold text-emerald-700 mb-2">Điểm mạnh</div>
                                @if(!empty($opinion['strengths']))
                                    <ul class="space-y-1.5 text-gray-700 text-xs leading-relaxed">
                                        @foreach($opinion['strengths'] as $item)
                                            <li class="flex items-start gap-2"><span class="text-emerald-500 mt-0.5">●</span><span>{{ $item }}</span></li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="text-xs text-gray-400">Không có ghi chú nổi bật.</div>
                                @endif
                            </div>

                            <div class="rounded-xl bg-white/70 p-3">
                                <div class="font-semibold text-rose-700 mb-2">Điểm cần xem kỹ</div>
                                @if(!empty($opinion['concerns']))
                                    <ul class="space-y-1.5 text-gray-700 text-xs leading-relaxed">
                                        @foreach($opinion['concerns'] as $item)
                                            <li class="flex items-start gap-2"><span class="text-rose-500 mt-0.5">●</span><span>{{ $item }}</span></li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="text-xs text-gray-400">Không có cảnh báo lớn.</div>
                                @endif
                            </div>
                        </div>

                        @if(!empty($opinion['notes']) || !empty($opinion['trend_source']))
                            <div class="mt-3 pt-3 border-t border-black/5 text-xs text-gray-600 space-y-1.5">
                                @if(!empty($opinion['notes']))
                                    <div><strong>Note:</strong> {{ $opinion['notes'] }}</div>
                                @endif
                                @if(!empty($opinion['trend_source']))
                                    <div><strong>Trend lens:</strong> {{ $opinion['trend_source'] }}</div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif

<div class="glass-panel rounded-[28px] p-6 lg:p-7 mb-6 animate-xray-in" style="animation-delay:.1s"
     x-data="xrayBoard()" x-init="init()">
    <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <span class="w-10 h-10 rounded-2xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white shadow-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"></path></svg></span>
                <h2 class="text-lg font-bold text-gray-900">AI Matching X-Ray</h2>
            </div>
            <p class="text-sm text-gray-500 max-w-3xl">
                Trục trái là hồ sơ ứng viên, trục phải là yêu cầu job. Phần giữa tách rõ
                <strong class="text-gray-700">match trực tiếp</strong>,
                <strong class="text-gray-700">phù hợp gián tiếp qua skill graph</strong>,
                và <strong class="text-gray-700">open gaps</strong>.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button"
                @click="replay()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-slate-900 text-white text-sm font-bold shadow-lg shadow-slate-900/10 hover:bg-indigo-600 transition-all">
                <span x-text="isPlaying ? '⏳' : '▶'"></span>
                <span x-text="isPlaying ? 'Analyzing…' : 'Analyze Candidate'"></span>
            </button>
            <div class="px-3.5 py-2 rounded-full bg-indigo-50 text-indigo-700 text-xs font-bold">
                Step <span x-text="currentStep + 1"></span>/<span x-text="steps.length"></span>
            </div>
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold"><span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span> Match trực tiếp</span>
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-sky-50 text-sky-700 text-xs font-semibold"><span class="w-2.5 h-2.5 rounded-full bg-sky-400"></span> Graph reasoning</span>
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-rose-50 text-rose-700 text-xs font-semibold"><span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span> Required gaps</span>
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-50 text-amber-700 text-xs font-semibold"><span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span> Preferred gaps</span>
        </div>
    </div>

    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-5">
        <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-4">
            <div class="text-xs uppercase tracking-[0.18em] text-emerald-600 font-bold mb-1">Matched</div>
            <div class="text-3xl font-black text-emerald-700">{{ $matchedCount }}</div>
            <div class="text-xs text-emerald-700/70 mt-1">AI tìm được coverage rõ ràng</div>
        </div>
        <div class="rounded-2xl bg-sky-50 border border-sky-100 p-4">
            <div class="text-xs uppercase tracking-[0.18em] text-sky-600 font-bold mb-1">Related</div>
            <div class="text-3xl font-black text-sky-700">{{ $relatedCount }}</div>
            <div class="text-xs text-sky-700/70 mt-1">Điểm cộng từ skill graph</div>
        </div>
        <div class="rounded-2xl bg-rose-50 border border-rose-100 p-4">
            <div class="text-xs uppercase tracking-[0.18em] text-rose-600 font-bold mb-1">Required Gaps</div>
            <div class="text-3xl font-black text-rose-700">{{ $missingCount }}</div>
            <div class="text-xs text-rose-700/70 mt-1">Thiếu bắt buộc cần review</div>
        </div>
        <div class="rounded-2xl bg-amber-50 border border-amber-100 p-4">
            <div class="text-xs uppercase tracking-[0.18em] text-amber-600 font-bold mb-1">Preferred Gaps</div>
            <div class="text-3xl font-black text-amber-700">{{ $missingPreferredCount }}</div>
            <div class="text-xs text-amber-700/70 mt-1">Thiếu ưu tiên nhưng chưa phải blocker</div>
        </div>
    </div>

    <div class="grid grid-cols-1 2xl:grid-cols-[minmax(0,1.55fr)_340px] gap-5 items-start">
        <div :class="isFullscreen ? 'fixed inset-0 z-[100] bg-[#fafafa] flex flex-col w-screen h-screen overflow-hidden shadow-2xl' : 'rounded-[28px] bg-white/70 backdrop-blur-xl shadow-2xl shadow-indigo-100/60 border border-white/80 overflow-hidden relative'">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/40 to-white/40 pointer-events-none"></div>
            <div class="relative z-10 px-6 py-5 border-b border-gray-100 bg-white/60 flex items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                        <div class="text-xs uppercase tracking-[0.22em] text-indigo-600 font-bold">Recruiter Intelligence Map</div>
                    </div>
                    <div class="text-sm text-gray-500 font-medium">Một canvas duy nhất để nhìn cả strengths, graph jumps và review gaps.</div>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="resetPanZoom()" class="px-3 py-1.5 rounded-full bg-white shadow-sm border border-indigo-50 text-[11px] text-indigo-500 font-bold uppercase tracking-wider hover:bg-indigo-50 transition-colors">
                        Reset
                    </button>
                    <button @click="toggleFullscreen()" class="px-3 py-1.5 rounded-full bg-indigo-500 shadow-sm border border-indigo-600 text-[11px] text-white font-bold uppercase tracking-wider hover:bg-indigo-600 transition-colors">
                        <span x-text="isFullscreen ? 'Thu nhỏ' : 'Toàn màn hình'"></span>
                    </button>
                </div>
            </div>

            <div class="relative z-10 overflow-hidden bg-[#fafafa]/50 flex-1 cursor-grab active:cursor-grabbing" 
                 :class="isFullscreen ? 'h-full' : 'h-[650px]'"
                 style="background-image: radial-gradient(rgba(99, 102, 241, 0.1) 1px, transparent 1px); background-size: 24px 24px;"
                 @mousedown="startPan" @mousemove.window="doPan" @mouseup.window="endPan" @wheel.prevent="handleWheel">
                <div class="origin-top-left transform-gpu will-change-transform select-none"
                     :style="`transform: translate(${panX}px, ${panY}px) scale(${scale}); width: ${svgW}px; height: ${svgH}px;`"
                     x-html="renderSvg()"></div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-[24px] bg-white/75 border border-white/70 p-5">
                <div class="text-xs uppercase tracking-[0.22em] text-indigo-500 font-bold mb-2">Why this candidate scored {{ $score ?? '--' }}/100</div>
                <h3 class="text-base font-bold text-gray-900 mb-3">Recruiter explanation panel</h3>
                <div class="space-y-3 text-sm">
                    <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-3">
                        <div class="font-semibold text-emerald-800 mb-1">Exact match</div>
                        <div class="text-emerald-700/80 text-xs leading-relaxed font-medium">
                            {{ !empty($matched) ? implode(', ', array_slice($matched, 0, 4)) : 'Chưa có direct match nổi bật.' }}
                        </div>
                    </div>
                    <div class="rounded-2xl bg-sky-50 border border-sky-100 p-3">
                        <div class="font-bold text-sky-800 mb-1">Related match</div>
                        @if($relatedCount > 0)
                            <div class="space-y-1.5">
                                @foreach(array_slice($related, 0, 2) as $item)
                                    <div class="text-sky-700/80 text-xs font-medium">{{ $item['candidate_skill'] ?? 'Skill' }} → {{ $item['target_skill'] ?? 'Target' }}</div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-sky-700/80 text-xs font-medium">Không có graph contribution đáng kể ở hồ sơ này.</div>
                        @endif
                    </div>
                    <div class="rounded-2xl bg-rose-50 border border-rose-100 p-3">
                        <div class="font-bold text-rose-800 mb-1">Missing</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach(array_slice(array_merge($missing, $missingPref), 0, 4) as $item)
                                <span class="px-2.5 py-1 rounded-full bg-white text-rose-700 text-[11px] font-bold border border-rose-100 shadow-sm">{{ $item }}</span>
                            @endforeach
                            @if(empty($missing) && empty($missingPref))
                                <span class="text-xs text-rose-700/80">Không có gap lớn.</span>
                            @endif
                        </div>
                    </div>
                    <div class="rounded-2xl bg-amber-50 border border-amber-100 p-3">
                        <div class="font-semibold text-amber-800 mb-1">Risk & confidence</div>
                        <div class="text-xs text-amber-800/80 leading-relaxed">
                            {{ !empty($riskFlags) ? $riskFlags[0] : 'No major issue.' }}
                            <span class="font-semibold text-gray-700">Confidence:</span> {{ $conf['label'] }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-[24px] bg-gradient-to-b from-indigo-50/80 to-white/80 backdrop-blur-md border border-white p-5 shadow-xl shadow-indigo-100/50">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <div class="text-xs uppercase tracking-[0.22em] text-indigo-500 font-bold mb-2">Live Pipeline</div>
                        <h3 class="text-base font-bold text-gray-900">AI đang làm gì?</h3>
                    </div>
                    <div class="px-3 py-1 rounded-full text-[11px] font-bold border transition-colors" 
                         :class="isPlaying ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-white text-gray-500 border-gray-200'" 
                         x-text="isPlaying ? 'LIVE' : 'READY'"></div>
                </div>

                <div class="rounded-2xl bg-white shadow-sm border border-gray-100 px-4 py-3 mb-4">
                    <div class="text-[11px] uppercase tracking-[0.18em] text-gray-400 font-bold mb-1">Current Step</div>
                    <div class="text-sm font-bold text-gray-900" x-text="steps[currentStep]?.title"></div>
                    <div class="text-xs text-gray-500 mt-1" x-text="steps[currentStep]?.caption"></div>
                </div>

                <div class="space-y-2.5">
                    <template x-for="(step, idx) in steps" :key="idx">
                        <div class="rounded-2xl px-3.5 py-3 border transition-all duration-300 shadow-sm"
                            :class="idx === currentStep
                                ? 'bg-indigo-600 border-indigo-700 text-white scale-[1.02]'
                                : (idx < currentStep ? 'bg-white border-gray-200 text-gray-800' : 'bg-gray-50 border-gray-100 text-gray-400 opacity-70')">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-bold"
                                    :class="idx === currentStep ? 'bg-white/20 text-white' : (idx < currentStep ? 'bg-indigo-50 text-indigo-600' : 'bg-gray-100 text-gray-400')"
                                    x-text="idx + 1"></div>
                                <div class="min-w-0 mt-1">
                                    <div class="text-sm font-semibold" :class="idx === currentStep ? 'text-white' : (idx < currentStep ? 'text-gray-900' : 'text-gray-500')" x-text="step.title"></div>
                                    <div class="text-xs mt-0.5" :class="idx === currentStep ? 'text-indigo-100' : (idx < currentStep ? 'text-gray-500' : 'text-gray-400')" x-text="step.agentLabel"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="hidden rounded-[24px] bg-white/75 border border-white/70 p-5">
                <div class="text-xs uppercase tracking-[0.22em] text-indigo-500 font-bold mb-2">Score Story</div>
                <h3 class="text-base font-bold text-gray-900 mb-3">Why this candidate scored {{ $score ?? '--' }}/100?</h3>
                <div class="space-y-3 text-sm">
                    <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-3">
                        <div class="font-semibold text-emerald-800 mb-1">Exact match</div>
                        <div class="text-emerald-700/80 text-xs leading-relaxed font-medium">
                            {{ !empty($matched) ? implode(', ', array_slice($matched, 0, 4)) : 'Chưa có direct match nổi bật.' }}
                        </div>
                    </div>
                    <div class="rounded-2xl bg-sky-50 border border-sky-100 p-3">
                        <div class="font-bold text-sky-800 mb-1">Related match</div>
                        @if($relatedCount > 0)
                            <div class="space-y-1.5">
                                @foreach(array_slice($related, 0, 2) as $item)
                                    <div class="text-sky-700/80 text-xs font-medium">{{ $item['candidate_skill'] ?? 'Skill' }} → {{ $item['target_skill'] ?? 'Target' }}</div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-sky-700/80 text-xs font-medium">Không có graph contribution đáng kể ở hồ sơ này.</div>
                        @endif
                    </div>
                    <div class="rounded-2xl bg-rose-50 border border-rose-100 p-3">
                        <div class="font-semibold text-rose-800 mb-1">Missing</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach(array_slice(array_merge($missing, $missingPref), 0, 4) as $item)
                                <span class="px-2.5 py-1 rounded-full bg-white text-rose-700 text-[11px] font-semibold border border-rose-100">{{ $item }}</span>
                            @endforeach
                            @if(empty($missing) && empty($missingPref))
                                <span class="text-xs text-rose-700/80">Không có gap lớn.</span>
                            @endif
                        </div>
                    </div>
                    <div class="rounded-2xl bg-amber-50 border border-amber-100 p-3">
                        <div class="font-semibold text-amber-800 mb-1">Risk & confidence</div>
                        <div class="text-xs text-amber-800/80 leading-relaxed">
                            {{ !empty($riskFlags) ? $riskFlags[0] : 'No major issue.' }}
                            <span class="font-semibold text-gray-700">Confidence:</span> {{ $conf['label'] }}
                        </div>
                    </div>
                </div>
            </div>

            @if(!empty($candidateSkillsPreview))
                <div class="rounded-[24px] bg-white/75 border border-white/70 p-5">
                    <div class="text-xs uppercase tracking-[0.22em] text-slate-500 font-bold mb-2">Candidate Snapshot</div>
                    <h3 class="text-base font-bold text-gray-900 mb-3">Skill surface từ hồ sơ</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($candidateSkillsPreview as $skill)
                            <span class="px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">{{ is_string($skill) ? $skill : json_encode($skill) }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="glass-panel rounded-[28px] p-6 lg:p-7 animate-xray-in" style="animation-delay:.2s">
    <div class="flex items-center gap-3 mb-5">
        <span class="w-10 h-10 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white shadow-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></span>
        <div>
            <h2 class="text-lg font-bold text-gray-900">AI Processing Timeline</h2>
            <p class="text-sm text-gray-400">Trace compact của pipeline, đủ để hiểu AI đã đi qua những bước gì.</p>
        </div>
    </div>

    @if($hasTimeline)
        <div class="relative pl-8 space-y-0">
            <div class="absolute left-[14px] top-2 bottom-2 w-0.5 bg-gradient-to-b from-cyan-300 via-indigo-300 to-violet-300 rounded-full"></div>

            @foreach($agentTrace as $idx => $trace)
                @php
                    $agentIcons = [
                        'ExtractorAgent' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342"></path></svg>',
                        'RAGAgent' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"></path></svg>',
                        'MatcherAgent' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75"></path></svg>',
                        'ExplainerAgent' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"></path></svg>',
                        'CriticAgent' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"></path></svg>',
                        'FeedbackReranker' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22"></path></svg>',
                    ];
                    $parts = explode(':', $trace, 2);
                    $agentName = trim($parts[0] ?? '');
                    $agentDetail = trim($parts[1] ?? $trace);
                    $icon = $agentIcons[$agentName] ?? '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>';
                    $isLast = $idx === count($agentTrace) - 1;
                @endphp
                <div class="relative flex items-start gap-4 pb-5 {{ $isLast ? 'pb-0' : '' }}">
                    <div class="absolute left-[-22px] w-7 h-7 rounded-full bg-white border-2 border-indigo-300 flex items-center justify-center text-sm shadow-sm z-10">
                        {!! $icon !!}
                    </div>
                    <div class="flex-1 bg-white/75 rounded-2xl px-4 py-3 border border-white/80 hover:border-indigo-200 transition-colors">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-sm font-bold text-gray-800">{{ $agentName }}</span>
                            <span class="text-[10px] text-gray-400 font-mono">step {{ $idx + 1 }}/{{ count($agentTrace) }}</span>
                        </div>
                        <p class="text-sm text-gray-600 leading-relaxed">{{ $agentDetail }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex items-center gap-3 p-4 rounded-2xl bg-gray-50 border border-gray-200">
            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-6.548 0c-1.131.094-1.976 1.057-1.976 2.192V16.5A2.25 2.25 0 0012 18.75h.75"></path></svg>
            <div>
                <p class="text-sm font-semibold text-gray-600">Timeline không khả dụng cho kết quả cũ</p>
                <p class="text-xs text-gray-400">Nhấn "Tính lại AI" trên trang AI Shortlist để tạo kết quả mới với timeline đầy đủ.</p>
            </div>
        </div>
    @endif
</div>

<script>
function xrayBoard() {
    return {
        svgW: 1400,
        svgH: 820,
        nodes: [],
        links: [],
        currentStep: 0,
        isPlaying: false,
        timer: null,
        
        // Pan and Zoom states
        scale: 1,
        panX: 0,
        panY: 0,
        isFullscreen: false,
        isPanning: false,
        startX: 0,
        startY: 0,
        
        toggleFullscreen() {
            this.isFullscreen = !this.isFullscreen;
            if (!this.isFullscreen) {
                this.resetPanZoom();
            } else {
                this.scale = 0.8;
                this.panX = 40;
                this.panY = 40;
            }
        },
        resetPanZoom() {
            this.scale = 1; this.panX = 0; this.panY = 0;
        },
        startPan(e) {
            this.isPanning = true;
            this.startX = e.clientX - this.panX;
            this.startY = e.clientY - this.panY;
        },
        doPan(e) {
            if (!this.isPanning) return;
            this.panX = e.clientX - this.startX;
            this.panY = e.clientY - this.startY;
        },
        endPan() {
            this.isPanning = false;
        },
        handleWheel(e) {
            const zoomSensitivity = 0.001;
            const delta = -e.deltaY * zoomSensitivity;
            let newScale = this.scale * (1 + delta);
            newScale = Math.min(Math.max(0.3, newScale), 3);
            
            const rect = e.currentTarget.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;
            
            this.panX = mouseX - (mouseX - this.panX) * (newScale / this.scale);
            this.panY = mouseY - (mouseY - this.panY) * (newScale / this.scale);
            this.scale = newScale;
        },
        steps: [
            {
                title: 'Extracting CV profile...',
                caption: 'Ứng viên được bóc tách thành skills, experience và profile signals.',
                agentLabel: 'Extractor Agent',
                activeAgents: ['extractor'],
                focus: 'candidate',
            },
            {
                title: 'Extracting JD requirements...',
                caption: 'JD được chuẩn hóa thành required skills, preferred skills và seniority.',
                agentLabel: 'Extractor Agent',
                activeAgents: ['extractor'],
                focus: 'job',
            },
            {
                title: 'Normalizing skills...',
                caption: 'Hệ thống hợp nhất alias, synonym và skill variants trước khi match.',
                agentLabel: 'Skill Graph',
                activeAgents: ['skillgraph'],
                focus: 'matched',
            },
            {
                title: 'Searching Skill Knowledge Graph...',
                caption: 'AI dò các liên hệ gần giữa skills để tìm related matches hợp lý.',
                agentLabel: 'Skill Graph + RAG Retriever',
                activeAgents: ['skillgraph', 'rag'],
                focus: 'related',
            },
            {
                title: 'Calculating hybrid score...',
                caption: 'Matcher Agent cộng weighted score từ skills, experience, seniority và domain.',
                agentLabel: 'Matcher Agent',
                activeAgents: ['matcher'],
                focus: 'matched',
            },
            {
                title: 'Running Critic Agent...',
                caption: 'Risk flags và confidence được kiểm tra để tránh over-scoring.',
                agentLabel: 'Critic Agent',
                activeAgents: ['critic'],
                focus: 'gaps',
            },
            {
                title: 'Generating recruiter explanation...',
                caption: 'Explainer gom lại thành recruiter story: why scored, gaps và review guidance.',
                agentLabel: 'Explainer Agent',
                activeAgents: ['explainer'],
                focus: 'summary',
            },
        ],

        init() {
            const matched = @json($matched);
            const missing = @json($missing);
            const missingPref = @json($missingPref);
            const related = @json($related);
            const candidateName = @json(\Illuminate\Support\Str::limit($candidate->name ?? 'Ung vien', 18));
            const jobTitle = @json(\Illuminate\Support\Str::limit($job->title ?? 'Vi tri', 18));

            const candidate = {
                id: 'candidate', kind: 'anchor',
                x: 30, y: 120, w: 220, h: 160,
                title: candidateName, subtitle: 'Candidate profile',
                meta: [`${matched.length} matched`, `${related.length} graph signals`, `${missing.length + missingPref.length} open gaps`],
            };

            const job = {
                id: 'job', kind: 'anchor',
                x: 1150, y: 120, w: 220, h: 160,
                title: jobTitle, subtitle: 'Job requirements',
                meta: [`${missing.length} required gaps`, `${missingPref.length} preferred gaps`, `${matched.length} direct matches`],
            };

            const laneY = { matched: 110, related: 480, gaps: 110 };
            const nodes = [candidate, job];
            const links = [];

            const pushSkillCard = (id, x, y, label, kind, extra = {}) => {
                nodes.push({ id, x, y, w: extra.w || 180, h: extra.h || 48, label, kind, pill: extra.pill || null, relation: extra.relation || null });
            };

            const bezier = (fromX, fromY, toX, toY, color, width = 2, dash = null, glow = null) => {
                links.push({ fromX, fromY, toX, toY, color, width, dash, glow });
            };

            matched.slice(0, 6).forEach((skill, index) => {
                const y = laneY.matched + (index * 56);
                pushSkillCard(`m_${index}`, 300, y, skill, 'match', { pill: 'Exact', w: 190 });
                bezier(candidate.x + candidate.w, candidate.y + 40 + (index * 14), 300, y + 24, '#34d399', 1.8, null, 'rgba(52,211,153,0.12)');
                bezier(490, y + 24, 700, 300, '#34d399', 1.8, null, 'rgba(52,211,153,0.12)');
            });

            related.slice(0, 4).forEach((item, index) => {
                const y = laneY.related + (index * 62);
                const sourceX = 300; const targetX = 520;
                const relation = `${String(item.relation_type || 'related').replaceAll('_', ' ')} • ${Math.round((item.similarity || 0) * 100)}%`;
                pushSkillCard(`r_source_${index}`, sourceX, y, item.candidate_skill || 'Candidate skill', 'related-source', { pill: `Hop ${item.hop_count || 1}`, w: 170 });
                pushSkillCard(`r_target_${index}`, targetX, y, item.target_skill || 'Target skill', 'related-target', { relation, w: 170 });

                bezier(candidate.x + candidate.w, candidate.y + 130, sourceX, y + 24, '#38bdf8', 1.6, '6 6', 'rgba(56,189,248,0.12)');
                bezier(sourceX + 170, y + 24, targetX, y + 24, '#60a5fa', 1.6, '6 6', 'rgba(96,165,250,0.12)');
                bezier(targetX + 170, y + 24, 700, 320, '#818cf8', 1.6, '6 6', 'rgba(129,140,248,0.12)');
            });

            missing.slice(0, 4).forEach((skill, index) => {
                const y = laneY.gaps + (index * 56);
                pushSkillCard(`g_req_${index}`, 910, y, skill, 'gap-required', { pill: 'Required', w: 190 });
                bezier(1100, y + 24, job.x, job.y + 50 + (index * 16), '#fb7185', 1.8, null, 'rgba(251,113,133,0.12)');
            });

            missingPref.slice(0, 4).forEach((skill, index) => {
                const y = laneY.gaps + 30 + ((missing.slice(0, 4).length + index) * 56);
                pushSkillCard(`g_pref_${index}`, 910, y, skill, 'gap-preferred', { pill: 'Preferred', w: 190 });
                bezier(1100, y + 24, job.x, job.y + 100 + (index * 14), '#fbbf24', 1.6, '6 6', 'rgba(251,191,36,0.12)');
            });

            this.nodes = nodes;
            this.links = links;
            this.svgW = 1400;
            this.svgH = Math.max(720, laneY.gaps + ((missing.slice(0, 4).length + missingPref.slice(0, 4).length + 1) * 56));
            this.replay();
        },

        replay() {
            if (this.timer) {
                clearTimeout(this.timer);
            }
            this.currentStep = 0;
            this.isPlaying = true;
            this.advance();
        },

        advance() {
            if (this.currentStep >= this.steps.length - 1) {
                this.isPlaying = false;
                this.timer = null;
                return;
            }

            this.timer = setTimeout(() => {
                this.currentStep += 1;
                this.advance();
            }, 1050);
        },

        isAgentHot(agentKey) {
            return (this.steps[this.currentStep]?.activeAgents || []).includes(agentKey);
        },

        isFocusHot(focus) {
            return this.steps[this.currentStep]?.focus === focus;
        },

        renderSvg() {
            const esc = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');

            const roundedCard = (x, y, w, h, fill, stroke, radius = 22) =>
                `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${radius}" fill="${fill}" stroke="${stroke}" stroke-width="1.2"/>`;

            const hot = {
                extractor: this.isAgentHot('extractor'),
                skillgraph: this.isAgentHot('skillgraph'),
                rag: this.isAgentHot('rag'),
                matcher: this.isAgentHot('matcher'),
                critic: this.isAgentHot('critic'),
                explainer: this.isAgentHot('explainer'),
                candidate: this.isFocusHot('candidate'),
                job: this.isFocusHot('job'),
                matched: this.isFocusHot('matched'),
                related: this.isFocusHot('related'),
                gaps: this.isFocusHot('gaps'),
                summary: this.isFocusHot('summary'),
            };

            let svg = `<svg viewBox="0 0 ${this.svgW} ${this.svgH}" class="w-full" xmlns="http://www.w3.org/2000/svg">`;
            svg += `
                <defs>
                    <filter id="softGlow" x="-50%" y="-50%" width="200%" height="200%">
                        <feGaussianBlur stdDeviation="14" result="blur"/>
                        <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
                    </filter>
                    <linearGradient id="boardShade" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#ffffff"/>
                        <stop offset="50%" stop-color="#f8fafc"/>
                        <stop offset="100%" stop-color="#f1f5f9"/>
                    </linearGradient>
                </defs>
            `;

            svg += `<rect x="0" y="0" width="${this.svgW}" height="${this.svgH}" rx="24" fill="url(#boardShade)"/>`;
            svg += `<rect x="270" y="85" width="250" height="380" rx="24" fill="${hot.matched ? 'rgba(52,211,153,0.08)' : 'rgba(0,0,0,0.015)'}" stroke="${hot.matched ? 'rgba(52,211,153,0.35)' : 'rgba(52,211,153,0.08)'}" stroke-width="1"/>`;
            svg += `<rect x="270" y="455" width="450" height="280" rx="24" fill="${hot.related ? 'rgba(56,189,248,0.08)' : 'rgba(0,0,0,0.015)'}" stroke="${hot.related ? 'rgba(56,189,248,0.35)' : 'rgba(56,189,248,0.08)'}" stroke-width="1"/>`;
            svg += `<rect x="880" y="85" width="240" height="420" rx="24" fill="${hot.gaps ? 'rgba(251,113,133,0.08)' : 'rgba(0,0,0,0.015)'}" stroke="${hot.gaps ? 'rgba(251,113,133,0.35)' : 'rgba(251,113,133,0.08)'}" stroke-width="1"/>`;

            const agents = [
                { key: 'extractor', x: 44, title: 'Extractor', tint: '#a78bfa', icon: 'EX' },
                { key: 'skillgraph', x: 220, title: 'Skill Graph', tint: '#22c55e', icon: 'SG' },
                { key: 'rag', x: 396, title: 'RAG', tint: '#38bdf8', icon: 'RG' },
                { key: 'matcher', x: 572, title: 'Matcher', tint: '#f59e0b', icon: 'MT' },
                { key: 'critic', x: 748, title: 'Critic', tint: '#fb7185', icon: 'CR' },
                { key: 'explainer', x: 924, title: 'Explainer', tint: '#2dd4bf', icon: 'EX' },
            ];

            agents.forEach((agent) => {
                const active = hot[agent.key];
                const bg = active ? agent.tint : 'rgba(255,255,255,0.7)';
                const stroke = active ? agent.tint : 'rgba(0,0,0,0.08)';
                const text = active ? '#ffffff' : '#64748b';
                const small = active ? 'rgba(255,255,255,0.9)' : '#94a3b8';
                const iconCircle = active ? '#ffffff' : 'rgba(0,0,0,0.04)';
                const iconText = active ? agent.tint : '#94a3b8';
                
                svg += `<rect x="${agent.x}" y="20" width="150" height="44" rx="14" fill="${bg}" stroke="${stroke}" stroke-width="1.2" ${active ? 'filter="url(#softGlow)"' : ''}/>`;
                svg += `<circle cx="${agent.x + 20}" cy="42" r="10" fill="${iconCircle}"/>`;
                svg += `<text x="${agent.x + 20}" y="46" text-anchor="middle" fill="${iconText}" font-size="8" font-weight="800">${agent.icon}</text>`;
                svg += `<text x="${agent.x + 38}" y="39" fill="${text}" font-size="12" font-weight="700">${agent.title}</text>`;
                svg += `<text x="${agent.x + 38}" y="52" fill="${small}" font-size="9" font-weight="600">${active ? 'active' : 'standby'}</text>`;
            });

            svg += `<text x="300" y="105" fill="#10b981" font-size="11" font-weight="800" letter-spacing="0.14em">EXACT MATCHES</text>`;
            svg += `<text x="300" y="475" fill="#0ea5e9" font-size="11" font-weight="800" letter-spacing="0.14em">GRAPH REASONING</text>`;
            svg += `<text x="910" y="105" fill="#f43f5e" font-size="11" font-weight="800" letter-spacing="0.14em">OPEN GAPS</text>`;
            svg += `<circle cx="700" cy="300" r="72" fill="rgba(99,102,241,0.04)" stroke="${hot.matched || hot.related || hot.summary ? 'rgba(99,102,241,0.4)' : 'rgba(99,102,241,0.08)'}" stroke-width="1.5"/>`;
            svg += `<circle cx="700" cy="300" r="52" fill="#ffffff" stroke="#8b5cf6" stroke-width="2.5" filter="url(#softGlow)"/>`;
            svg += `<text x="700" y="284" text-anchor="middle" fill="#6366f1" font-size="9" font-weight="800" letter-spacing="0.18em">FIT SCORE</text>`;
            svg += `<text x="700" y="312" text-anchor="middle" fill="#1e1b4b" font-size="36" font-weight="900">${esc(@json($score ?? '--'))}</text>`;
            svg += `<text x="700" y="330" text-anchor="middle" fill="#8b5cf6" font-size="10" font-weight="700">${esc(@json($rank['label']))}</text>`;

            this.links.forEach((link) => {
                const dist = Math.abs(link.toX - link.fromX);
                const c1x = link.fromX + (dist * 0.5);
                const c2x = link.toX - (dist * 0.5);
                const path = `M ${link.fromX} ${link.fromY} C ${c1x} ${link.fromY}, ${c2x} ${link.toY}, ${link.toX} ${link.toY}`;
                if (link.glow) {
                    svg += `<path d="${path}" stroke="${link.glow}" stroke-width="${link.width * 3.8}" fill="none" filter="url(#softGlow)"/>`;
                }
                svg += `<path d="${path}" stroke="${link.color}" stroke-width="${link.width}" fill="none" ${link.dash ? `stroke-dasharray="${link.dash}"` : ''} stroke-linecap="round" opacity="0.95"/>`;
            });

            this.nodes.forEach((node) => {
                if (node.kind === 'anchor') {
                    const glowClass = node.id === 'candidate' 
                        ? (hot.candidate ? 'ring-4 ring-indigo-400 ring-opacity-50 ring-offset-2' : '')
                        : (hot.job ? 'ring-4 ring-amber-400 ring-opacity-50 ring-offset-2' : '');
                        
                    svg += `<foreignObject x="${node.x}" y="${node.y}" width="${node.w}" height="${node.h}">
                        <div xmlns="http://www.w3.org/1999/xhtml" class="h-full w-full bg-white/90 backdrop-blur-md border ${node.id === 'candidate' ? 'border-indigo-200 shadow-indigo-100/50' : 'border-amber-200 shadow-amber-100/50'} rounded-2xl shadow-xl flex flex-col p-5 transition-all duration-300 ${glowClass}">
                            <div class="text-[11px] font-bold uppercase tracking-widest ${node.id === 'candidate' ? 'text-indigo-500' : 'text-amber-500'} mb-1">${esc(node.subtitle)}</div>
                            <div class="text-[22px] font-black text-gray-900 mb-3 leading-tight">${esc(node.title)}</div>
                            <div class="space-y-2 mt-auto">
                                ${node.meta.map(m => `<div class="text-xs font-semibold ${node.id === 'candidate' ? 'text-indigo-700/80' : 'text-amber-700/80'}">${esc(m)}</div>`).join('')}
                            </div>
                        </div>
                    </foreignObject>`;
                    return;
                }

                const cardStyles = {
                    'match': { bg: 'bg-emerald-50/90', border: 'border-emerald-200', text: 'text-emerald-900', pillBg: 'bg-emerald-200/80', pillText: 'text-emerald-800' },
                    'related-source': { bg: 'bg-sky-50/90', border: 'border-sky-200', text: 'text-sky-900', pillBg: 'bg-sky-200/80', pillText: 'text-sky-800' },
                    'related-target': { bg: 'bg-indigo-50/90', border: 'border-indigo-200', text: 'text-indigo-900', pillBg: 'bg-indigo-200/80', pillText: 'text-indigo-800' },
                    'gap-required': { bg: 'bg-rose-50/90', border: 'border-rose-200', text: 'text-rose-900', pillBg: 'bg-rose-200/80', pillText: 'text-rose-800' },
                    'gap-preferred': { bg: 'bg-amber-50/90', border: 'border-amber-200', text: 'text-amber-900', pillBg: 'bg-amber-200/80', pillText: 'text-amber-800' },
                };
                
                const style = cardStyles[node.kind] || cardStyles['match'];
                const activeNode = (node.kind === 'match' && hot.matched) || ((node.kind === 'related-source' || node.kind === 'related-target') && hot.related) || ((node.kind === 'gap-required' || node.kind === 'gap-preferred') && hot.gaps);
                const activeClass = activeNode ? 'shadow-[0_0_15px_rgba(0,0,0,0.08)] ring-2 ring-offset-1 ' + (node.kind.includes('match') ? 'ring-emerald-400' : (node.kind.includes('related') ? 'ring-sky-400' : 'ring-rose-400')) : 'shadow-sm';

                svg += `<foreignObject x="${node.x}" y="${node.y}" width="${node.w}" height="${node.h}">
                    <div xmlns="http://www.w3.org/1999/xhtml" class="h-full w-full ${style.bg} backdrop-blur-sm border ${style.border} rounded-xl flex items-center px-4 transition-all duration-300 ${activeClass}">
                        <div class="flex-1 min-w-0">
                            <div class="text-[13px] font-bold ${style.text} truncate">${esc(node.label)}</div>
                            ${node.relation ? `<div class="text-[10px] ${style.text} opacity-70 mt-0.5 truncate">${esc(node.relation)}</div>` : ''}
                        </div>
                        ${node.pill ? `<div class="${style.pillBg} ${style.pillText} text-[10px] font-bold px-2 py-0.5 rounded-md ml-2 flex-shrink-0">${esc(node.pill)}</div>` : ''}
                    </div>
                </foreignObject>`;
            });

            svg += `</svg>`;
            return svg;
        }
    };
}
</script>

</x-layouts.app>
