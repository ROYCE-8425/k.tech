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
        </div>
    </div>
</div>

{{-- ═══════ SECTION 1: AI SCORE CARD ═══════ --}}
<div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 mb-6 animate-xray-in">
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

{{-- ═══════ SECTION 2: X-RAY GRAPH ═══════ --}}
<div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 mb-6 animate-xray-in" style="animation-delay:.1s"
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

    {{-- SVG Graph --}}
    <div class="relative overflow-x-auto">
        <svg :viewBox="'0 0 ' + svgW + ' ' + svgH" class="w-full" style="min-height: 340px; max-height: 540px;"
             xmlns="http://www.w3.org/2000/svg">
            <defs>
                <marker id="arrow-green" markerWidth="8" markerHeight="6" refX="8" refY="3" orient="auto"><path d="M0,0 L8,3 L0,6" fill="#10b981"/></marker>
                <marker id="arrow-red" markerWidth="8" markerHeight="6" refX="8" refY="3" orient="auto"><path d="M0,0 L8,3 L0,6" fill="#ef4444"/></marker>
                <marker id="arrow-blue" markerWidth="8" markerHeight="6" refX="8" refY="3" orient="auto"><path d="M0,0 L8,3 L0,6" fill="#3b82f6"/></marker>
                <filter id="glow"><feGaussianBlur stdDeviation="2" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
            </defs>

            {{-- Edges --}}
            <template x-for="edge in edges" :key="edge.id">
                <g>
                    <line :x1="edge.x1" :y1="edge.y1" :x2="edge.x2" :y2="edge.y2"
                          :stroke="edge.color" :stroke-width="edge.highlighted ? 3 : 1.5"
                          :stroke-dasharray="edge.dashed ? '6,4' : 'none'"
                          :opacity="hoveredNode && !edge.highlighted ? 0.15 : 1"
                          class="transition-all duration-200"
                          :marker-end="'url(#arrow-' + edge.arrowColor + ')'"/>
                    <text x-show="edge.label && (edge.highlighted || !hoveredNode)"
                          :x="(edge.x1+edge.x2)/2" :y="(edge.y1+edge.y2)/2 - 6"
                          text-anchor="middle" class="text-[10px] fill-gray-400 select-none" x-text="edge.label"/>
                </g>
            </template>

            {{-- Nodes --}}
            <template x-for="node in nodes" :key="node.id">
                <g :transform="'translate('+node.x+','+node.y+')'" class="cursor-pointer"
                   @mouseenter="hoverNode(node.id)" @mouseleave="hoverNode(null)">
                    {{-- Node circle --}}
                    <circle r="20" :fill="node.fill" :stroke="node.stroke" stroke-width="2.5"
                            :opacity="hoveredNode && hoveredNode !== node.id && !node.highlighted ? 0.3 : 1"
                            :filter="node.highlighted ? 'url(#glow)' : ''"
                            class="transition-all duration-200"/>
                    {{-- Icon --}}
                    <text text-anchor="middle" dominant-baseline="central" class="text-base select-none pointer-events-none"
                          x-text="node.icon" :opacity="hoveredNode && hoveredNode !== node.id && !node.highlighted ? 0.3 : 1"/>
                    {{-- Label --}}
                    <text :y="30" text-anchor="middle" class="text-[11px] font-semibold select-none pointer-events-none"
                          :fill="hoveredNode && hoveredNode !== node.id && !node.highlighted ? '#d1d5db' : '#374151'"
                          x-text="node.label.length > 14 ? node.label.slice(0,12)+'…' : node.label"/>
                    {{-- Tooltip --}}
                    <g x-show="hoveredNode === node.id && node.tooltip" x-transition>
                        <rect :x="-60" :y="-52" width="120" height="24" rx="6" fill="rgba(17,24,39,0.9)"/>
                        <text :y="-36" text-anchor="middle" class="text-[10px] fill-white select-none" x-text="node.tooltip"/>
                    </g>
                </g>
            </template>
        </svg>
    </div>
</div>

{{-- ═══════ SECTION 3: PROCESSING TIMELINE ═══════ --}}
<div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 animate-xray-in" style="animation-delay:.2s">
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

            const allSkills = [...new Set([...matched, ...missing, ...missingPref])];
            const relTargets = related.map(r => r.target_skill);
            const relSources = related.map(r => r.candidate_skill);

            // Compute layout
            const totalRows = Math.max(1, allSkills.length + related.length);
            this.svgH = Math.max(340, totalRows * 48 + 100);
            const cx = 80, jx = this.svgW - 80;
            const midX = this.svgW / 2;

            // Candidate + Job anchor nodes
            const nodes = [
                { id:'candidate', x:cx, y:this.svgH/2, icon:'🧑', label:'{{ Str::limit($candidate->name ?? "Ứng viên", 14) }}', fill:'#eef2ff', stroke:'#6366f1', tooltip:null },
                { id:'job', x:jx, y:this.svgH/2, icon:'📋', label:'{{ Str::limit($job->title ?? "Vị trí", 14) }}', fill:'#fef3c7', stroke:'#f59e0b', tooltip:null },
            ];
            const edges = [];

            // Position skills in the middle
            let row = 0;
            const totalSkillNodes = matched.length + missing.length + missingPref.length;
            const startY = Math.max(40, (this.svgH - totalSkillNodes * 46) / 2);

            // Matched skills (green)
            matched.forEach(s => {
                const ny = startY + row * 46;
                const isRelSource = relSources.includes(s);
                nodes.push({ id:'m_'+s, x:midX, y:ny, icon:'✓', label:s, fill:'#d1fae5', stroke:'#10b981', tooltip:'Phù hợp chính xác' });
                edges.push({ id:'ce_'+s, x1:cx+20, y1:this.svgH/2, x2:midX-20, y2:ny, color:'#10b981', dashed:false, arrowColor:'green', label:null, sourceNode:'candidate', targetNode:'m_'+s });
                edges.push({ id:'je_'+s, x1:midX+20, y1:ny, x2:jx-20, y2:this.svgH/2, color:'#10b981', dashed:false, arrowColor:'green', label:null, sourceNode:'m_'+s, targetNode:'job' });
                row++;
            });

            // Related matches (blue dashed) — show candidate_skill → target_skill
            related.forEach((r, i) => {
                const ny = startY + row * 46;
                const simPct = Math.round(r.similarity * 100) + '%';
                const relLabel = (r.relation_type || '').replace(/_/g,' ');
                // Candidate skill node (if not already a matched node)
                const csId = 'rs_' + r.candidate_skill + '_' + i;
                const tsId = 'rt_' + r.target_skill + '_' + i;
                nodes.push({ id:csId, x:midX - 100, y:ny, icon:'◈', label:r.candidate_skill, fill:'#dbeafe', stroke:'#3b82f6', tooltip:'Kỹ năng ứng viên' });
                nodes.push({ id:tsId, x:midX + 100, y:ny, icon:'◇', label:r.target_skill, fill:'#ede9fe', stroke:'#8b5cf6', tooltip:'Yêu cầu (' + relLabel + ', ' + simPct + ')' });
                edges.push({ id:'re_c_'+i, x1:cx+20, y1:this.svgH/2, x2:midX-120, y2:ny, color:'#3b82f6', dashed:true, arrowColor:'blue', label:null, sourceNode:'candidate', targetNode:csId });
                edges.push({ id:'re_m_'+i, x1:midX-80, y1:ny, x2:midX+80, y2:ny, color:'#3b82f6', dashed:true, arrowColor:'blue', label:relLabel+' '+simPct, sourceNode:csId, targetNode:tsId });
                edges.push({ id:'re_j_'+i, x1:midX+120, y1:ny, x2:jx-20, y2:this.svgH/2, color:'#8b5cf6', dashed:true, arrowColor:'blue', label:null, sourceNode:tsId, targetNode:'job' });
                row++;
            });

            // Missing required (red)
            missing.forEach(s => {
                // Skip if already shown as related target
                if (relTargets.includes(s)) return;
                const ny = startY + row * 46;
                nodes.push({ id:'x_'+s, x:midX + 60, y:ny, icon:'✗', label:s, fill:'#fee2e2', stroke:'#ef4444', tooltip:'Thiếu bắt buộc' });
                edges.push({ id:'xe_'+s, x1:midX+80, y1:ny, x2:jx-20, y2:this.svgH/2, color:'#ef4444', dashed:false, arrowColor:'red', label:null, sourceNode:'x_'+s, targetNode:'job' });
                row++;
            });

            // Missing preferred (amber, smaller)
            missingPref.forEach(s => {
                const ny = startY + row * 46;
                nodes.push({ id:'p_'+s, x:midX + 60, y:ny, icon:'~', label:s, fill:'#fef9c3', stroke:'#f59e0b', tooltip:'Thiếu ưu tiên' });
                edges.push({ id:'pe_'+s, x1:midX+80, y1:ny, x2:jx-20, y2:this.svgH/2, color:'#fbbf24', dashed:true, arrowColor:'green', label:null, sourceNode:'p_'+s, targetNode:'job' });
                row++;
            });

            // Recalculate svgH based on actual rows
            this.svgH = Math.max(340, row * 46 + 100);

            this.nodes = nodes.map(n => ({ ...n, highlighted: false }));
            this.edges = edges.map(e => ({ ...e, highlighted: false }));
        },

        hoverNode(nodeId) {
            this.hoveredNode = nodeId;
            this.nodes.forEach(n => n.highlighted = false);
            this.edges.forEach(e => {
                e.highlighted = false;
                if (nodeId && (e.sourceNode === nodeId || e.targetNode === nodeId)) {
                    e.highlighted = true;
                    // Also highlight connected nodes
                    this.nodes.forEach(n2 => {
                        if (n2.id === e.sourceNode || n2.id === e.targetNode) n2.highlighted = true;
                    });
                }
            });
            if (nodeId) {
                const n = this.nodes.find(n => n.id === nodeId);
                if (n) n.highlighted = true;
            }
        }
    };
}
</script>
</x-layouts.app>
