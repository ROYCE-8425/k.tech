<x-layouts.app>
    <div class="max-w-5xl mx-auto">
        <div class="mb-8">
            <a href="{{ route('admin.jobs.applications', $application->job_id) }}" class="inline-flex items-center text-gray-500 hover:text-indigo-600 mb-4 group transition-colors">
                <div class="w-10 h-10 rounded-xl bg-gray-100 group-hover:bg-indigo-100 flex items-center justify-center mr-3 transition-colors">
                    <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </div>
                <span class="font-medium">Quay lại danh sách ứng viên</span>
            </a>

            <div class="flex flex-col gap-2">
                <h1 class="text-2xl font-bold text-gray-900">Chấm CV (Manual Rubric)</h1>
                <p class="text-gray-600">
                    Job: <span class="font-semibold">{{ $application->job->title }}</span> •
                    Ứng viên: <span class="font-semibold">{{ $application->candidate->name ?? 'N/A' }}</span>
                </p>
            </div>
        </div>

        @if(session('status'))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800">
                {{ session('status') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 overflow-hidden mb-6">
            <div class="p-6 border-b border-gray-100">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Rubric</p>
                        <p class="text-lg font-bold text-gray-900">{{ $rubric->name }} ({{ $rubric->key }})</p>
                    </div>

                    <form method="GET" action="{{ route('admin.applications.score', $application->id) }}" class="flex items-center gap-3">
                        <label class="text-sm font-semibold text-gray-700">Profile</label>
                        <select name="profile_id" class="px-4 py-2 rounded-xl border-2 border-gray-200 focus:border-indigo-500">
                            @foreach($profiles as $p)
                                <option value="{{ $p->id }}" {{ (int)$selectedProfile->id === (int)$p->id ? 'selected' : '' }}>
                                    {{ $p->name }} ({{ $p->key }})
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition-colors">Đổi</button>
                    </form>
                </div>

                @if($existingBreakdown && isset($existingBreakdown['total']))
                    <div class="mt-5 p-4 rounded-2xl bg-gray-50 border border-gray-100">
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="text-gray-700">
                                <span class="text-sm text-gray-500">Tổng điểm</span>
                                <div class="text-2xl font-bold">{{ number_format((float)($existingBreakdown['total'] ?? 0), 2) }} / {{ (int)($existingBreakdown['rubric']['total_max'] ?? $rubric->total_max) }}</div>
                            </div>
                            <div class="text-gray-700">
                                <span class="text-sm text-gray-500">Xếp loại</span>
                                <div class="text-lg font-bold">{{ $existingBreakdown['grade']['label'] ?? ($application->cv_manual_grade ?: 'N/A') }}</div>
                                @if(!empty($existingBreakdown['grade']['note']))
                                    <div class="text-sm text-gray-600">{{ $existingBreakdown['grade']['note'] }}</div>
                                @endif
                            </div>
                            @if($application->cv_manual_scored_at)
                                <div class="text-gray-700">
                                    <span class="text-sm text-gray-500">Chấm lúc</span>
                                    <div class="text-sm font-semibold">{{ $application->cv_manual_scored_at->format('d/m/Y H:i') }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.applications.score.store', $application->id) }}" class="p-6">
                @csrf
                <input type="hidden" name="profile_id" value="{{ $selectedProfile->id }}">

                <div class="space-y-6">
                    @foreach($groups as $g)
                        <div class="p-5 rounded-2xl border-2 border-gray-100">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <div class="text-sm font-semibold text-indigo-700">Nhóm {{ $g['code'] }}</div>
                                    <div class="text-lg font-bold text-gray-900">{{ $g['name'] }}</div>
                                </div>
                                <div class="text-sm text-gray-600">Tối đa: <span class="font-semibold">{{ $g['max_score'] }}</span></div>
                            </div>

                            <div class="space-y-4">
                                @foreach($g['criteria'] as $c)
                                    @php
                                        $cfg = is_array($c['rule_config'] ?? null) ? $c['rule_config'] : [];
                                        $type = $c['rule_type'] ?? '';
                                    @endphp

                                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900">{{ $c['code'] }} • {{ $c['name'] }}</div>
                                                <div class="text-xs text-gray-500">Tối đa: {{ $c['max_score'] }} • Rule: {{ $type }}</div>
                                            </div>

                                            @if($existingBreakdown && isset($existingBreakdown['criteria'][$c['code']]['score']))
                                                <div class="text-sm text-gray-700">
                                                    Điểm: <span class="font-bold">{{ number_format((float)$existingBreakdown['criteria'][$c['code']]['score'], 2) }}</span>
                                                    @if(isset($existingBreakdown['criteria'][$c['code']]['weight']))
                                                        <span class="text-gray-500">(x{{ number_format((float)$existingBreakdown['criteria'][$c['code']]['weight'], 2) }})</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>

                                        <div class="mt-4">
                                            @if($type === 'per_unit_cap')
                                                @php
                                                    $k = $cfg['input_key'] ?? '';
                                                    $label = $cfg['label'] ?? $k;
                                                @endphp
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ $label }}</label>
                                                <input type="number" step="0.01" name="{{ $k }}" value="{{ old($k, $existingInputs[$k] ?? '') }}"
                                                       class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500">
                                                <p class="mt-2 text-xs text-gray-500">Mỗi đơn vị: {{ $cfg['points_per_unit'] ?? 0 }} điểm • Cap: {{ $cfg['cap'] ?? $c['max_score'] }}</p>
                                                @error($k)
                                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                                @enderror

                                            @elseif($type === 'weighted_two_inputs_cap')
                                                @php
                                                    $k1 = $cfg['major_input_key'] ?? '';
                                                    $k2 = $cfg['minor_input_key'] ?? '';
                                                    $l1 = $cfg['major_label'] ?? $k1;
                                                    $l2 = $cfg['minor_label'] ?? $k2;
                                                @endphp
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div>
                                                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ $l1 }}</label>
                                                        <input type="number" step="0.01" name="{{ $k1 }}" value="{{ old($k1, $existingInputs[$k1] ?? '') }}"
                                                               class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500">
                                                        <p class="mt-2 text-xs text-gray-500">{{ $cfg['major_points'] ?? 0 }} điểm / đơn vị</p>
                                                        @error($k1)
                                                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                                        @enderror
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ $l2 }}</label>
                                                        <input type="number" step="0.01" name="{{ $k2 }}" value="{{ old($k2, $existingInputs[$k2] ?? '') }}"
                                                               class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500">
                                                        <p class="mt-2 text-xs text-gray-500">{{ $cfg['minor_points'] ?? 0 }} điểm / đơn vị</p>
                                                        @error($k2)
                                                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                                        @enderror
                                                    </div>
                                                </div>
                                                <p class="mt-2 text-xs text-gray-500">Cap: {{ $cfg['cap'] ?? $c['max_score'] }}</p>

                                            @elseif($type === 'choice_map')
                                                @php
                                                    $k = $cfg['input_key'] ?? '';
                                                    $choices = is_array($cfg['choices'] ?? null) ? $cfg['choices'] : [];
                                                    $choiceLabels = is_array($cfg['choice_labels'] ?? null) ? $cfg['choice_labels'] : [];
                                                    $label = $cfg['label'] ?? $k;
                                                @endphp
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ $label }}</label>
                                                <select name="{{ $k }}" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500">
                                                    <option value="">-- Chọn --</option>
                                                    @foreach($choices as $val => $pts)
                                                        <option value="{{ $val }}" {{ (string)old($k, $existingInputs[$k] ?? '') === (string)$val ? 'selected' : '' }}>
                                                            {{ $choiceLabels[$val] ?? $val }} ({{ $pts }} điểm)
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error($k)
                                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                                @enderror

                                            @else
                                                <div class="text-sm text-gray-600">Rule type chưa hỗ trợ hiển thị: {{ $type }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-sm text-gray-500">Lưu sẽ tính điểm theo profile đang chọn và ghi vào ứng viên.</p>
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold shadow-xl hover:shadow-2xl hover:shadow-indigo-500/30 transition-all duration-300">
                        Lưu điểm chấm CV
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
