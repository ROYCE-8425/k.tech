<x-layouts.app>
    <div class="px-8 py-6 max-w-7xl mx-auto" x-data="evaluationDashboard()">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 font-jakarta">AI Performance Evaluation</h1>
                <p class="text-sm text-gray-500 mt-1">Real-time benchmark of the Hybrid AI Engine against standard baselines.</p>
            </div>
            <div>
                <button @click="runEvaluation" 
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium transition-all shadow-sm flex items-center gap-2"
                        :class="{'opacity-50 cursor-not-allowed': isRunning}"
                        :disabled="isRunning">
                    <svg x-show="!isRunning" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <svg x-show="isRunning" class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="isRunning ? 'Đang chạy test (1-2 phút)...' : 'Chạy Evaluation (Real-time)'"></span>
                </button>
            </div>
        </div>

        {{-- Status Alert --}}
        <div x-show="error" class="mb-6 p-4 bg-red-50 text-red-700 rounded-xl border border-red-100 flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <span x-text="error"></span>
        </div>

        {{-- Loading Overlay or Main Content --}}
        <div class="space-y-6" x-show="hasData" x-transition>
            
            {{-- Primary Metrics Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Keyword --}}
                <div class="bg-white/70 backdrop-blur-md border border-gray-200 rounded-2xl p-6 shadow-sm">
                    <div class="text-sm font-medium text-gray-500 mb-2">Baseline 1: Keyword Matching</div>
                    <div class="flex items-baseline gap-2">
                        <div class="text-3xl font-bold text-gray-900 font-mono" x-text="formatPercent(metrics.exact_only?.rank_label_agreement)">--</div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Must-Match Precision:</span> <span class="font-medium text-gray-900" x-text="formatPercent(metrics.exact_only?.must_match_precision)"></span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Band Accuracy:</span> <span class="font-medium text-gray-900" x-text="formatPercent(metrics.exact_only?.score_band_accuracy)"></span></div>
                    </div>
                </div>

                {{-- LLM Only --}}
                <div class="bg-white/70 backdrop-blur-md border border-gray-200 rounded-2xl p-6 shadow-sm">
                    <div class="text-sm font-medium text-gray-500 mb-2">Baseline 2: LLM Only (1-Hop)</div>
                    <div class="flex items-baseline gap-2">
                        <div class="text-3xl font-bold text-gray-900 font-mono" x-text="formatPercent(metrics.one_hop?.rank_label_agreement)">--</div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Must-Match Precision:</span> <span class="font-medium text-gray-900" x-text="formatPercent(metrics.one_hop?.must_match_precision)"></span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Band Accuracy:</span> <span class="font-medium text-gray-900" x-text="formatPercent(metrics.one_hop?.score_band_accuracy)"></span></div>
                    </div>
                </div>

                {{-- Hybrid --}}
                <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl p-6 shadow-lg text-white">
                    <div class="text-sm font-medium text-indigo-100 mb-2 flex justify-between items-center">
                        <span>Hybrid AI Engine (2-Hop + Graph)</span>
                        <span class="px-2 py-0.5 bg-white/20 rounded-full text-xs">Winner</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <div class="text-4xl font-bold font-mono" x-text="formatPercent(metrics.two_hop?.rank_label_agreement)">--</div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="flex justify-between text-sm"><span class="text-indigo-100">Must-Match Precision:</span> <span class="font-medium" x-text="formatPercent(metrics.two_hop?.must_match_precision)"></span></div>
                        <div class="flex justify-between text-sm"><span class="text-indigo-100">Band Accuracy:</span> <span class="font-medium" x-text="formatPercent(metrics.two_hop?.score_band_accuracy)"></span></div>
                    </div>
                </div>
            </div>

            {{-- Charts Area --}}
            <div class="bg-white/80 backdrop-blur-md border border-gray-200 rounded-2xl p-6 shadow-sm">
                <h3 class="text-lg font-bold text-gray-900 mb-6">Performance Comparison</h3>
                <div class="h-80">
                    <canvas id="evalChart"></canvas>
                </div>
            </div>

        </div>
        
        {{-- Empty State --}}
        <div x-show="!hasData && !isRunning && !error" class="text-center py-20 bg-white/50 backdrop-blur-sm rounded-2xl border border-gray-200 mt-6" x-cloak>
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 text-indigo-600 mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900">Chưa có dữ liệu Evaluation</h3>
            <p class="mt-2 text-gray-500">Bấm nút "Chạy Evaluation" ở góc trên để bắt đầu đánh giá trên bộ test dataset thực tế.</p>
        </div>

    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('evaluationDashboard', () => ({
                runId: 'latest',
                isRunning: false,
                hasData: false,
                metrics: {},
                error: null,
                chartInstance: null,
                pollingInterval: null,

                init() {
                    // Try to load latest run on init
                    this.pollStatus();
                },

                runEvaluation() {
                    if (this.isRunning) return;
                    this.isRunning = true;
                    this.error = null;
                    
                    fetch('{{ route("admin.ai-evaluation.run") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            this.runId = data.run_id;
                            this.startPolling();
                        } else {
                            this.error = data.error || 'Có lỗi xảy ra khi khởi chạy';
                            this.isRunning = false;
                        }
                    })
                    .catch(err => {
                        this.error = 'Không thể kết nối đến server: ' + err.message;
                        this.isRunning = false;
                    });
                },

                startPolling() {
                    if (this.pollingInterval) clearInterval(this.pollingInterval);
                    this.pollingInterval = setInterval(() => this.pollStatus(), 3000);
                },

                pollStatus() {
                    fetch(`/admin/ai/evaluation/status/${this.runId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'running') {
                            this.isRunning = true;
                        } else if (data.status === 'completed') {
                            this.isRunning = false;
                            this.hasData = true;
                            this.metrics = data.metrics || {};
                            if (this.pollingInterval) clearInterval(this.pollingInterval);
                            this.renderChart();
                        } else if (data.status === 'failed') {
                            this.isRunning = false;
                            this.error = data.error || 'Quá trình đánh giá bị lỗi.';
                            if (this.pollingInterval) clearInterval(this.pollingInterval);
                        }
                    })
                    .catch(err => {
                        if(this.runId !== 'latest') {
                            console.error('Polling error', err);
                        }
                    });
                },

                formatPercent(val) {
                    if (val === undefined || val === null) return '--';
                    return (val * 100).toFixed(0) + '%';
                },

                renderChart() {
                    if (!this.metrics.two_hop) return;
                    
                    const ctx = document.getElementById('evalChart');
                    if (!ctx) return;

                    if (this.chartInstance) {
                        this.chartInstance.destroy();
                    }

                    const labels = ['Score Band Accuracy', 'Rank Agreement', 'Must-Match Precision', 'Must-Miss Recall'];
                    
                    const exactData = [
                        this.metrics.exact_only?.score_band_accuracy || 0,
                        this.metrics.exact_only?.rank_label_agreement || 0,
                        this.metrics.exact_only?.must_match_precision || 0,
                        this.metrics.exact_only?.must_miss_recall || 0
                    ].map(v => v * 100);

                    const oneHopData = [
                        this.metrics.one_hop?.score_band_accuracy || 0,
                        this.metrics.one_hop?.rank_label_agreement || 0,
                        this.metrics.one_hop?.must_match_precision || 0,
                        this.metrics.one_hop?.must_miss_recall || 0
                    ].map(v => v * 100);

                    const twoHopData = [
                        this.metrics.two_hop?.score_band_accuracy || 0,
                        this.metrics.two_hop?.rank_label_agreement || 0,
                        this.metrics.two_hop?.must_match_precision || 0,
                        this.metrics.two_hop?.must_miss_recall || 0
                    ].map(v => v * 100);

                    this.chartInstance = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Keyword Baseline',
                                    data: exactData,
                                    backgroundColor: 'rgba(156, 163, 175, 0.8)',
                                    borderRadius: 4,
                                },
                                {
                                    label: 'LLM Only (1-Hop)',
                                    data: oneHopData,
                                    backgroundColor: 'rgba(96, 165, 250, 0.8)',
                                    borderRadius: 4,
                                },
                                {
                                    label: 'Hybrid AI (2-Hop)',
                                    data: twoHopData,
                                    backgroundColor: 'rgba(79, 70, 229, 0.9)',
                                    borderRadius: 4,
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100,
                                    ticks: {
                                        callback: function(value) { return value + '%' }
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                }
            }));
        });
    </script>
    @endpush
</x-layouts.app>
