<x-layouts.app>
    <!-- Page Header -->
    <div class="mb-10">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-4xl font-bold text-gray-900 mb-2">Dashboard</h1>
                <p class="text-gray-500 text-lg">Chào mừng trở lại! Đây là tổng quan về hoạt động tuyển dụng của bạn.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.companies.index') }}" class="inline-flex items-center px-5 py-3 rounded-2xl bg-white border-2 border-gray-200 text-gray-700 font-bold hover:border-indigo-300 hover:text-indigo-600 transition-all duration-300">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M7 7V5a2 2 0 012-2h6a2 2 0 012 2v2M5 7v14a2 2 0 002 2h10a2 2 0 002-2V7"></path>
                    </svg>
                    Công ty
                </a>
                <!-- Export Report PDF -->
                <div x-data="{ showExport: false }" class="relative">
                    <button @click="showExport = !showExport" class="inline-flex items-center px-5 py-3 rounded-2xl bg-red-50 border-2 border-red-200 text-red-700 font-bold hover:bg-red-600 hover:text-white hover:border-red-600 transition-all duration-300">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Xuất báo cáo PDF
                    </button>
                    <div x-show="showExport" @click.away="showExport = false" x-transition 
                         class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 p-5 z-50">
                        <h4 class="font-bold text-gray-900 mb-3">Chọn khoảng thời gian</h4>
                        <form action="{{ route('admin.reports.export-pdf') }}" method="GET">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Từ ngày</label>
                                    <input type="date" name="from_date" value="{{ now()->subMonth()->toDateString() }}" 
                                           class="w-full px-3 py-2 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Đến ngày</label>
                                    <input type="date" name="to_date" value="{{ now()->toDateString() }}" 
                                           class="w-full px-3 py-2 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none">
                                </div>
                                <button type="submit" class="w-full px-4 py-2.5 bg-red-600 text-white font-bold rounded-xl hover:bg-red-700 transition-colors">
                                    Tải xuống PDF
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <a href="{{ route('admin.interviews') }}" class="inline-flex items-center px-5 py-3 rounded-2xl bg-white border-2 border-gray-200 text-gray-700 font-bold hover:border-indigo-300 hover:text-indigo-600 transition-all duration-300">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Lịch phỏng vấn
                </a>
                <a href="{{ route('admin.jobs.create') }}" class="inline-flex items-center px-6 py-3 rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold shadow-xl hover:shadow-2xl hover:shadow-indigo-500/30 hover:scale-105 transition-all duration-300 shine" style="background: linear-gradient(to right, #4f46e5, #9333ea);">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Đăng việc mới
                </a>
            </div>
        </div>
    </div>

    {{-- Demo quick-action guide --}}
    @if(config('app.demo_mode'))
        <div class="flex items-start gap-3 p-4 rounded-2xl bg-purple-50 border border-purple-200 mb-8 animate-fade-in">
            <span class="text-lg mt-0.5">🚀</span>
            <div class="text-sm">
                <p class="font-semibold text-purple-900 mb-1">Bắt đầu nhanh:</p>
                <p class="text-purple-700">
                    Cuộn xuống danh sách job → chọn job có badge <span class="font-semibold">🤖 Có AI shortlist sẵn</span> → nhấn <span class="font-semibold">AI Shortlist</span> để xem xếp hạng ứng viên.
                    Hoặc nhấn <a href="{{ route('admin.jobs.create') }}" class="underline font-semibold">+ Đăng tuyển</a> để tạo job mới và thử JD quality checker.
                </p>
            </div>
        </div>
    @endif

    <!-- Stats Cards Row 1 -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Jobs -->
        <div class="relative group">
            <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-3xl blur-xl opacity-0 group-hover:opacity-20 transition-opacity duration-500"></div>
            <div class="relative bg-white rounded-3xl p-6 shadow-xl shadow-gray-200/50">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center" style="background: linear-gradient(to bottom right, #6366f1, #a855f7);">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-emerald-600 bg-emerald-100 px-3 py-1 rounded-full">Tổng</span>
                </div>
                <h3 class="text-4xl font-bold text-gray-900 mb-1">{{ $jobCount }}</h3>
                <p class="text-gray-500 font-medium">Việc làm</p>
            </div>
        </div>

        <!-- Total Applications -->
        <div class="relative group">
            <div class="absolute inset-0 bg-gradient-to-r from-emerald-600 to-teal-600 rounded-3xl blur-xl opacity-0 group-hover:opacity-20 transition-opacity duration-500"></div>
            <div class="relative bg-white rounded-3xl p-6 shadow-xl shadow-gray-200/50">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center" style="background: linear-gradient(to bottom right, #10b981, #14b8a6);">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-indigo-600 bg-indigo-100 px-3 py-1 rounded-full">+{{ $newApplicationsCount }} hôm nay</span>
                </div>
                <h3 class="text-4xl font-bold text-gray-900 mb-1">{{ $totalApplications }}</h3>
                <p class="text-gray-500 font-medium">Tổng đơn ứng tuyển</p>
            </div>
        </div>

        <!-- Active Jobs -->
        <div class="relative group">
            <div class="absolute inset-0 bg-gradient-to-r from-orange-600 to-pink-600 rounded-3xl blur-xl opacity-0 group-hover:opacity-20 transition-opacity duration-500"></div>
            <div class="relative bg-white rounded-3xl p-6 shadow-xl shadow-gray-200/50">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-500 to-pink-500 flex items-center justify-center" style="background: linear-gradient(to bottom right, #f97316, #ec4899);">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-orange-600 bg-orange-100 px-3 py-1 rounded-full">Active</span>
                </div>
                <h3 class="text-4xl font-bold text-gray-900 mb-1">{{ $jobs->where('status', 'published')->count() }}</h3>
                <p class="text-gray-500 font-medium">Đang tuyển</p>
            </div>
        </div>

        <!-- Accepted Rate -->
        <div class="relative group">
            <div class="absolute inset-0 bg-gradient-to-r from-cyan-600 to-blue-600 rounded-3xl blur-xl opacity-0 group-hover:opacity-20 transition-opacity duration-500"></div>
            <div class="relative bg-white rounded-3xl p-6 shadow-xl shadow-gray-200/50">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-cyan-500 to-blue-500 flex items-center justify-center" style="background: linear-gradient(to bottom right, #06b6d4, #3b82f6);">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-cyan-600 bg-cyan-100 px-3 py-1 rounded-full">Tỷ lệ</span>
                </div>
                <h3 class="text-4xl font-bold text-gray-900 mb-1">
                    {{ $totalApplications > 0 ? round(($acceptedApplications / $totalApplications) * 100) : 0 }}%
                </h3>
                <p class="text-gray-500 font-medium">Đã nhận việc</p>
            </div>
        </div>
    </div>

    <!-- Stats Cards Row 2 - Application Status -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-yellow-50 border-2 border-yellow-100 rounded-2xl p-5 text-center">
            <div class="text-3xl font-bold text-yellow-600 mb-1">{{ $pendingApplications }}</div>
            <div class="text-sm text-yellow-700 font-medium">Chờ xử lý</div>
        </div>
        <div class="bg-blue-50 border-2 border-blue-100 rounded-2xl p-5 text-center">
            <div class="text-3xl font-bold text-blue-600 mb-1">{{ $reviewedApplications }}</div>
            <div class="text-sm text-blue-700 font-medium">Đã xem xét</div>
        </div>
        <div class="bg-green-50 border-2 border-green-100 rounded-2xl p-5 text-center">
            <div class="text-3xl font-bold text-green-600 mb-1">{{ $acceptedApplications }}</div>
            <div class="text-sm text-green-700 font-medium">Đã nhận</div>
        </div>
        <div class="bg-red-50 border-2 border-red-100 rounded-2xl p-5 text-center">
            <div class="text-3xl font-bold text-red-600 mb-1">{{ $rejectedApplications }}</div>
            <div class="text-sm text-red-700 font-medium">Từ chối</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Applications by Day Chart -->
        <div class="glass-panel rounded-3xl p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <svg class="w-6 h-6 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Đơn ứng tuyển 7 ngày qua
            </h3>
            <div class="flex items-end justify-between h-48 gap-2">
                @foreach($applicationsByDay as $day)
                    @php
                        $maxCount = max(array_column($applicationsByDay, 'count'));
                        $height = $maxCount > 0 ? ($day['count'] / $maxCount) * 100 : 0;
                    @endphp
                    <div class="flex-1 flex flex-col items-center">
                        <div class="text-sm font-bold text-gray-700 mb-2">{{ $day['count'] }}</div>
                        <div class="w-full bg-gradient-to-t from-indigo-500 to-purple-500 rounded-t-lg transition-all duration-500 hover:from-indigo-600 hover:to-purple-600" 
                             style="background: linear-gradient(to top, #6366f1, #a855f7); height: {{ max($height, 5) }}%; min-height: 8px;"></div>
                        <div class="text-xs text-gray-500 mt-2 font-medium">{{ $day['date'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Applications by Month Chart -->
        <div class="glass-panel rounded-3xl p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <svg class="w-6 h-6 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                </svg>
                Đơn ứng tuyển 6 tháng qua
            </h3>
            <div class="flex items-end justify-between h-48 gap-3">
                @foreach($applicationsByMonth as $month)
                    @php
                        $maxCount = max(array_column($applicationsByMonth, 'count'));
                        $height = $maxCount > 0 ? ($month['count'] / $maxCount) * 100 : 0;
                    @endphp
                    <div class="flex-1 flex flex-col items-center">
                        <div class="text-sm font-bold text-gray-700 mb-2">{{ $month['count'] }}</div>
                        <div class="w-full bg-gradient-to-t from-emerald-500 to-teal-500 rounded-t-lg transition-all duration-500 hover:from-emerald-600 hover:to-teal-600" 
                             style="background: linear-gradient(to top, #10b981, #14b8a6); height: {{ max($height, 5) }}%; min-height: 8px;"></div>
                        <div class="text-xs text-gray-500 mt-2 font-medium">{{ $month['month'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Two Column Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Upcoming Interviews -->
    <div class="glass-panel rounded-3xl overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Phỏng vấn sắp tới
                </h3>
                <a href="{{ route('admin.interviews') }}" class="text-sm text-indigo-600 hover:underline font-medium">Xem tất cả →</a>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($upcomingInterviews as $interview)
                    <div class="p-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-100 to-pink-100 flex items-center justify-center" style="background: linear-gradient(to bottom right, #ffedd5, #fce7f3);">
                                    <span class="text-sm font-bold text-orange-600">
                                        {{ strtoupper(substr($interview->application->candidate->name ?? 'U', 0, 1)) }}
                                    </span>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900">{{ $interview->application->candidate->name ?? 'Ứng viên' }}</div>
                                    <div class="text-sm text-gray-500">{{ $interview->application->job->title ?? 'Vị trí' }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($interview->scheduled_at)->format('H:i') }}</div>
                                <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($interview->scheduled_at)->format('d/m/Y') }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Chưa có lịch phỏng vấn nào
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Applications -->
    <div class="glass-panel rounded-3xl overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                    Ứng viên mới nhất
                </h3>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($recentApplications as $application)
                    <div class="p-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center" style="background: linear-gradient(to bottom right, #e0e7ff, #f3e8ff);">
                                    <span class="text-sm font-bold text-indigo-600">
                                        {{ strtoupper(substr($application->candidate->name ?? 'U', 0, 1)) }}
                                    </span>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900">{{ $application->candidate->name ?? 'Ứng viên' }}</div>
                                    <div class="text-sm text-gray-500">{{ $application->job->title ?? 'Vị trí' }}</div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                @php 
                                    $aiScore = is_array($application->ai_match_result) ? ($application->ai_match_result['fit_score'] ?? null) : null; 
                                    if ($aiScore !== null && $aiScore > 10) {
                                        $aiScore = $aiScore / 10;
                                    }
                                @endphp
                                @if($aiScore !== null)
                                    <span class="px-2 py-1 rounded-lg bg-violet-100 text-violet-700 text-xs font-bold" title="AI Fit Score">
                                        🤖 {{ number_format($aiScore, 1) }}/10
                                    </span>
                                @endif
                                <span class="px-2 py-1 rounded-lg text-xs font-medium
                                    {{ in_array($application->status, ['submitted', 'reviewing'], true) ? 'bg-yellow-100 text-yellow-700' : '' }}
                                    {{ in_array($application->status, ['shortlisted', 'interviewed', 'offered'], true) ? 'bg-blue-100 text-blue-700' : '' }}
                                    {{ $application->status === 'hired' ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $application->status === 'rejected' ? 'bg-red-100 text-red-700' : '' }}
                                ">
                                    {{ in_array($application->status, ['submitted', 'reviewing'], true) ? 'Chờ' : '' }}
                                    {{ in_array($application->status, ['shortlisted', 'interviewed', 'offered'], true) ? 'Tiến triển' : '' }}
                                    {{ $application->status === 'hired' ? 'Nhận' : '' }}
                                    {{ $application->status === 'rejected' ? 'Từ chối' : '' }}
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        Chưa có ứng viên nào
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Top Jobs Section -->
    <div class="glass-panel rounded-3xl overflow-hidden mb-8">
        <div class="p-6 border-b border-gray-100">
            <h3 class="text-xl font-bold text-gray-900 flex items-center">
                <svg class="w-6 h-6 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                </svg>
                Top việc làm được ứng tuyển nhiều nhất
            </h3>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($topJobs as $index => $job)
                <div class="p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-white
                                {{ $index === 0 ? 'bg-yellow-500 bg-gradient-to-br from-yellow-400 to-orange-500' : '' }}
                                {{ $index === 1 ? 'bg-gray-400 bg-gradient-to-br from-gray-300 to-gray-400' : '' }}
                                {{ $index === 2 ? 'bg-orange-400 bg-gradient-to-br from-orange-300 to-orange-400' : '' }}
                                {{ $index > 2 ? 'bg-indigo-500 bg-gradient-to-br from-indigo-400 to-purple-500' : '' }}
                            ">
                                {{ $index + 1 }}
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">{{ $job->title }}</div>
                                <div class="text-sm text-gray-500">{{ $job->company->name ?? 'Công ty' }} • {{ $job->location ?? 'N/A' }}</div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-indigo-600">{{ $job->applications_count }}</div>
                                <div class="text-xs text-gray-500">ứng viên</div>
                            </div>
                            <a href="{{ route('admin.jobs.ai-shortlist', $job->id) }}" 
                               class="px-4 py-2 rounded-xl bg-violet-50 text-violet-600 font-medium hover:bg-violet-600 hover:text-white transition-all duration-300">
                                🤖 Shortlist
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-500">
                    Chưa có dữ liệu
                </div>
            @endforelse
        </div>
    </div>

    <!-- Jobs List -->
    <div class="glass-panel rounded-3xl overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Danh sách việc làm</h2>
                    <p class="text-gray-500">Quản lý các vị trí đang tuyển</p>
                </div>
            </div>
        </div>

        @if($jobs->count() > 0)
            <div class="divide-y divide-gray-100">
                @foreach($jobs as $job)
                    <div class="p-6 hover:bg-gray-50/50 transition-colors group">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <!-- Company Avatar -->
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center group-hover:scale-110 transition-transform duration-300" style="background: linear-gradient(to bottom right, #e0e7ff, #f3e8ff);">
                                    <span class="text-xl font-bold gradient-text">
                                        {{ strtoupper(substr($job->company->name ?? 'C', 0, 1)) }}
                                    </span>
                                </div>

                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">
                                        {{ $job->title }}
                                    </h3>
                                    <div class="flex items-center space-x-3 mt-1">
                                        <span class="text-gray-500 text-sm">{{ $job->company->name ?? 'Công ty' }}</span>
                                        @if($job->location)
                                            <span class="text-gray-300">•</span>
                                            <span class="text-gray-500 text-sm">{{ $job->location }}</span>
                                        @endif
                                    </div>
                                    @if(config('app.demo_mode') && !empty($demoJobSeedInfo[$job->id]))
                                        @php $seedInfo = $demoJobSeedInfo[$job->id]; @endphp
                                        <div class="flex flex-wrap gap-1.5 mt-2">
                                            @if(($seedInfo['app_count'] ?? 0) > 0 && ($seedInfo['ai_count'] ?? 0) > 0)
                                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-xs font-semibold rounded-lg border border-emerald-200">🤖 Có AI shortlist sẵn</span>
                                                @if(($seedInfo['app_count'] ?? 0) > ($seedInfo['ai_count'] ?? 0))
                                                    <span class="px-2 py-0.5 bg-amber-50 text-amber-600 text-xs font-medium rounded-lg border border-amber-200">+{{ ($seedInfo['app_count'] ?? 0) - ($seedInfo['ai_count'] ?? 0) }} chưa chấm AI</span>
                                                @endif
                                            @elseif(($seedInfo['app_count'] ?? 0) > 0)
                                                <span class="px-2 py-0.5 bg-amber-50 text-amber-700 text-xs font-semibold rounded-lg border border-amber-200">📋 Có ứng viên, chưa chấm AI</span>
                                            @else
                                                <span class="px-2 py-0.5 bg-gray-50 text-gray-500 text-xs font-medium rounded-lg border border-gray-200">Chưa có ứng viên</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center space-x-6">
                                <!-- Applications Count -->
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-gray-900">{{ $job->applications_count }}</div>
                                    <div class="text-xs text-gray-500">Ứng viên</div>
                                </div>

                                <!-- Status Badge -->
                                <div class="min-w-[200px]">
                                    <form method="POST" action="{{ route('admin.jobs.update-status', $job->id) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="px-3 py-2 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none text-sm">
                                            <option value="published" {{ $job->status === 'published' ? 'selected' : '' }}>🟢 Đang tuyển</option>
                                            <option value="draft" {{ $job->status === 'draft' ? 'selected' : '' }}>📝 Nháp</option>
                                            <option value="closed" {{ $job->status === 'closed' ? 'selected' : '' }}>🔴 Đã đóng</option>
                                        </select>
                                        <button type="submit" class="px-3 py-2 rounded-xl bg-gray-100 text-gray-700 text-sm font-semibold hover:bg-indigo-600 hover:text-white transition-all">
                                            Lưu
                                        </button>
                                    </form>
                                </div>

                                <!-- AI Shortlist CTA (primary) -->
                                <a href="{{ route('admin.jobs.ai-shortlist', $job->id) }}" 
                                   class="inline-flex items-center px-5 py-2.5 rounded-xl bg-gradient-to-r from-violet-500 to-purple-600 text-white font-semibold hover:shadow-lg hover:shadow-violet-300/40 transition-all duration-300" style="background: linear-gradient(to right, #8b5cf6, #9333ea);">
                                    🤖 AI Shortlist
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>

                                <!-- View applications (secondary) -->
                                <a href="{{ route('admin.jobs.applications', $job->id) }}" 
                                   class="inline-flex items-center px-4 py-2.5 rounded-xl bg-gray-100 text-gray-600 font-medium hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-300 text-sm">
                                    Ứng viên
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-12 text-center">
                <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Chưa có việc làm nào</h3>
                <p class="text-gray-500 mb-6">Bắt đầu đăng tuyển để tìm kiếm ứng viên phù hợp</p>
                <a href="{{ route('admin.jobs.create') }}" class="inline-flex items-center px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold hover:shadow-lg transition-all duration-300">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Đăng việc đầu tiên
                </a>
            </div>
        @endif
    </div>
</x-layouts.app>
