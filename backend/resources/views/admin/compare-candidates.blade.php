<x-layouts.app title="So sánh ứng viên - {{ $job->title }}">
    <div class="space-y-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <a href="{{ route('admin.jobs.applications', $job->id) }}" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium flex items-center gap-1 mb-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Quay lại danh sách
                </a>
                <h1 class="text-3xl font-bold text-gray-900">⚖️ So sánh ứng viên</h1>
                <p class="text-gray-600 mt-1">{{ $job->title }}</p>
            </div>
        </div>

        <!-- Candidate Selector -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Chọn ứng viên để so sánh (tối đa 4)</h3>
            <form action="{{ route('admin.jobs.compare', $job->id) }}" method="GET" id="compareForm">
                <div class="flex flex-wrap gap-3">
                    @foreach($allApplications as $app)
                        <label class="flex items-center gap-2 px-4 py-2 rounded-xl border-2 cursor-pointer transition-all
                            {{ in_array($app->id, $selectedIds) ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300' }}">
                            <input type="checkbox" name="ids[]" value="{{ $app->id }}" 
                                {{ in_array($app->id, $selectedIds) ? 'checked' : '' }}
                                class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500"
                                onchange="this.form.submit()">
                            <span class="font-medium text-gray-700">{{ $app->candidate?->name ?? 'N/A' }}</span>
                            @if($app->cv_manual_score !== null)
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $app->cv_manual_score >= 70 ? 'bg-emerald-100 text-emerald-700' : ($app->cv_manual_score >= 50 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                                    {{ number_format($app->cv_manual_score, 0) }}
                                </span>
                            @endif
                        </label>
                    @endforeach
                </div>
            </form>
        </div>

        @if($applications->count() > 0)
            <!-- Comparison Table -->
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
                                <th class="px-6 py-4 text-left font-semibold w-48">Tiêu chí</th>
                                @foreach($applications as $app)
                                    <th class="px-6 py-4 text-center font-semibold min-w-[200px]">
                                        <div class="flex flex-col items-center gap-2">
                                            <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center text-lg font-bold">
                                                {{ substr($app->candidate?->name ?? 'N', 0, 1) }}
                                            </div>
                                            <span>{{ $app->candidate?->name ?? 'N/A' }}</span>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <!-- CV Score -->
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold text-gray-700">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">📊</span>
                                        Điểm CV
                                    </div>
                                </td>
                                @foreach($applications as $app)
                                    <td class="px-6 py-4 text-center">
                                        @if($app->cv_manual_score !== null)
                                            <div class="inline-flex flex-col items-center">
                                                <span class="text-2xl font-bold {{ $app->cv_manual_score >= 70 ? 'text-emerald-600' : ($app->cv_manual_score >= 50 ? 'text-amber-600' : 'text-red-600') }}">
                                                    {{ number_format($app->cv_manual_score, 0) }}
                                                </span>
                                                <div class="w-24 h-2 bg-gray-200 rounded-full mt-2">
                                                    <div class="h-full rounded-full {{ $app->cv_manual_score >= 70 ? 'bg-emerald-500' : ($app->cv_manual_score >= 50 ? 'bg-amber-500' : 'bg-red-500') }}" 
                                                        style="width: {{ min(100, max(0, (float) $app->cv_manual_score)) }}%"></div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-gray-400">Chưa tính</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>

                            <!-- Email -->
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold text-gray-700">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">📧</span>
                                        Email
                                    </div>
                                </td>
                                @foreach($applications as $app)
                                    <td class="px-6 py-4 text-center text-gray-600 text-sm">
                                        {{ $app->candidate?->email ?? 'N/A' }}
                                    </td>
                                @endforeach
                            </tr>

                            <!-- Phone -->
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold text-gray-700">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">📱</span>
                                        Điện thoại
                                    </div>
                                </td>
                                @foreach($applications as $app)
                                    <td class="px-6 py-4 text-center text-gray-600">
                                        {{ $app->candidate?->phone ?? 'Chưa có' }}
                                    </td>
                                @endforeach
                            </tr>

                            <!-- Status -->
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold text-gray-700">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">📋</span>
                                        Trạng thái
                                    </div>
                                </td>
                                @foreach($applications as $app)
                                    <td class="px-6 py-4 text-center">
                                        @php
                                            $statusConfig = [
                                                'submitted' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'Đã nộp'],
                                                'reviewing' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => 'Đang xem'],
                                                'shortlisted' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'label' => 'Được chọn'],
                                                'interviewed' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'label' => 'Đã PV'],
                                                'offered' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'label' => 'Có offer'],
                                                'rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => 'Từ chối'],
                                            ];
                                            $status = $statusConfig[$app->status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => $app->status];
                                        @endphp
                                        <span class="{{ $status['bg'] }} {{ $status['text'] }} px-3 py-1 rounded-full text-sm font-semibold">
                                            {{ $status['label'] }}
                                        </span>
                                    </td>
                                @endforeach
                            </tr>

                            <!-- Applied Date -->
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold text-gray-700">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">📅</span>
                                        Ngày nộp
                                    </div>
                                </td>
                                @foreach($applications as $app)
                                    <td class="px-6 py-4 text-center text-gray-600 text-sm">
                                        {{ $app->applied_at?->format('d/m/Y H:i') ?? $app->created_at->format('d/m/Y H:i') }}
                                    </td>
                                @endforeach
                            </tr>

                            <!-- Skills -->
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold text-gray-700">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">🛠️</span>
                                        Kỹ năng
                                    </div>
                                </td>
                                @foreach($applications as $app)
                                    <td class="px-6 py-4 text-center">
                                        @if($app->candidate?->skills)
                                            <div class="flex flex-wrap justify-center gap-1">
                                                @foreach(array_slice(explode(',', $app->candidate->skills), 0, 5) as $skill)
                                                    <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-xs rounded-full">{{ trim($skill) }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-400 text-sm">Chưa cập nhật</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>

                            <!-- Experience -->
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold text-gray-700">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">💼</span>
                                        Kinh nghiệm
                                    </div>
                                </td>
                                @foreach($applications as $app)
                                    <td class="px-6 py-4 text-center">
                                        @if($app->candidate?->experience)
                                            <p class="text-sm text-gray-600 line-clamp-3">{{ Str::limit($app->candidate->experience, 150) }}</p>
                                        @else
                                            <span class="text-gray-400 text-sm">Chưa cập nhật</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>

                            <!-- Education -->
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold text-gray-700">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">🎓</span>
                                        Học vấn
                                    </div>
                                </td>
                                @foreach($applications as $app)
                                    <td class="px-6 py-4 text-center">
                                        @if($app->candidate?->education)
                                            <p class="text-sm text-gray-600">{{ Str::limit($app->candidate->education, 100) }}</p>
                                        @else
                                            <span class="text-gray-400 text-sm">Chưa cập nhật</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>

                            <!-- Notes -->
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold text-gray-700">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">📝</span>
                                        Ghi chú
                                    </div>
                                </td>
                                @foreach($applications as $app)
                                    <td class="px-6 py-4 text-center">
                                        @if($app->notes)
                                            <p class="text-sm text-gray-600 italic">{{ Str::limit($app->notes, 100) }}</p>
                                        @else
                                            <span class="text-gray-400 text-sm">Chưa có ghi chú</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>

                            <!-- CV Download -->
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold text-gray-700">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">📄</span>
                                        CV
                                    </div>
                                </td>
                                @foreach($applications as $app)
                                    <td class="px-6 py-4 text-center">
                                        @if($app->cv_file_path)
                                            <a href="{{ route('admin.applications.download-cv', $app->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition-colors text-sm font-medium">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                Tải CV
                                            </a>
                                        @else
                                            <span class="text-gray-400 text-sm">Không có</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Summary -->
            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-2xl p-6 border border-indigo-100">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <span class="text-xl">🏆</span>
                    Tổng kết
                </h3>
                @php
                    $bestMatch = $applications->sortByDesc('cv_manual_score')->first();
                @endphp
                @if($bestMatch && $bestMatch->cv_manual_score !== null)
                    <p class="text-gray-700">
                        Ứng viên có điểm CV cao nhất là 
                        <strong class="text-indigo-600">{{ $bestMatch->candidate?->name }}</strong> 
                        với <strong class="text-emerald-600">{{ number_format($bestMatch->cv_manual_score, 0) }}</strong> điểm.
                    </p>
                @else
                    <p class="text-gray-500">Chưa có đủ dữ liệu để đánh giá.</p>
                @endif
            </div>
        @else
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <div class="text-6xl mb-4">🔍</div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Chưa chọn ứng viên</h3>
                <p class="text-gray-500">Vui lòng chọn ít nhất một ứng viên để so sánh.</p>
            </div>
        @endif
    </div>
</x-layouts.app>
