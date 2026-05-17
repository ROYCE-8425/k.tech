<x-layouts.app>
    {{-- Role-aware context bar --}}
    @auth
        @if(config('app.demo_mode'))
            <div class="mb-8 animate-fade-in">
                @if(Auth::user()->role === 'candidate')
                    <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <div>
                                <p class="font-bold text-indigo-900">Bạn đang xem với vai Ứng viên</p>
                                <p class="text-indigo-600 text-sm">Chọn một công việc bên dưới → Ứng tuyển → Xem AI phân tích</p>
                            </div>
                        </div>
                        <a href="{{ route('candidate.applications') }}" class="hidden sm:inline-flex items-center px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-all">
                            <svg class="w-4 h-4 mr-1.5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg> Đơn đã nộp
                        </a>
                    </div>
                @elseif(Auth::user()->role === 'recruiter' || Auth::user()->role === 'admin')
                    <div class="bg-purple-50 border border-purple-200 rounded-2xl p-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            </div>
                            <div>
                                <p class="font-bold text-purple-900">Bạn đang xem với vai Nhà tuyển dụng</p>
                                <p class="text-purple-600 text-sm">Chọn job → Nhấn "AI Shortlist" → Xem xếp hạng ứng viên</p>
                            </div>
                        </div>
                        <div class="hidden sm:flex items-center gap-2">
                            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-purple-100 text-purple-700 text-sm font-semibold hover:bg-purple-200 transition-all">
                                <svg class="w-4 h-4 mr-1.5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg> Dashboard
                            </a>
                            <a href="{{ route('admin.jobs.create') }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-purple-600 text-white text-sm font-semibold hover:bg-purple-700 transition-all">
                                + Đăng tuyển
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    @endauth

    {{-- Jobs Section --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Việc làm đang tuyển</h1>
                <p class="text-gray-500 mt-1 text-sm">{{ $jobs->total() }} vị trí · Chọn để xem chi tiết và ứng tuyển</p>
            </div>

            {{-- Compact search --}}
            <form action="{{ route('home') }}" method="GET" class="hidden md:flex items-center gap-2">
                <input type="hidden" name="sector" value="{{ $sector ?? 'it' }}">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Tìm kiếm..." class="pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent w-48">
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-all">Tìm</button>
                @if(request()->hasAny(['keyword']))
                    <a href="{{ route('home') }}" class="text-xs text-gray-400 hover:text-red-500">✕</a>
                @endif
            </form>
        </div>
    </div>

    @if($jobs->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-10">
            @foreach($jobs as $index => $job)
                <a href="{{ Auth::check() && (Auth::user()->role === 'recruiter' || Auth::user()->role === 'admin') ? route('admin.jobs.ai-shortlist', $job->id) : route('jobs.show', $job->id) }}"
                   class="block bg-white rounded-2xl border border-gray-100 hover:border-indigo-300 hover:shadow-xl hover:shadow-indigo-100/50 transition-all duration-300 overflow-hidden group animate-fade-in"
                   style="animation-delay: {{ $index * 0.05 }}s;">

                    {{-- Card top accent --}}
                    <div class="h-1.5 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

                    <div class="p-5">
                        {{-- Company & title --}}
                        <div class="flex items-start gap-3 mb-3">
                            <div class="w-11 h-11 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center flex-shrink-0 overflow-hidden">
                                @if($job->company && $job->company->logo_path)
                                    <img src="{{ asset('storage/' . $job->company->logo_path) }}" alt="{{ $job->company->name ?? '' }}" class="w-full h-full object-cover">
                                @else
                                    <span class="text-sm font-bold gradient-text">{{ strtoupper(substr($job->company->name ?? 'C', 0, 1)) }}</span>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs text-indigo-600 font-semibold">{{ $job->company->name ?? 'Công ty' }}</p>
                                <h3 class="text-base font-bold text-gray-900 group-hover:text-indigo-600 transition-colors line-clamp-2">
                                    {{ $job->title }}
                                </h3>
                            </div>
                        </div>

                        {{-- Description preview --}}
                        <p class="text-gray-500 text-sm mb-3 line-clamp-2">
                            {{ Str::limit(strip_tags($job->description), 90) }}
                        </p>

                        {{-- Demo seed-aware badges --}}
                        @if(config('app.demo_mode') && !empty($demoSeedInfo[$job->id]))
                            @php $seedInfo = $demoSeedInfo[$job->id]; @endphp
                            <div class="flex flex-wrap gap-1.5 mb-3">
                                @auth
                                    @if(Auth::user()->role === 'candidate')
                                        @if(!empty($seedInfo['applied']) && !empty($seedInfo['has_ai_result']))
                                            <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-xs font-semibold rounded-lg border border-emerald-200"><svg class="w-3.5 h-3.5 inline-block -mt-0.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Đã ứng tuyển</span>
                                            <span class="px-2 py-0.5 bg-violet-50 text-violet-600 text-xs font-medium rounded-lg border border-violet-200"><svg class="w-3.5 h-3.5 inline-block -mt-0.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>Có AI follow-up</span>
                                        @elseif(!empty($seedInfo['applied']))
                                            <span class="px-2 py-0.5 bg-amber-50 text-amber-700 text-xs font-semibold rounded-lg border border-amber-200"><svg class="w-3.5 h-3.5 inline-block -mt-0.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>Đã ứng tuyển</span>
                                        @else
                                            <span class="px-2 py-0.5 bg-blue-50 text-blue-600 text-xs font-medium rounded-lg border border-blue-200"><svg class="w-3.5 h-3.5 inline-block -mt-0.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>Nên thử apply</span>
                                        @endif
                                    @elseif(Auth::user()->role === 'recruiter' || Auth::user()->role === 'admin')
                                        @if(($seedInfo['app_count'] ?? 0) > 0 && ($seedInfo['ai_count'] ?? 0) > 0)
                                            <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-xs font-semibold rounded-lg border border-emerald-200"><svg class="w-3.5 h-3.5 inline-block -mt-0.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>Có AI shortlist sẵn</span>
                                            @if(($seedInfo['app_count'] ?? 0) > ($seedInfo['ai_count'] ?? 0))
                                                <span class="px-2 py-0.5 bg-amber-50 text-amber-600 text-xs font-medium rounded-lg border border-amber-200">+{{ ($seedInfo['app_count'] ?? 0) - ($seedInfo['ai_count'] ?? 0) }} chưa chấm AI</span>
                                            @endif
                                        @elseif(($seedInfo['app_count'] ?? 0) > 0)
                                            <span class="px-2 py-0.5 bg-amber-50 text-amber-700 text-xs font-semibold rounded-lg border border-amber-200"><svg class="w-3.5 h-3.5 inline-block -mt-0.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>Có ứng viên, chưa chấm AI</span>
                                        @else
                                            <span class="px-2 py-0.5 bg-gray-50 text-gray-500 text-xs font-medium rounded-lg border border-gray-200">Chưa có ứng viên</span>
                                        @endif
                                    @endif
                                @endauth
                            </div>
                        @endif

                        {{-- Tags --}}
                        <div class="flex flex-wrap gap-1.5 mb-4">
                            @if($job->seniority)
                                <span class="px-2 py-0.5 bg-violet-50 text-violet-700 text-xs font-semibold rounded-lg">{{ ucfirst($job->seniority) }}</span>
                            @endif
                            @if($job->location)
                                <span class="px-2 py-0.5 bg-gray-50 text-gray-600 text-xs rounded-lg flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg> {{ $job->location }}</span>
                            @endif
                            @if($job->salary_min || $job->salary_max)
                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-xs rounded-lg">
                                    @if($job->salary_min && $job->salary_max)
                                        {{ number_format($job->salary_min/1000000) }}-{{ number_format($job->salary_max/1000000) }}M
                                    @elseif($job->salary_min)
                                        Từ {{ number_format($job->salary_min/1000000) }}M
                                    @else
                                        Tới {{ number_format($job->salary_max/1000000) }}M
                                    @endif
                                </span>
                            @endif
                        </div>

                        {{-- Required skills preview --}}
                        @if(is_array($job->required_skills) && count($job->required_skills) > 0)
                            <div class="flex flex-wrap gap-1 mb-3">
                                @foreach(array_slice($job->required_skills, 0, 4) as $skill)
                                    <span class="px-2 py-0.5 bg-indigo-50 text-indigo-600 text-xs font-medium rounded-md">{{ $skill }}</span>
                                @endforeach
                                @if(count($job->required_skills) > 4)
                                    <span class="px-2 py-0.5 bg-gray-50 text-gray-500 text-xs rounded-md">+{{ count($job->required_skills) - 4 }}</span>
                                @endif
                            </div>
                        @endif

                        {{-- Footer with role-aware CTA --}}
                        <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                            <span class="text-xs text-gray-400">{{ $job->created_at->diffForHumans() }}</span>
                            @auth
                                @if(Auth::user()->role === 'recruiter' || Auth::user()->role === 'admin')
                                    @if(config('app.demo_mode') && !empty($demoSeedInfo[$job->id]) && ($demoSeedInfo[$job->id]['ai_count'] ?? 0) > 0)
                                        <span class="text-xs font-semibold text-emerald-600 group-hover:text-emerald-700 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg> Xem AI Shortlist →
                                        </span>
                                    @else
                                        <span class="text-xs font-semibold text-purple-600 group-hover:text-purple-700 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg> AI Shortlist →
                                        </span>
                                    @endif
                                @else
                                    @if(config('app.demo_mode') && !empty($demoSeedInfo[$job->id]) && !empty($demoSeedInfo[$job->id]['applied']))
                                        <span class="text-xs font-semibold text-emerald-600 group-hover:text-emerald-700">
                                            Xem kết quả AI →
                                        </span>
                                    @else
                                        <span class="text-xs font-semibold text-indigo-600 group-hover:text-indigo-700">
                                            Xem & Ứng tuyển →
                                        </span>
                                    @endif
                                @endif
                            @else
                                <span class="text-xs font-semibold text-indigo-600 group-hover:text-indigo-700">
                                    Xem chi tiết →
                                </span>
                            @endauth
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="flex justify-center">
            {{ $jobs->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="text-center py-16">
            <div class="w-20 h-20 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Chưa có việc làm nào</h3>
            <p class="text-gray-500 text-sm">Hãy quay lại sau để xem các cơ hội việc làm mới nhất.</p>
        </div>
    @endif
</x-layouts.app>
