<x-layouts.app title="Sửa công ty">
    <div class="max-w-4xl mx-auto space-y-8">
        <a href="{{ route('admin.companies.index') }}" class="inline-flex items-center text-gray-500 hover:text-indigo-600 group transition-colors">
            <div class="w-10 h-10 rounded-xl bg-gray-100 group-hover:bg-indigo-100 flex items-center justify-center mr-3 transition-colors">
                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </div>
            <span class="font-medium">Quay lại danh sách công ty</span>
        </a>

        <div class="text-center">
            <div class="w-24 h-24 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-4xl font-bold mx-auto mb-4 shadow-xl overflow-hidden">
                @if($company->logo_path)
                    <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" class="w-full h-full object-cover">
                @else
                    {{ strtoupper(substr($company->name, 0, 1)) }}
                @endif
            </div>
            <h1 class="text-3xl font-bold text-gray-900">Sửa công ty</h1>
            <p class="text-gray-600 mt-2">Cập nhật thông tin hiển thị cho ứng viên.</p>
        </div>

        <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6">
                <h2 class="text-xl font-bold text-white flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Hồ sơ công ty
                </h2>
                <p class="text-indigo-100 text-sm mt-1">Thông tin hiển thị cho ứng viên ở trang job.</p>
            </div>

            <form action="{{ route('admin.companies.update', $company->id) }}" method="POST" enctype="multipart/form-data" class="p-8 space-y-8">
                @csrf
                @method('PUT')

                @if($errors->any())
                    <div class="p-4 bg-red-50 border border-red-200 rounded-xl">
                        <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">1) Thông tin cơ bản</h3>
                    <p class="text-sm text-gray-500 mb-6">Tên/website/địa chỉ của công ty.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tên công ty <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $company->name) }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none transition-all" required>
                            @error('name')
                                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Website</label>
                            <input type="text" name="website" value="{{ old('website', $company->website) }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none transition-all" placeholder="https://...">
                            @error('website')
                                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Địa chỉ</label>
                        <input type="text" name="address" value="{{ old('address', $company->address) }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none transition-all" placeholder="VD: Hà Nội">
                        @error('address')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">2) Giới thiệu</h3>
                    <p class="text-sm text-gray-500 mb-6">Mô tả ngắn về công ty (tùy chọn).</p>

                    <textarea name="description" rows="6" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none transition-all" placeholder="VD: Công ty sản phẩm, môi trường trẻ...">{{ old('description', $company->description) }}</textarea>
                    @error('description')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">3) Logo</h3>
                    <p class="text-sm text-gray-500 mb-6">PNG/JPG (tối đa 5MB). Upload sẽ thay logo cũ.</p>

                    @if($company->logo_path)
                        <div class="mb-4">
                            <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" class="h-20 w-20 rounded-2xl object-cover border border-gray-200">
                        </div>
                    @endif

                    <input type="file" name="logo" accept="image/*" class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-indigo-50 file:text-indigo-700 file:font-semibold hover:file:bg-indigo-600 hover:file:text-white">
                    @error('logo')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.companies.index') }}" class="px-5 py-3 rounded-xl bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition-all">Huỷ</a>
                    <button type="submit" class="px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold hover:shadow-lg transition-all">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
