<x-layouts.app title="Lịch phỏng vấn - Admin">
    <div class="space-y-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">📅 Lịch phỏng vấn</h1>
                <p class="text-gray-600 mt-2">Quản lý các buổi phỏng vấn ứng viên</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <form action="{{ route('admin.interviews') }}" method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-2">Trạng thái</label>
                    <select name="status" class="px-4 py-2.5 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none">
                        <option value="">Tất cả</option>
                        <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>📅 Đã lên lịch</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>✅ Hoàn thành</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>❌ Đã hủy</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-2">Từ ngày</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="px-4 py-2.5 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-2">Đến ngày</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="px-4 py-2.5 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none">
                </div>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-colors">
                    Lọc
                </button>
                @if(request()->hasAny(['status', 'from_date', 'to_date']))
                    <a href="{{ route('admin.interviews') }}" class="px-6 py-2.5 text-gray-600 hover:text-gray-800 font-medium">
                        Xóa bộ lọc
                    </a>
                @endif
            </form>
        </div>

        @if($interviews->isEmpty())
            <!-- Empty State -->
            <div class="bg-white rounded-3xl shadow-xl p-12 text-center">
                <div class="w-24 h-24 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="text-5xl">📭</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Chưa có lịch phỏng vấn</h2>
                <p class="text-gray-600 mb-8 max-w-md mx-auto">Bạn có thể lên lịch phỏng vấn từ trang chi tiết ứng viên.</p>
            </div>
        @else
            <!-- Interview List -->
            <div class="space-y-4">
                @foreach($interviews as $interview)
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all" x-data="{ showFeedback: false }">
                        <div class="p-6">
                            <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                                <!-- Date/Time Block -->
                                <div class="flex-shrink-0 text-center">
                                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-500 flex flex-col items-center justify-center text-white shadow-lg">
                                        <span class="text-2xl font-bold">{{ $interview->scheduled_at->format('d') }}</span>
                                        <span class="text-xs uppercase">{{ $interview->scheduled_at->format('M') }}</span>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-2 font-medium">{{ $interview->scheduled_at->format('H:i') }}</p>
                                </div>

                                <!-- Interview Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            {{ $interview->application->candidate?->name ?? 'Ứng viên' }}
                                        </h3>
                                        @php
                                            $statusColors = [
                                                'scheduled' => 'bg-blue-100 text-blue-700',
                                                'completed' => 'bg-emerald-100 text-emerald-700',
                                                'cancelled' => 'bg-red-100 text-red-700',
                                                'rescheduled' => 'bg-amber-100 text-amber-700',
                                            ];
                                        @endphp
                                        <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $statusColors[$interview->status] ?? 'bg-gray-100 text-gray-700' }}">
                                            {{ $interview->getStatusLabel() }}
                                        </span>
                                        <span class="px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-700">
                                            {{ $interview->getTypeLabel() }}
                                        </span>
                                    </div>
                                    
                                    <p class="text-gray-600 font-medium">
                                        {{ $interview->application->job?->title ?? 'Vị trí' }}
                                    </p>
                                    
                                    <div class="flex flex-wrap items-center gap-4 mt-2 text-sm text-gray-500">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            {{ $interview->duration_minutes }} phút
                                        </span>
                                        @if($interview->location)
                                            <span class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                </svg>
                                                {{ Str::limit($interview->location, 40) }}
                                            </span>
                                        @endif
                                        @if($interview->rating)
                                            <span class="flex items-center gap-1 text-amber-500">
                                                @for($i = 1; $i <= 5; $i++)
                                                    @if($i <= $interview->rating)
                                                        ★
                                                    @else
                                                        ☆
                                                    @endif
                                                @endfor
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-2">
                                    <button @click="showFeedback = !showFeedback" class="px-4 py-2 bg-indigo-50 text-indigo-600 font-semibold rounded-xl hover:bg-indigo-100 transition-colors">
                                        <span x-text="showFeedback ? 'Ẩn' : 'Chi tiết'"></span>
                                    </button>
                                    
                                    @if($interview->status === 'scheduled')
                                        <form action="{{ route('admin.interviews.update', $interview->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" class="px-4 py-2 bg-emerald-100 text-emerald-700 font-semibold rounded-xl hover:bg-emerald-200 transition-colors">
                                                ✓ Hoàn thành
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <!-- Feedback Form (expandable) -->
                            <div x-show="showFeedback" x-transition class="mt-6 pt-6 border-t border-gray-100">
                                <form action="{{ route('admin.interviews.update', $interview->id) }}" method="POST" class="space-y-4">
                                    @csrf
                                    @method('PATCH')
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Trạng thái</label>
                                            <select name="status" class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none">
                                                <option value="scheduled" {{ $interview->status == 'scheduled' ? 'selected' : '' }}>📅 Đã lên lịch</option>
                                                <option value="completed" {{ $interview->status == 'completed' ? 'selected' : '' }}>✅ Hoàn thành</option>
                                                <option value="cancelled" {{ $interview->status == 'cancelled' ? 'selected' : '' }}>❌ Đã hủy</option>
                                                <option value="rescheduled" {{ $interview->status == 'rescheduled' ? 'selected' : '' }}>🔄 Đổi lịch</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Đánh giá</label>
                                            <div class="flex items-center gap-2">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <label class="cursor-pointer">
                                                        <input type="radio" name="rating" value="{{ $i }}" class="hidden peer" {{ $interview->rating == $i ? 'checked' : '' }}>
                                                        <span class="text-3xl peer-checked:text-amber-500 text-gray-300 hover:text-amber-400 transition-colors">★</span>
                                                    </label>
                                                @endfor
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nhận xét / Feedback</label>
                                        <textarea name="feedback" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none resize-none" placeholder="Nhận xét về buổi phỏng vấn...">{{ $interview->feedback }}</textarea>
                                    </div>
                                    
                                    <div class="flex justify-end">
                                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-colors">
                                            Lưu thay đổi
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="flex justify-center">
                {{ $interviews->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
