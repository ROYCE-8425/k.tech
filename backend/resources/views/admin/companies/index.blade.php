<x-layouts.app title="Công ty">
    <div class="max-w-5xl mx-auto space-y-8">
        <div class="text-center">
            <div class="w-24 h-24 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-4xl font-bold mx-auto mb-4 shadow-xl">
                🏢
            </div>
            <h1 class="text-3xl font-bold text-gray-900">Quản lý công ty</h1>
            <p class="text-gray-600 mt-2">Tạo và cập nhật thông tin công ty để đăng việc.</p>
        </div>

        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-5 py-3 rounded-2xl bg-white border-2 border-gray-200 text-gray-700 font-bold hover:border-indigo-300 hover:text-indigo-600 transition-all duration-300">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Dashboard
            </a>

            <a href="{{ route('admin.companies.create') }}" class="inline-flex items-center px-6 py-3 rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold shadow-xl hover:shadow-2xl hover:shadow-indigo-500/30 hover:scale-105 transition-all duration-300">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Thêm công ty
            </a>
        </div>

        @if(session('status'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <p class="text-emerald-700 font-medium">{{ session('status') }}</p>
            </div>
        @endif

        <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6">
                <h2 class="text-xl font-bold text-white flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M7 7V5a2 2 0 012-2h6a2 2 0 012 2v2M5 7v14a2 2 0 002 2h10a2 2 0 002-2V7"></path>
                    </svg>
                    Danh sách công ty
                </h2>
                <p class="text-indigo-100 text-sm mt-1">Chọn công ty khi đăng việc.</p>
            </div>

            <div class="p-8">
                @if($companies->count() === 0)
                    <div class="p-6 rounded-2xl bg-yellow-50 border border-yellow-200">
                        <p class="text-yellow-800 font-medium">⚠️ Chưa có công ty nào. Hãy tạo công ty đầu tiên.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($companies as $company)
                            <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-16 h-16 rounded-2xl bg-white shadow flex items-center justify-center overflow-hidden">
                                        @if($company->logo_path)
                                            <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" class="w-full h-full object-cover">
                                        @else
                                            <span class="text-2xl font-bold gradient-text">{{ strtoupper(substr($company->name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-lg font-bold text-gray-900 truncate">{{ $company->name }}</p>
                                        <p class="text-sm text-gray-500 truncate">{{ $company->address ?? '—' }}</p>
                                        @if($company->website)
                                            <p class="text-xs text-indigo-600 truncate">{{ $company->website }}</p>
                                        @endif
                                    </div>
                                    <a href="{{ route('admin.companies.edit', $company->id) }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-white border border-gray-200 text-gray-700 font-semibold hover:border-indigo-300 hover:text-indigo-600 transition-all">
                                        Sửa
                                    </a>
                                </div>

                                @if($company->description)
                                    <div class="mt-4 text-sm text-gray-600 leading-relaxed">
                                        {{ \Illuminate\Support\Str::limit(strip_tags($company->description), 160) }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
