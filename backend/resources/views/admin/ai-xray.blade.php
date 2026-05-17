<x-layouts.app title="AI X-Ray — {{ $candidate->name ?? 'Ứng viên' }}">
@php
    $score = $aiResult['fit_score'] ?? null;
    $scoreColor = $score === null ? 'gray' : ($score >= 80 ? 'emerald' : ($score >= 60 ? 'amber' : 'red'));
    $scoreBg = ['gray'=>'from-gray-400 to-gray-500','emerald'=>'from-emerald-400 to-teal-500','amber'=>'from-amber-400 to-orange-500','red'=>'from-red-400 to-pink-500'][$scoreColor];
    $rankLabels = ['high_fit'=>['label'=>'Phù hợp cao','bg'=>'bg-emerald-100','text'=>'text-emerald-700'],'medium_fit'=>['label'=>'Phù hợp vừa','bg'=>'bg-amber-100','text'=>'text-amber-700'],'low_fit'=>['label'=>'Phù hợp thấp','bg'=>'bg-red-100','text'=>'text-red-700']];
    $rank = $rankLabels[$aiResult['rank_label'] ?? ''] ?? ['label'=>'Chưa rõ','bg'=>'bg-gray-100','text'=>'text-gray-700'];
    $confLabels = ['high'=>['label'=>'Tin cậy cao','bg'=>'bg-green-50','text'=>'text-green-700','icon'=>'🟢'],'medium'=>['label'=>'Tin cậy TB','bg'=>'bg-yellow-50','text'=>'text-yellow-700','icon'=>'🟡'],'low'=>['label'=>'Tin cậy thấp','bg'=>'bg-red-50','text'=>'text-red-700','icon'=>'🔴']];
    $conf = $confLabels[$aiResult['confidence_label'] ?? 'low'] ?? $confLabels['low'];

    $matched = $aiResult['matched_skills'] ?? [];
    $missing = $aiResult['missing_skills'] ?? [];
    $missingPref = $aiResult['missing_preferred_skills'] ?? [];
    $related = $aiResult['related_matches'] ?? [];
    $riskFlags = $aiResult['risk_flags'] ?? [];
    $breakdown = $aiResult['score_breakdown'] ?? [];
    $agentTrace = $aiResult['agent_trace'] ?? [];
    $hasTimeline = !empty($agentTrace);
@endphp

<!-- Header -->
<div class="mb-8">
    <a href="{{ route('admin.jobs.ai-shortlist', $job->id) }}" class="inline-flex items-center text-gray-500 hover:text-indigo-600 mb-6 group transition-colors">
        <div class="w-10 h-10 rounded-xl bg-gray-100 group-hover:bg-indigo-100 flex items-center justify-center mr-3 transition-colors">
            <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </div>
        <span class="font-medium">Quay lại AI Shortlist</span>
    </a>
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-4 mb-2">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-xl">
                    <span class="text-2xl">🔬</span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">AI Matching X-Ray</h1>
                    <p class="text-gray-500 text-sm">{{ $candidate->name ?? 'Ứng viên' }} → {{ $job->title }}</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="{{ $rank['bg'] }} {{ $rank['text'] }} px-4 py-1.5 rounded-full text-sm font-bold">{{ $rank['label'] }}</span>
            <span class="{{ $conf['bg'] }} {{ $conf['text'] }} px-4 py-1.5 rounded-full text-sm font-bold">{{ $conf['icon'] }} {{ $conf['label'] }}</span>
            <a href="{{ route('admin.applications.ai-decision-lab', $application->id) }}"
               class="inline-flex items-center px-4 py-1.5 rounded-full bg-teal-50 text-teal-700 text-sm font-bold hover:bg-teal-600 hover:text-white transition-all duration-200">
                🧪 Decision Lab
            </a>
        </div>
    </div>
</div>

{{-- ═══════ SECTION 1: AI SCORE CARD ═══════ --}}
<div class="glass-panel rounded-2xl p-6 mb-6 animate-xray-in">
    <h2 class="text-lg font-bold text-gray-900 mb-5 flex items-center gap-2">
        <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-white text-sm">📊</span>
        AI Score Card
    </h2>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Donut --}}
        <div class="flex flex-col items-center justify-center">
            <div class="relative w-40 h-40">
                <svg viewBox="0 0 120 120" class="w-full h-full -rotate-90">
                    <circle cx="60" cy="60" r="52" fill="none" stroke="#e5e7eb" stroke-width="12"/>
                    <circle cx="60" cy="60" r="52" fill="none"
                        stroke="{{ $scoreColor === 'emerald' ? '#10b981' : ($scoreColor === 'amber' ? '#f59e0b' : ($scoreColor === 'red' ? '#ef4444' : '#9ca3af')) }}"
                        stroke-width="12" stroke-linecap="round"
                        stroke-dasharray="{{ ($score ?? 0) * 3.267 }} 326.7"
                        class="transition-all duration-1000"/>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-4xl font-black text-gray-900">{{ $score !== null ? number_format($score, 0) : '--' }}</span>
                    <span class="text-xs text-gray-400 font-medium">/ 100 điểm</span>
                </div>
            </div>
            <div class="mt-3 text-center">
                <p class="text-xs text-gray-400">Pipeline: {{ $aiResult['pipeline_version'] ?? 'unknown' }}</p>
                @if($aiResult['generated_at'] ?? null)
                    <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($aiResult['generated_at'])->format('d/m/Y H:i') }}</p>
                @endif
            </div>
        </div>

        {{-- Breakdown bars --}}
        <div class="lg:col-span-2 space-y-3">
            @foreach($breakdown as $key => $bd)
                @php
                    $componentLabels = ['required_skill_coverage'=>'Kỹ năng bắt buộc','preferred_skill_coverage'=>'Kỹ năng ưu tiên','experience_fit'=>'Kinh nghiệm','seniority_fit'=>'Cấp bậc','domain_relevance'=>'Lĩnh vực','confidence_adjustment'=>'Độ tin cậy'];
                    $label = $componentLabels[$key] ?? $key;
                    $raw = is_array($bd) ? ($bd['score'] ?? 0) : 0;
                    $weight = is_array($bd) ? ($bd['weight'] ?? 0) : 0;
                    $weighted = is_array($bd) ? ($bd['weighted'] ?? 0) : 0;
                    $detail = is_array($bd) ? ($bd['detail'] ?? '') : '';
                    $barW = min(100, max(0, $raw * 100));
                    $barColor = $raw >= 0.7 ? 'bg-emerald-400' : ($raw >= 0.4 ? 'bg-amber-400' : 'bg-red-400');
                @endphp
                <div>
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="font-semibold text-gray-700">{{ $label }} <span class="text-gray-400 text-xs">({{ round($weight * 100) }}%)</span></span>
                        <span class="font-bold text-gray-800">{{ number_format($weighted, 1) }} pt</span>
                    </div>
                    <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full {{ $barColor }} transition-all duration-700" style="width: {{ $barW }}%"></div>
                    </div>
                    @if($detail)
                        <p class="text-xs text-gray-400 mt-0.5">{{ $detail }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Risk flags --}}
    @if(!empty($riskFlags))
        <div class="mt-5 pt-5 border-t border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1"><span class="text-orange-500">⚠</span> Cảnh báo rủi ro</h3>
            <div class="space-y-1.5">
                @foreach($riskFlags as $flag)
                    <div class="flex items-start gap-2 text-sm text-orange-700 bg-orange-50 rounded-lg px-3 py-2 border border-orange-100">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>
                        <span>{{ $flag }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

{{-- ═══════ SECTION 1.5: MULTI-AGENT COUNCIL ═══════ --}}
@php
    $council = $aiResult['multi_agent_council'] ?? null;
    $agentColors = [
        'SkillGraphAgent' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200', 'icon' => '🔗'],
        'ExperienceFitAgent' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'icon' => '⏳'],
        'DomainTrendAgent' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'border' => 'border-purple-200', 'icon' => '📈'],
        'RiskCriticAgent' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'border' => 'border-red-200', 'icon' => '⚠️'],
        'ConsensusAgent' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-700', 'border' => 'border-indigo-200', 'icon' => '⚖️'],
    ];
@endphp
@if($council)
    <div class="glass-panel rounded-2xl p-6 mb-6 animate-xray-in" style="animation-delay:.05s">
        <h2 class="text-lg font-bold text-gray-900 mb-5 flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-white text-sm">🤖</span>
            AI Scoring Council
        </h2>

        {{-- Consensus Summary --}}
        <div class="bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-100 rounded-xl p-5 mb-6">
            <div class="flex items-start gap-4">
                <div class="text-3xl">⚖️</div>
                <div>
                    <h3 class="font-bold text-indigo-900 text-lg mb-1">{{ $council['consensus_label'] ?? 'Tổng hợp ý kiến' }}</h3>
                    <p class="text-sm text-indigo-800 leading-relaxed">{{ $council['summary'] ?? '' }}</p>
                    @if(!empty($council['reviewer_guidance']))
                        <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-100 rounded-lg text-xs font-semibold text-indigo-700">
                            <span>💡 Lời khuyên cho Recruiter:</span> {{ $council['reviewer_guidance'] }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Agent Opinions --}}
        @if(!empty($council['agent_opinions']))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($council['agent_opinions'] as $opinion)
                    @php
                        $name = $opinion['agent_name'] ?? 'Agent';
                        $style = $agentColors[$name] ?? ['bg'=>'bg-gray-50','text'=>'text-gray-700','border'=>'border-gray-200','icon'=>'🤖'];
                    @endphp
                    <div class="rounded-xl p-4 border {{ $style['border'] }} {{ $style['bg'] }}">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">{{ $style['icon'] }}</span>
                                <span class="font-bold {{ $style['text'] }}">{{ $name }}</span>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-white/60 {{ $style['text'] }}">
                                {{ $opinion['verdict'] ?? '' }}
                            </span>
                        </div>
                        
                        @if(!empty($opinion['strengths']))
                            <div class="mb-2">
                                <p class="text-xs font-semibold text-emerald-700 mb-1">Điểm mạnh:</p>
                                <ul class="list-disc pl-4 text-xs text-gray-700 space-y-0.5">
                                    @foreach($opinion['strengths'] as $s) <li>{{ $s }}</li> @endforeach
                                </ul>
                            </div>
                        @endif

                        @if(!empty($opinion['concerns']))
                            <div class="mb-2">
                                <p class="text-xs font-semibold text-red-700 mb-1">Điểm yếu / Rủi ro:</p>
                                <ul class="list-disc pl-4 text-xs text-gray-700 space-y-0.5">
                                    @foreach($opinion['concerns'] as $c) <li>{{ $c }}</li> @endforeach
                                </ul>
                            </div>
                        @endif

                        @if(!empty($opinion['notes']))
                            <div class="mt-3 pt-3 border-t border-black/5 text-xs text-gray-600">
                                <strong>Ghi chú:</strong> {{ $opinion['notes'] }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif

{{-- ═══════ SECTION 2: X-RAY GRAPH ═══════ --}}
<div class="glass-panel rounded-2xl p-6 mb-6 animate-xray-in" style="animation-delay:.1s"
     x-data="xrayGraph()" x-init="init()">
    <h2 class="text-lg font-bold text-gray-900 mb-2 flex items-center gap-2">
        <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white text-sm">🔗</span>
        AI Matching X-Ray
    </h2>
    <p class="text-sm text-gray-400 mb-4">Biểu đồ kỹ năng: xanh = phù hợp, đỏ = thiếu bắt buộc, cam = thiếu ưu tiên, xanh dương nét đứt = phù hợp gián tiếp</p>

    {{-- Legend --}}
    <div class="flex flex-wrap gap-4 text-xs text-gray-500 mb-4 pb-4 border-b border-gray-100">
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-400"></span> Phù hợp chính xác</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-400"></span> Thiếu bắt buộc</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-amber-300"></span> Thiếu ưu tiên</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-400"></span> Phù hợp gián tiếp</span>
    </div>

    {{-- SVG Graph rendered via x-html to avoid Alpine x-for + SVG template issues --}}
    <div class="relative overflow-x-auto">
        <div x-html="renderSvg()"></div>
    </div>
</div>

{{-- ═══════ SECTION 3: PROCESSING TIMELINE ═══════ --}}
<div class="glass-panel rounded-2xl p-6 animate-xray-in" style="animation-delay:.2s">
    <h2 class="text-lg font-bold text-gray-900 mb-5 flex items-center gap-2">
        <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-sm">⏱</span>
        AI Processing Timeline
    </h2>

    @if($hasTimeline)
        <div class="relative pl-8 space-y-0">
            {{-- Vertical line --}}
            <div class="absolute left-[14px] top-2 bottom-2 w-0.5 bg-gradient-to-b from-violet-300 via-indigo-300 to-purple-300 rounded-full"></div>

            @foreach($agentTrace as $idx => $trace)
                @php
                    $agentIcons = ['ExtractorAgent'=>'🧠','RAGAgent'=>'📚','MatcherAgent'=>'⚖️','ExplainerAgent'=>'💬','CriticAgent'=>'🔍','FeedbackReranker'=>'📊'];
                    $parts = explode(':', $trace, 2);
                    $agentName = trim($parts[0] ?? '');
                    $agentDetail = trim($parts[1] ?? $trace);
                    $icon = $agentIcons[$agentName] ?? '⚙️';
                    $isLast = $idx === count($agentTrace) - 1;
                @endphp
                <div class="relative flex items-start gap-4 pb-5 {{ $isLast ? 'pb-0' : '' }}">
                    <div class="absolute left-[-22px] w-7 h-7 rounded-full bg-white border-2 border-indigo-300 flex items-center justify-center text-sm shadow-sm z-10">
                        {{ $icon }}
                    </div>
                    <div class="flex-1 bg-gray-50 rounded-xl px-4 py-3 border border-gray-100 hover:border-indigo-200 transition-colors">
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
        <div class="flex items-center gap-3 p-4 rounded-xl bg-gray-50 border border-gray-200">
            <span class="text-2xl">📋</span>
            <div>
                <p class="text-sm font-semibold text-gray-600">Timeline không khả dụng cho kết quả cũ</p>
                <p class="text-xs text-gray-400">Nhấn "Tính lại AI" trên trang AI Shortlist để tạo kết quả mới với timeline đầy đủ.</p>
            </div>
        </div>
    @endif
</div>

<style>
@keyframes xray-in { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
.animate-xray-in { animation: xray-in 0.5s ease-out both; }
</style>

<script>
function xrayGraph() {
    return {
        nodes: [],
        edges: [],
        hoveredNode: null,
        svgW: 900,
        svgH: 400,

        init() {
            const matched = @json($matched);
            const missing = @json($missing);
            const missingPref = @json($missingPref);
            const related = @json($related);

            const relTargets = related.map(r => r.target_skill);
            const relSources = related.map(r => r.candidate_skill);

            const totalRows = Math.max(1, matched.length + missing.length + missingPref.length + related.length);
            this.svgH = Math.max(340, totalRows * 48 + 100);
            const cx = 80, jx = this.svgW - 80, midX = this.svgW / 2;

            const nodes = [
                { id:'candidate', x:cx, y:this.svgH/2, icon:'🧑', label:@json(Str::limit($candidate->name ?? 'Ứng viên', 14)), fill:'#eef2ff', stroke:'#6366f1', tooltip:null },
                { id:'job', x:jx, y:this.svgH/2, icon:'📋', label:@json(Str::limit($job->title ?? 'Vị trí', 14)), fill:'#fef3c7', stroke:'#f59e0b', tooltip:null },
            ];
            const edges = [];
            let row = 0;
            const startY = Math.max(40, (this.svgH - totalRows * 46) / 2);

            matched.forEach(s => {
                const ny = startY + row * 46;
                nodes.push({ id:'m_'+s, x:midX, y:ny, icon:'✓', label:s, fill:'#d1fae5', stroke:'#10b981', tooltip:'Phù hợp chính xác' });
                edges.push({ id:'ce_'+s, x1:cx+20, y1:this.svgH/2, x2:midX-20, y2:ny, color:'#10b981', dashed:false, arrowColor:'green', label:null });
                edges.push({ id:'je_'+s, x1:midX+20, y1:ny, x2:jx-20, y2:this.svgH/2, color:'#10b981', dashed:false, arrowColor:'green', label:null });
                row++;
            });

            related.forEach((r, i) => {
                const ny = startY + row * 46;
                const simPct = Math.round(r.similarity * 100) + '%';
                const relLabel = (r.relation_type || '').replace(/_/g,' ');
                nodes.push({ id:'rs_'+i, x:midX-100, y:ny, icon:'◈', label:r.candidate_skill, fill:'#dbeafe', stroke:'#3b82f6', tooltip:'Kỹ năng ứng viên' });
                nodes.push({ id:'rt_'+i, x:midX+100, y:ny, icon:'◇', label:r.target_skill, fill:'#ede9fe', stroke:'#8b5cf6', tooltip:relLabel+' '+simPct });
                edges.push({ id:'rc_'+i, x1:cx+20, y1:this.svgH/2, x2:midX-120, y2:ny, color:'#3b82f6', dashed:true, arrowColor:'blue', label:null });
                edges.push({ id:'rm_'+i, x1:midX-80, y1:ny, x2:midX+80, y2:ny, color:'#3b82f6', dashed:true, arrowColor:'blue', label:relLabel+' '+simPct });
                edges.push({ id:'rj_'+i, x1:midX+120, y1:ny, x2:jx-20, y2:this.svgH/2, color:'#8b5cf6', dashed:true, arrowColor:'blue', label:null });
                row++;
            });

            missing.forEach(s => {
                if (relTargets.includes(s)) return;
                const ny = startY + row * 46;
                nodes.push({ id:'x_'+s, x:midX+60, y:ny, icon:'✗', label:s, fill:'#fee2e2', stroke:'#ef4444', tooltip:'Thiếu bắt buộc' });
                edges.push({ id:'xe_'+s, x1:midX+80, y1:ny, x2:jx-20, y2:this.svgH/2, color:'#ef4444', dashed:false, arrowColor:'red', label:null });
                row++;
            });

            missingPref.forEach(s => {
                const ny = startY + row * 46;
                nodes.push({ id:'p_'+s, x:midX+60, y:ny, icon:'~', label:s, fill:'#fef9c3', stroke:'#f59e0b', tooltip:'Thiếu ưu tiên' });
                edges.push({ id:'pe_'+s, x1:midX+80, y1:ny, x2:jx-20, y2:this.svgH/2, color:'#fbbf24', dashed:true, arrowColor:'green', label:null });
                row++;
            });

            this.svgH = Math.max(340, row * 46 + 100);
            // Recompute anchor Y after final svgH
            nodes[0].y = this.svgH / 2;
            nodes[1].y = this.svgH / 2;
            edges.forEach(e => {
                if (e.y1 === undefined) return;
                // fix anchor positions for candidate/job
            });

            this.nodes = nodes;
            this.edges = edges;
        },

        renderSvg() {
            const esc = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
            let svg = `<svg viewBox="0 0 ${this.svgW} ${this.svgH}" class="w-full" style="min-height:340px;max-height:600px" xmlns="http://www.w3.org/2000/svg">`;
            svg += `<defs>`;
            svg += `<marker id="ag" markerWidth="8" markerHeight="6" refX="8" refY="3" orient="auto"><path d="M0,0 L8,3 L0,6" fill="#10b981"/></marker>`;
            svg += `<marker id="ar" markerWidth="8" markerHeight="6" refX="8" refY="3" orient="auto"><path d="M0,0 L8,3 L0,6" fill="#ef4444"/></marker>`;
            svg += `<marker id="ab" markerWidth="8" markerHeight="6" refX="8" refY="3" orient="auto"><path d="M0,0 L8,3 L0,6" fill="#3b82f6"/></marker>`;
            svg += `</defs>`;

            // Edges
            this.edges.forEach(e => {
                const dash = e.dashed ? 'stroke-dasharray="6,4"' : '';
                const marker = e.arrowColor === 'red' ? 'url(#ar)' : (e.arrowColor === 'blue' ? 'url(#ab)' : 'url(#ag)');
                svg += `<line x1="${e.x1}" y1="${e.y1}" x2="${e.x2}" y2="${e.y2}" stroke="${e.color}" stroke-width="1.5" ${dash} marker-end="${marker}" opacity="0.8"/>`;
                if (e.label) {
                    const lx = (e.x1 + e.x2) / 2, ly = (e.y1 + e.y2) / 2 - 6;
                    svg += `<text x="${lx}" y="${ly}" text-anchor="middle" fill="#9ca3af" font-size="10">${esc(e.label)}</text>`;
                }
            });

            // Nodes
            this.nodes.forEach(n => {
                const lbl = n.label.length > 14 ? n.label.slice(0,12)+'…' : n.label;
                svg += `<g transform="translate(${n.x},${n.y})">`;
                svg += `<circle r="20" fill="${n.fill}" stroke="${n.stroke}" stroke-width="2.5"/>`;
                svg += `<text text-anchor="middle" dominant-baseline="central" font-size="14">${n.icon}</text>`;
                svg += `<text y="30" text-anchor="middle" fill="#374151" font-size="11" font-weight="600">${esc(lbl)}</text>`;
                if (n.tooltip) {
                    svg += `<title>${esc(n.tooltip)}</title>`;
                }
                svg += `</g>`;
            });

            svg += `</svg>`;
            return svg;
        },

        hoverNode(nodeId) { /* simplified - hover handled via native SVG title */ }
    };
}
</script>
</x-layouts.app>
