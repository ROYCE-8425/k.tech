<x-layouts.app title="Đơn đã nộp - Smart AI Recruitment System">
    <div class="space-y-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">📋 Đơn ứng tuyển của tôi</h1>
                <p class="text-gray-600 mt-2">Theo dõi trạng thái các đơn ứng tuyển</p>
            </div>
            <a href="{{ route('home') }}" class="inline-flex items-center px-6 py-3 btn-primary shine text-white font-semibold rounded-xl transition-all shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Tìm việc mới
            </a>
        </div>

        @if($applications->isEmpty())
            <!-- Empty State -->
            <div class="glass-panel rounded-3xl p-12 text-center">
                <div class="w-24 h-24 bg-blue-50/50 rounded-full flex items-center justify-center mx-auto mb-6 border border-white/40">
                    <span class="text-5xl">📄</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Chưa có đơn ứng tuyển nào</h2>
                <p class="text-gray-600 mb-8 max-w-md mx-auto">Bắt đầu hành trình tìm việc của bạn bằng cách khám phá các cơ hội việc làm hấp dẫn.</p>
                <a href="{{ route('home') }}" class="inline-flex items-center px-8 py-4 btn-primary shine text-white font-bold rounded-2xl transition-all shadow-xl">
                    Khám phá việc làm
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </a>
            </div>
        @else
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="glass-card rounded-2xl p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
                            <span class="text-2xl">📝</span>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ $applications->count() }}</p>
                            <p class="text-sm text-gray-500">Tổng đơn</p>
                        </div>
                    </div>
                </div>
                <div class="glass-card rounded-2xl p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center">
                            <span class="text-2xl">⏳</span>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ $applications->whereIn('status', ['submitted', 'reviewing'])->count() }}</p>
                            <p class="text-sm text-gray-500">Đang chờ</p>
                        </div>
                    </div>
                </div>
                <div class="glass-card rounded-2xl p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center">
                            <span class="text-2xl">✅</span>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ $applications->whereIn('status', ['shortlisted', 'interviewed', 'offered'])->count() }}</p>
                            <p class="text-sm text-gray-500">Tiến triển</p>
                        </div>
                    </div>
                </div>
                <div class="glass-card rounded-2xl p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
                            <span class="text-2xl">❌</span>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ $applications->where('status', 'rejected')->count() }}</p>
                            <p class="text-sm text-gray-500">Từ chối</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Applications List -->
            <div class="space-y-4">
                @foreach($applications as $application)
                    <div class="glass-card rounded-2xl overflow-hidden group">
                        <div class="p-6">
                            <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                                <!-- Company Logo -->
                                <div class="flex-shrink-0">
                                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                                        @if($application->job->company && $application->job->company->logo_path)
                                            <img src="{{ asset('storage/' . $application->job->company->logo_path) }}" alt="" class="w-full h-full object-cover rounded-2xl">
                                        @else
                                            {{ substr($application->job->company->name ?? 'C', 0, 1) }}
                                        @endif
                                    </div>
                                </div>

                                <!-- Job Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        <a href="{{ route('jobs.show', $application->job->id) }}" class="text-xl font-bold text-gray-900 hover:text-indigo-600 transition-colors">
                                            {{ $application->job->title }}
                                        </a>
                                        @php
                                            $statusConfig = [
                                                'submitted' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'Đã nộp', 'icon' => '📤'],
                                                'reviewing' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => 'Đang xem xét', 'icon' => '👀'],
                                                'shortlisted' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'label' => 'Được chọn', 'icon' => '⭐'],
                                                'interviewed' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'label' => 'Đã phỏng vấn', 'icon' => '🎤'],
                                                'offered' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'label' => 'Có offer', 'icon' => '🎉'],
                                                'rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => 'Từ chối', 'icon' => '❌'],
                                                'withdrawn' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => 'Đã rút', 'icon' => '↩️'],
                                            ];
                                            $status = $statusConfig[$application->status] ?? $statusConfig['submitted'];
                                        @endphp
                                        <span class="{{ $status['bg'] }} {{ $status['text'] }} px-3 py-1 rounded-full text-sm font-semibold flex items-center gap-1">
                                            {{ $status['icon'] }} {{ $status['label'] }}
                                        </span>
                                    </div>
                                    
                                    <p class="text-gray-600 font-medium">
                                        {{ $application->job->company->name ?? 'Công ty' }}
                                    </p>
                                    
                                    <div class="flex flex-wrap items-center gap-4 mt-3 text-sm text-gray-500">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            {{ $application->job->location ?? 'Việt Nam' }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Nộp {{ $application->applied_at?->diffForHumans() ?? $application->created_at->diffForHumans() }}
                                        </span>
                                        @php $aiResult = $application->ai_match_result; @endphp
                                        @if($aiResult && isset($aiResult['fit_score']))
                                            <span class="flex items-center gap-1 font-semibold {{ $aiResult['fit_score'] >= 7 ? 'text-emerald-600' : ($aiResult['fit_score'] >= 5 ? 'text-amber-600' : 'text-red-600') }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                </svg>
                                                🤖 Phù hợp: {{ number_format($aiResult['fit_score'], 1) }}/10
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('jobs.show', $application->job->id) }}" class="px-5 py-2.5 bg-indigo-50 text-indigo-600 font-semibold rounded-xl hover:bg-indigo-100 transition-colors">
                                        Xem tin
                                    </a>
                                </div>
                            </div>

                            <!-- Timeline Progress (Optional visual) -->
                            @if(!in_array($application->status, ['rejected', 'withdrawn']))
                                <div class="mt-6 pt-6 border-t border-gray-100">
                                    <div class="flex items-center justify-between">
                                        @php
                                            $steps = ['submitted', 'reviewing', 'shortlisted', 'interviewed', 'offered'];
                                            $currentIndex = array_search($application->status, $steps);
                                            if ($currentIndex === false) $currentIndex = 0;
                                        @endphp
                                        @foreach($steps as $index => $step)
                                            <div class="flex flex-col items-center flex-1 {{ $index < count($steps) - 1 ? 'relative' : '' }}">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold {{ $index <= $currentIndex ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500' }}">
                                                    @if($index < $currentIndex)
                                                        ✓
                                                    @else
                                                        {{ $index + 1 }}
                                                    @endif
                                                </div>
                                                <span class="text-xs mt-1 text-gray-500 hidden sm:block">
                                                    {{ ['Đã nộp', 'Xem xét', 'Chọn lọc', 'Phỏng vấn', 'Offer'][$index] }}
                                                </span>
                                                @if($index < count($steps) - 1)
                                                    <div class="absolute top-4 left-1/2 w-full h-0.5 {{ $index < $currentIndex ? 'bg-indigo-600' : 'bg-gray-200' }}"></div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.app>
