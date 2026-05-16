<x-layouts.app title="Hồ sơ của tôi - Hệ thống chấm CV tự động IT SOLO LEVELING">
    @php
        $itRoleOptions = $itRoleOptions ?? [];
        $itSkillsOptions = $itSkillsOptions ?? [];
        $experienceOptions = $experienceOptions ?? [];
        $educationOptions = $educationOptions ?? [];

        $selectedSector = 'it'; // IT only

        $selectedItRole = old('it_role', $candidate?->profile_data['primary_role'] ?? null);

        $selectedItSkills = old('it_skills', 
            $candidate?->profile_data['skills'] ?? ($candidate?->skills ? array_filter(array_map('trim', explode(',', $candidate->skills))) : []));
        if (!is_array($selectedItSkills)) $selectedItSkills = [];

        $selectedExperience = old('experience', $candidate?->experience);
        $selectedEducation = old('education', $candidate?->education);

        $cvQuick = $candidate?->profile_data['cv_quick'] ?? [];
        if (!is_array($cvQuick)) $cvQuick = [];
        $cvQuickSelf = old('cv_quick_self_description', $cvQuick['self_description'] ?? '');
        $cvQuickEducationJson = old('cv_quick_education_json', !empty($cvQuick['education'] ?? null) ? json_encode($cvQuick['education']) : '');
        $cvQuickWorkJson = old('cv_quick_work_experiences_json', !empty($cvQuick['work_experiences'] ?? null) ? json_encode($cvQuick['work_experiences']) : '');
        $cvQuickSkillsJson = old('cv_quick_skills_json', !empty($cvQuick['skills'] ?? null) ? json_encode($cvQuick['skills']) : '');
        $cvQuickCertificationsJson = old('cv_quick_certifications_json', !empty($cvQuick['certifications'] ?? null) ? json_encode($cvQuick['certifications']) : '');
    @endphp
    <div class="max-w-4xl mx-auto space-y-8">
        <!-- Header -->
        <div class="text-center">
            <div class="w-24 h-24 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-4xl font-bold mx-auto mb-4 shadow-xl">
                {{ substr($user->name, 0, 1) }}
            </div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $user->name }}</h1>
            <p class="text-gray-600 mt-2">{{ $user->email }}</p>
            <p class="text-sm text-gray-500 mt-1">Hoàn thiện hồ sơ để hệ thống gợi ý việc phù hợp và tăng tỷ lệ được nhà tuyển dụng liên hệ.</p>
        </div>

        <!-- Profile Form -->
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6">
                <h2 class="text-xl font-bold text-white flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Chỉnh sửa hồ sơ
                </h2>
                <p class="text-indigo-100 text-sm mt-1">Cập nhật thông tin để tăng cơ hội được tuyển dụng</p>
            </div>

            <form action="{{ route('candidate.profile.update') }}" method="POST" enctype="multipart/form-data" class="p-8 space-y-8" x-data="{ sector: '{{ $selectedSector }}' }">
                @csrf

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

                @if($errors->any())
                    <div class="p-4 bg-red-50 border border-red-200 rounded-xl">
                        <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Thông tin cơ bản -->
                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">1) Thông tin cơ bản</h3>
                    <p class="text-sm text-gray-500 mb-6">Nhập đúng để nhà tuyển dụng dễ liên hệ.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Họ và tên *</label>
                        <input 
                            type="text" 
                            name="name" 
                            value="{{ old('name', $user->name) }}"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none transition-all"
                            required
                        >
                    </div>

                    <!-- Phone -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Số điện thoại</label>
                        <input 
                            type="tel" 
                            name="phone" 
                            value="{{ old('phone', $user->phone ?? $candidate?->phone) }}"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none transition-all"
                            placeholder="0901234567"
                        >
                    </div>
                    </div>
                </div>

                <!-- Ngành & Thông tin nghề nghiệp -->
                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">2) Thông tin nghề nghiệp CNTT</h3>
                    <p class="text-sm text-gray-500 mb-6">Điền thông tin để hệ thống chấm CV chính xác hơn.</p>

                    <input type="hidden" name="sector" value="it">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Vai trò chính *</label>

                            <select name="it_role" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none transition-all">
                                <option value="">-- Chọn vai trò CNTT --</option>
                                @foreach($itRoleOptions as $r)
                                    <option value="{{ $r }}" @selected($r === $selectedItRole)>{{ $r }}</option>
                                @endforeach
                            </select>

                            @error('it_role')
                                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Kinh nghiệm</label>
                            <select name="experience" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none transition-all">
                                <option value="">-- Chọn mức kinh nghiệm --</option>
                                @foreach($experienceOptions as $exp)
                                    <option value="{{ $exp }}" @selected($exp === $selectedExperience)>{{ $exp }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Kỹ năng chính <span class="text-gray-400 font-normal">(có thể chọn nhiều)</span>
                        </label>

                        <select name="it_skills[]" multiple class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none transition-all">
                            @foreach($itSkillsOptions as $s)
                                <option value="{{ $s }}" @selected(in_array($s, $selectedItSkills, true))>{{ $s }}</option>
                            @endforeach
                        </select>

                        @error('it_skills')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-2">Giữ Ctrl (Windows) để chọn nhiều.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Học vấn</label>
                            <select name="education" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none transition-all">
                                <option value="">-- Chọn trình độ học vấn --</option>
                                @foreach($educationOptions as $edu)
                                    <option value="{{ $edu }}" @selected($edu === $selectedEducation)>{{ $edu }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Liên kết (tùy chọn) -->
                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">3) Liên kết (tùy chọn)</h3>
                    <p class="text-sm text-gray-500 mb-6">Chỉ cần dán link, không cần mô tả dài.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">GitHub</label>
                            <input type="url" name="github_url" value="{{ old('github_url', $candidate?->github_url) }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none transition-all" placeholder="https://github.com/...">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">LinkedIn</label>
                            <input type="url" name="linkedin_url" value="{{ old('linkedin_url', $candidate?->linkedin_url) }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none transition-all" placeholder="https://www.linkedin.com/in/...">
                        </div>
                    </div>
                </div>

                <!-- CV nhanh (tùy chọn) -->
                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">4) CV nhanh (tùy chọn)</h3>
                            <p class="text-sm text-gray-500">Thông tin này sẽ được tự điền khi bạn chọn “Tạo CV nhanh” lúc ứng tuyển. Bạn có thể sửa ở đây để dùng lại lần sau.</p>
                        </div>
                        <button type="button" onclick="openProfileCvDialog()" class="px-4 py-2 rounded-xl bg-indigo-50 text-indigo-700 text-sm font-semibold hover:bg-indigo-600 hover:text-white transition-all">Sửa CV nhanh</button>
                    </div>

                    <div class="mt-4 p-4 bg-white rounded-xl border border-gray-100">
                        <p class="text-sm font-semibold text-gray-800 mb-1">Mô tả bản thân</p>
                        <p class="text-sm text-gray-600">{{ $cvQuickSelf ? (mb_substr($cvQuickSelf, 0, 180) . (mb_strlen($cvQuickSelf) > 180 ? '…' : '')) : 'Chưa có' }}</p>
                    </div>

                    <!-- Hidden fields submitted with profile update -->
                    <textarea name="cv_quick_self_description" id="cv_quick_self_description" class="hidden">{{ $cvQuickSelf }}</textarea>
                    <textarea name="cv_quick_education_json" id="cv_quick_education_json" class="hidden">{{ $cvQuickEducationJson }}</textarea>
                    <textarea name="cv_quick_work_experiences_json" id="cv_quick_work_experiences_json" class="hidden">{{ $cvQuickWorkJson }}</textarea>
                    <textarea name="cv_quick_skills_json" id="cv_quick_skills_json" class="hidden">{{ $cvQuickSkillsJson }}</textarea>
                    <textarea name="cv_quick_certifications_json" id="cv_quick_certifications_json" class="hidden">{{ $cvQuickCertificationsJson }}</textarea>

                    @error('cv_quick_self_description')
                        <p class="mt-3 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                    @error('cv_quick_education_json')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                    @error('cv_quick_work_experiences_json')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                    @error('cv_quick_skills_json')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Minh chứng (upload) -->
                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">5) Minh chứng (bằng cấp/chứng chỉ)</h3>
                    <p class="text-sm text-gray-500 mb-6">Upload ảnh/PDF minh chứng (nếu có). Có thể chọn nhiều file.</p>

                    @php
                        $proofFiles = old('proof_files', $candidate?->proof_files ?? []);
                        if (!is_array($proofFiles)) $proofFiles = [];
                    @endphp

                    @if(!empty($candidate?->proof_files) && is_array($candidate->proof_files))
                        <div class="mb-4 p-4 bg-white rounded-xl border border-gray-100">
                            <p class="font-semibold text-gray-800 mb-2">File đã tải lên</p>
                            <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                                @foreach($candidate->proof_files as $path)
                                    <li>{{ basename($path) }}</li>
                                @endforeach
                            </ul>
                            <p class="text-xs text-gray-500 mt-2">(Hiện tại chỉ hiển thị tên file. Nếu bạn muốn có nút tải xuống/xoá file, mình sẽ làm tiếp.)</p>
                        </div>
                    @endif

                    <div class="relative">
                        <input
                            type="file"
                            name="proof_files[]"
                            id="proof_files"
                            multiple
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="sr-only"
                            onchange="updateProofFiles(this)"
                        >
                        <label for="proof_files" class="flex items-center justify-center gap-3 w-full px-6 py-4 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer hover:border-indigo-500 hover:bg-indigo-50 transition-all">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <div class="text-center">
                                <p class="font-medium text-gray-700" id="proofFileName">Tải lên minh chứng</p>
                                <p class="text-sm text-gray-500">PDF, JPG, PNG (Tối đa 5MB/file)</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- CV Upload -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">CV mặc định</label>
                    
                    @if($candidate?->file_path_cv)
                        <div class="mb-4 p-4 bg-indigo-50 rounded-xl flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">CV hiện tại</p>
                                    <p class="text-sm text-gray-500">{{ basename($candidate->file_path_cv) }}</p>
                                </div>
                            </div>
                            <span class="text-emerald-600 text-sm font-semibold">✓ Đã tải lên</span>
                        </div>
                    @endif

                    <div class="relative">
                        <input 
                            type="file" 
                            name="cv_file" 
                            id="cv_file"
                            accept=".doc,.docx,.pdf"
                            class="sr-only"
                            onchange="updateFileName(this)"
                        >
                        <label for="cv_file" class="flex items-center justify-center gap-3 w-full px-6 py-4 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer hover:border-indigo-500 hover:bg-indigo-50 transition-all">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <div class="text-center">
                                <p class="font-medium text-gray-700" id="fileName">{{ $candidate?->file_path_cv ? 'Thay đổi CV' : 'Tải lên CV mới' }}</p>
                                <p class="text-sm text-gray-500">DOC, DOCX, PDF (Tối đa 5MB)</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end pt-4">
                    <button 
                        type="submit" 
                        class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl"
                    >
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Lưu thay đổi
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <!-- CV nhanh dialog (profile) -->
        <dialog id="profileCvDialog" class="rounded-3xl p-0 w-full max-w-4xl backdrop:bg-black/50">
            <div class="bg-white rounded-3xl overflow-hidden shadow-2xl max-h-[90vh] overflow-y-auto">
                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 px-8 py-6 sticky top-0 z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-white flex items-center gap-3">
                                <span class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">📝</span>
                                Sửa CV nhanh
                            </h3>
                            <p class="text-indigo-100 mt-1">Thông tin sẽ được tự động điền khi ứng tuyển</p>
                        </div>
                        <button type="button" onclick="closeProfileCvDialog()" class="w-12 h-12 rounded-xl bg-white/20 text-white hover:bg-white/30 transition-all text-xl font-bold">✕</button>
                    </div>
                </div>

                <div class="p-8 space-y-8">
                    <!-- Mô tả bản thân -->
                    <div class="rounded-2xl border-2 border-indigo-100 bg-gradient-to-br from-indigo-50/50 to-purple-50/50 p-6">
                        <label class="block text-base font-bold text-gray-800 mb-3 flex items-center gap-2">
                            <span class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600">💬</span>
                            Mô tả bản thân
                        </label>
                        <textarea id="profileSelfDescription" rows="4" class="w-full px-5 py-4 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 focus:outline-none transition-all text-base resize-none" placeholder="VD: Backend Developer với 5+ năm kinh nghiệm. Chuyên về Laravel, MySQL, Redis. Có kinh nghiệm lead team 5 người...">{{ $cvQuickSelf }}</textarea>
                    </div>

                    <!-- Ngoại ngữ & Chứng chỉ -->
                    <div class="rounded-2xl border-2 border-blue-100 bg-gradient-to-br from-blue-50/50 to-cyan-50/50 p-6">
                        <h4 class="text-lg font-bold text-gray-800 mb-2 flex items-center gap-2">
                            <span class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">📜</span>
                            Ngoại ngữ & Chứng chỉ
                        </h4>
                        <p class="text-sm text-gray-500 mb-6">Giúp hệ thống đánh giá chính xác hơn</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">🇬🇧 Trình độ tiếng Anh</label>
                                <select id="profileEnglishLevel" class="w-full px-4 py-3.5 rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 focus:outline-none text-base bg-white">
                                    <option value="">-- Chưa đánh giá --</option>
                                    <option value="basic">Cơ bản (A1-A2)</option>
                                    <option value="intermediate">Trung cấp (B1-B2)</option>
                                    <option value="advanced">Nâng cao (C1-C2)</option>
                                    <option value="native">Bản ngữ / Native</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">📊 TOEIC (nếu có)</label>
                                <input type="number" id="profileToeicScore" class="w-full px-4 py-3.5 rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 focus:outline-none text-base" placeholder="VD: 850" min="0" max="990">
                                <p class="text-xs text-gray-500 mt-1.5">Điểm từ 0-990</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">📊 IELTS (nếu có)</label>
                                <input type="number" id="profileIeltsScore" step="0.5" class="w-full px-4 py-3.5 rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 focus:outline-none text-base" placeholder="VD: 7.0" min="0" max="9">
                                <p class="text-xs text-gray-500 mt-1.5">Điểm từ 0-9</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">🎓 Chứng chỉ chuyên môn</label>
                                <select id="profileCertifications" multiple class="w-full px-4 py-3.5 rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 focus:outline-none text-base bg-white" size="4">
                                    <optgroup label="IT - Development">
                                        <option value="aws_certified">AWS Certified</option>
                                        <option value="google_cloud">Google Cloud Professional</option>
                                        <option value="microsoft_azure">Microsoft Azure</option>
                                        <option value="cisco_ccna">Cisco CCNA/CCNP</option>
                                        <option value="oracle_java">Oracle Java Certification</option>
                                        <option value="pmp">PMP - Project Management</option>
                                    </optgroup>
                                    <optgroup label="IT - Security">
                                        <option value="cissp">CISSP</option>
                                        <option value="ceh">CEH - Ethical Hacker</option>
                                        <option value="comptia_security">CompTIA Security+</option>
                                    </optgroup>
                                    <optgroup label="Marketing & Media">
                                        <option value="google_analytics">Google Analytics IQ</option>
                                        <option value="google_ads">Google Ads Certified</option>
                                        <option value="facebook_blueprint">Facebook Blueprint</option>
                                        <option value="hubspot">HubSpot Certification</option>
                                        <option value="adobe_certified">Adobe Certified Expert</option>
                                    </optgroup>
                                    <optgroup label="Khác">
                                        <option value="other">Chứng chỉ khác</option>
                                    </optgroup>
                                </select>
                                <p class="text-xs text-gray-500 mt-1.5">Giữ Ctrl/Cmd để chọn nhiều</p>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t border-blue-200">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">⏱️ Tổng số năm kinh nghiệm</label>
                            <div class="flex items-center gap-4">
                                <input type="number" id="profileYearsOfExperience" class="w-32 px-4 py-3.5 rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 focus:outline-none text-base text-center font-semibold" placeholder="VD: 3" min="0" max="50" step="0.5">
                                <span class="text-base text-gray-600 font-medium">năm</span>
                            </div>
                        </div>
                    </div>

                    <!-- Học vấn -->
                    <div class="rounded-2xl border-2 border-emerald-100 bg-gradient-to-br from-emerald-50/50 to-teal-50/50 p-6">
                        <div class="flex items-center justify-between mb-5">
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center text-xl">🎓</span>
                                <div>
                                    <h4 class="text-lg font-bold text-gray-800">Học vấn</h4>
                                    <p class="text-sm text-gray-500">(tùy chọn)</p>
                                </div>
                            </div>
                            <button type="button" onclick="addProfileEducationRow()" class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700 hover:shadow-lg transition-all flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                Thêm học vấn
                            </button>
                        </div>
                        <div id="profileEducationRows" class="space-y-4"></div>
                    </div>

                    <!-- Kinh nghiệm làm việc -->
                    <div class="rounded-2xl border-2 border-amber-100 bg-gradient-to-br from-amber-50/50 to-orange-50/50 p-6">
                        <div class="flex items-center justify-between mb-5">
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center text-xl">💼</span>
                                <div>
                                    <h4 class="text-lg font-bold text-gray-800">Kinh nghiệm làm việc</h4>
                                    <p class="text-sm text-gray-500">(tùy chọn)</p>
                                </div>
                            </div>
                            <button type="button" onclick="addProfileWorkRow()" class="px-5 py-2.5 rounded-xl bg-amber-600 text-white text-sm font-bold hover:bg-amber-700 hover:shadow-lg transition-all flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                Thêm kinh nghiệm
                            </button>
                        </div>
                        <div id="profileWorkRows" class="space-y-4"></div>
                    </div>

                    <!-- Kỹ năng -->
                    <div class="rounded-2xl border-2 border-purple-100 bg-gradient-to-br from-purple-50/50 to-pink-50/50 p-6">
                        <div class="flex items-center gap-3 mb-5">
                            <span class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center text-xl">🛠️</span>
                            <div>
                                <h4 class="text-lg font-bold text-gray-800">Kỹ năng</h4>
                                <p class="text-sm text-gray-500">Click để chọn/bỏ chọn kỹ năng</p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <!-- Hard Skills -->
                            <div class="rounded-2xl bg-white border-2 border-indigo-100 p-5 shadow-sm">
                                <p class="font-bold text-indigo-700 mb-4 flex items-center gap-2">
                                    <span class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center text-sm">💻</span>
                                    Hard Skills
                                    <span id="hardSkillsCount" class="ml-auto text-xs bg-indigo-600 text-white px-2 py-1 rounded-full">0 đã chọn</span>
                                </p>
                                
                                <!-- Dropdown menu for Hard Skills -->
                                <div class="relative">
                                    <button type="button" onclick="toggleSkillDropdown('hard')" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 hover:border-indigo-400 bg-white text-left flex items-center justify-between transition-all">
                                        <span class="text-gray-500">Chọn Hard Skills...</span>
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </button>
                                    <div id="hardSkillsDropdown" class="hidden absolute z-50 w-full mt-2 bg-white border-2 border-indigo-200 rounded-2xl shadow-2xl max-h-80 overflow-y-auto">
                                        <div class="p-3 space-y-3">
                                            <div>
                                                <p class="text-xs font-bold text-gray-400 uppercase mb-2 px-1">Languages & Frameworks</p>
                                                <div class="flex flex-wrap gap-2" id="hardSkillsGroup1">
                                                    @foreach(['PHP', 'Laravel', 'JavaScript', 'TypeScript', 'React', 'Vue.js', 'Angular', 'Node.js', 'Python', 'Django', 'Java', 'Spring Boot', 'C#', '.NET', 'Go', 'Kotlin', 'Flutter', 'React Native'] as $skill)
                                                    <button type="button" data-skill="{{ $skill }}" data-type="hard" onclick="toggleSkill(this)" class="skill-chip px-3 py-1.5 rounded-full text-xs font-medium border-2 border-gray-200 bg-gray-50 text-gray-600 hover:border-indigo-400 hover:bg-indigo-50 transition-all">{{ $skill }}</button>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-400 uppercase mb-2 px-1">Databases</p>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach(['MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'Elasticsearch', 'SQL Server', 'SQLite'] as $skill)
                                                    <button type="button" data-skill="{{ $skill }}" data-type="hard" onclick="toggleSkill(this)" class="skill-chip px-3 py-1.5 rounded-full text-xs font-medium border-2 border-gray-200 bg-gray-50 text-gray-600 hover:border-indigo-400 hover:bg-indigo-50 transition-all">{{ $skill }}</button>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-400 uppercase mb-2 px-1">DevOps & Cloud</p>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach(['Docker', 'Kubernetes', 'AWS', 'Google Cloud', 'Azure', 'CI/CD', 'Jenkins', 'GitHub Actions', 'Linux', 'Nginx'] as $skill)
                                                    <button type="button" data-skill="{{ $skill }}" data-type="hard" onclick="toggleSkill(this)" class="skill-chip px-3 py-1.5 rounded-full text-xs font-medium border-2 border-gray-200 bg-gray-50 text-gray-600 hover:border-indigo-400 hover:bg-indigo-50 transition-all">{{ $skill }}</button>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-400 uppercase mb-2 px-1">Frontend & Design</p>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach(['HTML/CSS', 'TailwindCSS', 'Bootstrap', 'SASS/SCSS', 'Figma', 'Photoshop'] as $skill)
                                                    <button type="button" data-skill="{{ $skill }}" data-type="hard" onclick="toggleSkill(this)" class="skill-chip px-3 py-1.5 rounded-full text-xs font-medium border-2 border-gray-200 bg-gray-50 text-gray-600 hover:border-indigo-400 hover:bg-indigo-50 transition-all">{{ $skill }}</button>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-400 uppercase mb-2 px-1">Testing & Tools</p>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach(['Git', 'Unit Testing', 'Jest', 'PHPUnit', 'Selenium', 'Postman', 'Jira'] as $skill)
                                                    <button type="button" data-skill="{{ $skill }}" data-type="hard" onclick="toggleSkill(this)" class="skill-chip px-3 py-1.5 rounded-full text-xs font-medium border-2 border-gray-200 bg-gray-50 text-gray-600 hover:border-indigo-400 hover:bg-indigo-50 transition-all">{{ $skill }}</button>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-400 uppercase mb-2 px-1">API & Architecture</p>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach(['REST API', 'GraphQL', 'Microservices', 'WebSocket', 'RabbitMQ', 'Kafka'] as $skill)
                                                    <button type="button" data-skill="{{ $skill }}" data-type="hard" onclick="toggleSkill(this)" class="skill-chip px-3 py-1.5 rounded-full text-xs font-medium border-2 border-gray-200 bg-gray-50 text-gray-600 hover:border-indigo-400 hover:bg-indigo-50 transition-all">{{ $skill }}</button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Selected Hard Skills Display -->
                                <div id="selectedHardSkills" class="flex flex-wrap gap-2 mt-3 min-h-[32px]"></div>
                            </div>

                            <!-- Soft Skills -->
                            <div class="rounded-2xl bg-white border-2 border-pink-100 p-5 shadow-sm">
                                <p class="font-bold text-pink-700 mb-4 flex items-center gap-2">
                                    <span class="w-6 h-6 bg-pink-100 rounded-full flex items-center justify-center text-sm">🤝</span>
                                    Soft Skills
                                    <span id="softSkillsCount" class="ml-auto text-xs bg-pink-600 text-white px-2 py-1 rounded-full">0 đã chọn</span>
                                </p>
                                
                                <!-- Dropdown menu for Soft Skills -->
                                <div class="relative">
                                    <button type="button" onclick="toggleSkillDropdown('soft')" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 hover:border-pink-400 bg-white text-left flex items-center justify-between transition-all">
                                        <span class="text-gray-500">Chọn Soft Skills...</span>
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </button>
                                    <div id="softSkillsDropdown" class="hidden absolute z-50 w-full mt-2 bg-white border-2 border-pink-200 rounded-2xl shadow-2xl max-h-80 overflow-y-auto">
                                        <div class="p-3 space-y-3">
                                            <div>
                                                <p class="text-xs font-bold text-gray-400 uppercase mb-2 px-1">Giao tiếp & Làm việc nhóm</p>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach(['Communication' => 'Giao tiếp', 'Teamwork' => 'Làm việc nhóm', 'Presentation' => 'Thuyết trình', 'Negotiation' => 'Đàm phán', 'Collaboration' => 'Hợp tác'] as $value => $label)
                                                    <button type="button" data-skill="{{ $value }}" data-type="soft" onclick="toggleSkill(this)" class="skill-chip px-3 py-1.5 rounded-full text-xs font-medium border-2 border-gray-200 bg-gray-50 text-gray-600 hover:border-pink-400 hover:bg-pink-50 transition-all">{{ $label }}</button>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-400 uppercase mb-2 px-1">Lãnh đạo & Quản lý</p>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach(['Leadership' => 'Lãnh đạo', 'Project Management' => 'Quản lý dự án', 'Team Management' => 'Quản lý nhóm', 'Mentoring' => 'Đào tạo', 'Decision Making' => 'Ra quyết định'] as $value => $label)
                                                    <button type="button" data-skill="{{ $value }}" data-type="soft" onclick="toggleSkill(this)" class="skill-chip px-3 py-1.5 rounded-full text-xs font-medium border-2 border-gray-200 bg-gray-50 text-gray-600 hover:border-pink-400 hover:bg-pink-50 transition-all">{{ $label }}</button>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-400 uppercase mb-2 px-1">Tư duy & Giải quyết vấn đề</p>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach(['Problem Solving' => 'Giải quyết vấn đề', 'Critical Thinking' => 'Tư duy phản biện', 'Analytical Thinking' => 'Phân tích', 'Creativity' => 'Sáng tạo', 'Logical Thinking' => 'Tư duy logic'] as $value => $label)
                                                    <button type="button" data-skill="{{ $value }}" data-type="soft" onclick="toggleSkill(this)" class="skill-chip px-3 py-1.5 rounded-full text-xs font-medium border-2 border-gray-200 bg-gray-50 text-gray-600 hover:border-pink-400 hover:bg-pink-50 transition-all">{{ $label }}</button>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-400 uppercase mb-2 px-1">Quản lý bản thân</p>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach(['Time Management' => 'Quản lý thời gian', 'Adaptability' => 'Thích ứng', 'Stress Management' => 'Quản lý stress', 'Work Under Pressure' => 'Làm việc áp lực', 'Self-learning' => 'Tự học'] as $value => $label)
                                                    <button type="button" data-skill="{{ $value }}" data-type="soft" onclick="toggleSkill(this)" class="skill-chip px-3 py-1.5 rounded-full text-xs font-medium border-2 border-gray-200 bg-gray-50 text-gray-600 hover:border-pink-400 hover:bg-pink-50 transition-all">{{ $label }}</button>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-400 uppercase mb-2 px-1">Tính cách & Phương pháp</p>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach(['Attention to Detail' => 'Chi tiết', 'Responsibility' => 'Trách nhiệm', 'Professionalism' => 'Chuyên nghiệp', 'Agile' => 'Agile', 'Scrum' => 'Scrum'] as $value => $label)
                                                    <button type="button" data-skill="{{ $value }}" data-type="soft" onclick="toggleSkill(this)" class="skill-chip px-3 py-1.5 rounded-full text-xs font-medium border-2 border-gray-200 bg-gray-50 text-gray-600 hover:border-pink-400 hover:bg-pink-50 transition-all">{{ $label }}</button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Selected Soft Skills Display -->
                                <div id="selectedSoftSkills" class="flex flex-wrap gap-2 mt-3 min-h-[32px]"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer buttons -->
                    <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200">
                        <button type="button" onclick="closeProfileCvDialog()" class="px-6 py-3.5 rounded-xl bg-gray-100 text-gray-700 font-bold hover:bg-gray-200 transition-all">
                            Đóng
                        </button>
                        <button type="button" onclick="saveProfileCvDialog(event, true)" class="px-8 py-3.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold text-lg hover:shadow-xl hover:shadow-indigo-500/30 hover:scale-[1.02] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Lưu CV nhanh
                        </button>
                    </div>
                </div>
            </div>
        </dialog>

        <!-- Profile Completion Tips -->
        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-2xl p-6">
            <h3 class="font-bold text-amber-800 flex items-center gap-2 mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                Mẹo tăng cơ hội được tuyển
            </h3>
            <ul class="space-y-2 text-amber-700 text-sm">
                <li class="flex items-start gap-2">
                    <span class="text-amber-500">✦</span>
                    Hoàn thiện đầy đủ thông tin kỹ năng và kinh nghiệm
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-amber-500">✦</span>
                    Upload CV được cập nhật và format chuyên nghiệp
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-amber-500">✦</span>
                    Sử dụng từ khóa phù hợp với ngành nghề bạn đang tìm kiếm
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-amber-500">✦</span>
                    Liệt kê các dự án và thành tựu cụ thể trong phần kinh nghiệm
                </li>
            </ul>
        </div>
    </div>

    <script>
        function updateFileName(input) {
            const fileName = document.getElementById('fileName');
            if (input.files && input.files[0]) {
                fileName.textContent = input.files[0].name;
            }
        }

        function updateProofFiles(input) {
            const el = document.getElementById('proofFileName');
            if (!el) return;
            const count = input.files ? input.files.length : 0;
            if (count <= 0) {
                el.textContent = 'Tải lên minh chứng';
                return;
            }
            el.textContent = count === 1 ? input.files[0].name : `Đã chọn ${count} file`;
        }

        // CV nhanh (profile)
        const profileCvDialog = document.getElementById('profileCvDialog');
        let profileEducationIndex = 0;
        let profileWorkIndex = 0;
        let profileCvHydrated = false;
        
        // Skill selection storage
        let selectedHardSkills = [];
        let selectedSoftSkills = [];
        
        // Toggle skill dropdown
        function toggleSkillDropdown(type) {
            const dropdown = document.getElementById(type + 'SkillsDropdown');
            const otherType = type === 'hard' ? 'soft' : 'hard';
            const otherDropdown = document.getElementById(otherType + 'SkillsDropdown');
            
            // Close other dropdown
            if (otherDropdown && !otherDropdown.classList.contains('hidden')) {
                otherDropdown.classList.add('hidden');
            }
            
            // Toggle this dropdown
            if (dropdown) {
                dropdown.classList.toggle('hidden');
            }
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('[id$="SkillsDropdown"]') && !e.target.closest('[onclick*="toggleSkillDropdown"]')) {
                document.getElementById('hardSkillsDropdown')?.classList.add('hidden');
                document.getElementById('softSkillsDropdown')?.classList.add('hidden');
            }
        });
        
        // Toggle skill selection
        function toggleSkill(btn) {
            const skill = btn.dataset.skill;
            const type = btn.dataset.type;
            const skills = type === 'hard' ? selectedHardSkills : selectedSoftSkills;
            const index = skills.indexOf(skill);
            
            if (index === -1) {
                // Add skill
                skills.push(skill);
                btn.classList.remove('border-gray-200', 'bg-gray-50', 'text-gray-600');
                btn.classList.add(type === 'hard' ? 'border-indigo-500' : 'border-pink-500', 
                                  type === 'hard' ? 'bg-indigo-100' : 'bg-pink-100',
                                  type === 'hard' ? 'text-indigo-700' : 'text-pink-700');
            } else {
                // Remove skill
                skills.splice(index, 1);
                btn.classList.add('border-gray-200', 'bg-gray-50', 'text-gray-600');
                btn.classList.remove(type === 'hard' ? 'border-indigo-500' : 'border-pink-500',
                                     type === 'hard' ? 'bg-indigo-100' : 'bg-pink-100',
                                     type === 'hard' ? 'text-indigo-700' : 'text-pink-700');
            }
            
            updateSelectedSkillsDisplay(type);
        }
        
        // Update selected skills display
        function updateSelectedSkillsDisplay(type) {
            const skills = type === 'hard' ? selectedHardSkills : selectedSoftSkills;
            const container = document.getElementById('selected' + (type === 'hard' ? 'Hard' : 'Soft') + 'Skills');
            const countEl = document.getElementById(type + 'SkillsCount');
            
            if (countEl) {
                countEl.textContent = skills.length + ' đã chọn';
            }
            
            if (container) {
                if (skills.length === 0) {
                    container.innerHTML = '<span class="text-gray-400 text-sm italic">Chưa chọn kỹ năng nào</span>';
                } else {
                    container.innerHTML = skills.map(skill => `
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-medium ${type === 'hard' ? 'bg-indigo-100 text-indigo-700' : 'bg-pink-100 text-pink-700'}">
                            ${skill}
                            <button type="button" onclick="removeSkill('${skill}', '${type}')" class="ml-1 hover:text-red-500 transition-colors">&times;</button>
                        </span>
                    `).join('');
                }
            }
        }
        
        // Remove skill from selected
        function removeSkill(skill, type) {
            const skills = type === 'hard' ? selectedHardSkills : selectedSoftSkills;
            const index = skills.indexOf(skill);
            if (index !== -1) {
                skills.splice(index, 1);
            }
            
            // Update button state in dropdown
            const btn = document.querySelector(`button[data-skill="${skill}"][data-type="${type}"]`);
            if (btn) {
                btn.classList.add('border-gray-200', 'bg-gray-50', 'text-gray-600');
                btn.classList.remove(type === 'hard' ? 'border-indigo-500' : 'border-pink-500',
                                     type === 'hard' ? 'bg-indigo-100' : 'bg-pink-100',
                                     type === 'hard' ? 'text-indigo-700' : 'text-pink-700');
            }
            
            updateSelectedSkillsDisplay(type);
        }
        
        // Initialize skill displays
        function initSkillDisplays() {
            updateSelectedSkillsDisplay('hard');
            updateSelectedSkillsDisplay('soft');
        }
        
        // Call on page load
        document.addEventListener('DOMContentLoaded', initSkillDisplays);

        function openProfileCvDialog() {
            if (profileCvDialog && typeof profileCvDialog.showModal === 'function') {
                profileCvDialog.showModal();
            }

            // Luôn hydrate lại từ hidden inputs (để lấy data mới nhất sau khi save)
            hydrateProfileCvFromHidden();
            profileCvHydrated = true;
            
            // Nếu vẫn trống sau khi hydrate, điền data mẫu
            if (document.getElementById('profileEducationRows')?.children?.length === 0) {
                fillSampleData();
            }

            // Đảm bảo có ít nhất 1 row để user có thể bắt đầu nhập
            if (document.getElementById('profileEducationRows')?.children?.length === 0) addProfileEducationRow();
            if (document.getElementById('profileWorkRows')?.children?.length === 0) addProfileWorkRow();
        }
        
        function fillSampleData() {
            console.log('Filling sample data...');
            
            // Self description
            const selfEl = document.getElementById('profileSelfDescription');
            if (selfEl && !selfEl.value) {
                selfEl.value = 'Senior Backend Developer với 5+ năm kinh nghiệm phát triển hệ thống quy mô lớn. Chuyên về Laravel, MySQL, Redis, Docker. Có kinh nghiệm lead team và mentor junior developers.';
            }
            
            // Education
            const eduContainer = document.getElementById('profileEducationRows');
            if (eduContainer && eduContainer.children.length === 0) {
                // Education 1
                addProfileEducationRow();
                const edu1 = eduContainer.lastElementChild;
                if (edu1) {
                    edu1.querySelector('[data-field="school"]').value = 'ĐH Bách Khoa Hà Nội';
                    edu1.querySelector('[data-field="degree_level"]').value = 'ky_su';
                    edu1.querySelector('[data-field="major"]').value = 'Công nghệ thông tin';
                    edu1.querySelector('[data-field="graduation_year"]').value = '2018';
                }
                
                // Education 2
                addProfileEducationRow();
                const edu2 = eduContainer.lastElementChild;
                if (edu2) {
                    edu2.querySelector('[data-field="school"]').value = 'ĐH FPT';
                    edu2.querySelector('[data-field="degree_level"]').value = 'thac_si';
                    edu2.querySelector('[data-field="major"]').value = 'Khoa học máy tính';
                    edu2.querySelector('[data-field="graduation_year"]').value = '2020';
                }
            }
            
            // Work experiences
            const workContainer = document.getElementById('profileWorkRows');
            if (workContainer && workContainer.children.length === 0) {
                // Work 1
                addProfileWorkRow();
                const work1 = workContainer.lastElementChild;
                if (work1) {
                    work1.querySelector('[data-wfield="company_name"]').value = 'FPT Software';
                    work1.querySelector('[data-wfield="position_title"]').value = 'Senior Backend Developer';
                    work1.querySelector('[data-wfield="start_date"]').value = '2021-01';
                    work1.querySelector('[data-wfield="description"]').value = 'Phụ trách phát triển và maintain backend cho dự án E-commerce quy mô 5M+ users. Thiết kế kiến trúc microservices, tối ưu hóa API performance (giảm 60% response time). Implement Redis caching, queue system với Laravel. Mentor 3 junior developers, code review và đảm bảo code quality. Tech stack: Laravel 10, MySQL, Redis, Docker, AWS.';
                    const current1 = work1.querySelector('[data-wcurrent]');
                    if (current1) {
                        current1.checked = true;
                        current1.dispatchEvent(new Event('change'));
                    }
                }
                
                // Work 2
                addProfileWorkRow();
                const work2 = workContainer.lastElementChild;
                if (work2) {
                    work2.querySelector('[data-wfield="company_name"]').value = 'Viettel Software';
                    work2.querySelector('[data-wfield="position_title"]').value = 'Backend Developer';
                    work2.querySelector('[data-wfield="start_date"]').value = '2019-03';
                    work2.querySelector('[data-wfield="end_date"]').value = '2020-12';
                    work2.querySelector('[data-wfield="description"]').value = 'Phát triển RESTful APIs cho hệ thống quản lý nội bộ. Xây dựng module báo cáo, export Excel/PDF. Tối ưu database queries, implement full-text search. Làm việc với team 5 người theo Scrum.';
                }
                
                // Work 3
                addProfileWorkRow();
                const work3 = workContainer.lastElementChild;
                if (work3) {
                    work3.querySelector('[data-wfield="company_name"]').value = 'Startup ABC Tech';
                    work3.querySelector('[data-wfield="position_title"]').value = 'Junior Developer';
                    work3.querySelector('[data-wfield="start_date"]').value = '2018-06';
                    work3.querySelector('[data-wfield="end_date"]').value = '2019-02';
                    work3.querySelector('[data-wfield="description"]').value = 'Tham gia phát triển website booking tour du lịch. Làm việc với Laravel 5.8, MySQL, Bootstrap. Học hỏi về Git, deployment, CI/CD.';
                }
            }
            
            // Skills - select using chip UI
            const hardSkillsToSelect = [
                'PHP', 'Laravel', 'MySQL', 'PostgreSQL', 'Redis', 'MongoDB',
                'Docker', 'Kubernetes', 'AWS', 'Git', 'REST API', 'Microservices', 'CI/CD', 'Unit Testing'
            ];
            selectedHardSkills = [];
            hardSkillsToSelect.forEach(skill => {
                const btn = document.querySelector(`button[data-skill="${skill}"][data-type="hard"]`);
                if (btn) {
                    selectedHardSkills.push(skill);
                    btn.classList.remove('border-gray-200', 'bg-gray-50', 'text-gray-600');
                    btn.classList.add('border-indigo-500', 'bg-indigo-100', 'text-indigo-700');
                }
            });
            updateSelectedSkillsDisplay('hard');
            
            const softSkillsToSelect = [
                'Leadership', 'Mentoring', 'Problem Solving', 
                'Communication', 'Agile', 'Teamwork'
            ];
            selectedSoftSkills = [];
            softSkillsToSelect.forEach(skill => {
                const btn = document.querySelector(`button[data-skill="${skill}"][data-type="soft"]`);
                if (btn) {
                    selectedSoftSkills.push(skill);
                    btn.classList.remove('border-gray-200', 'bg-gray-50', 'text-gray-600');
                    btn.classList.add('border-pink-500', 'bg-pink-100', 'text-pink-700');
                }
            });
            updateSelectedSkillsDisplay('soft');
            
            // Certifications
            const englishEl = document.getElementById('profileEnglishLevel');
            if (englishEl && !englishEl.value) {
                englishEl.value = 'advanced';
            }
            
            const toeicEl = document.querySelector('[name="cv_quick_toeic_score"]');
            if (toeicEl && !toeicEl.value) {
                toeicEl.value = '890';
            }
            
            const ieltsEl = document.querySelector('[name="cv_quick_ielts_score"]');
            if (ieltsEl && !ieltsEl.value) {
                ieltsEl.value = '7.5';
            }
            
            const otherCertsEl = document.querySelector('[name="cv_quick_other_certs"]');
            if (otherCertsEl && !otherCertsEl.value) {
                otherCertsEl.value = 'AWS Solutions Architect Associate (2023), Oracle MySQL Developer (2022), PSM I (2021)';
            }
            
            console.log('Sample data filled!');
        }

        function closeProfileCvDialog() {
            if (profileCvDialog && typeof profileCvDialog.close === 'function') profileCvDialog.close();
        }

        function safeJsonParse(text, fallback) {
            const raw = (text ?? '').toString().trim();
            if (raw === '') return fallback;
            try { return JSON.parse(raw); } catch (e) { return fallback; }
        }

        function addProfileEducationRow() {
            const container = document.getElementById('profileEducationRows');
            if (!container) return;
            const idx = profileEducationIndex++;
            const row = document.createElement('div');
            row.className = 'rounded-2xl bg-white border border-gray-200 p-4';
            row.dataset.idx = String(idx);
            row.innerHTML = `
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Trường</label>
                            <select class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none" data-field="school">
                                <option value="">-- Chọn trường --</option>
                                <optgroup label="Đại học Công nghệ">
                                    <option value="ĐH Bách Khoa Hà Nội">ĐH Bách Khoa Hà Nội</option>
                                    <option value="ĐH Bách Khoa TP.HCM">ĐH Bách Khoa TP.HCM</option>
                                    <option value="ĐH Công nghệ - ĐHQGHN">ĐH Công nghệ - ĐHQGHN</option>
                                    <option value="ĐH FPT">ĐH FPT</option>
                                    <option value="ĐH RMIT">ĐH RMIT</option>
                                    <option value="ĐH Duy Tân">ĐH Duy Tân</option>
                                    <option value="ĐH Văn Lang">ĐH Văn Lang</option>
                                </optgroup>
                                <optgroup label="Đại học Kinh tế">
                                    <option value="ĐH Kinh tế Quốc dân">ĐH Kinh tế Quốc dân</option>
                                    <option value="ĐH Kinh tế TP.HCM">ĐH Kinh tế TP.HCM</option>
                                    <option value="ĐH Ngoại thương">ĐH Ngoại thương</option>
                                    <option value="ĐH Tôn Đức Thắng">ĐH Tôn Đức Thắng</option>
                                </optgroup>
                                <optgroup label="Đại học Tổng hợp">
                                    <option value="ĐH Quốc gia Hà Nội">ĐH Quốc gia Hà Nội</option>
                                    <option value="ĐH Quốc gia TP.HCM">ĐH Quốc gia TP.HCM</option>
                                    <option value="ĐH Khoa học Tự nhiên">ĐH Khoa học Tự nhiên</option>
                                    <option value="ĐH Khoa học Xã hội & Nhân văn">ĐH KHXH & NV</option>
                                </optgroup>
                                <optgroup label="Đại học Truyền thông">
                                    <option value="ĐH Báo chí & Tuyên truyền">ĐH Báo chí & Tuyên truyền</option>
                                    <option value="Học viện Báo chí & Tuyên truyền">HV Báo chí & Tuyên truyền</option>
                                </optgroup>
                                <optgroup label="Cao đẳng / Khác">
                                    <option value="Cao đẳng FPT Polytechnic">CĐ FPT Polytechnic</option>
                                    <option value="Arena Multimedia">Arena Multimedia</option>
                                    <option value="Trường khác">Trường khác</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Loại bằng</label>
                            <select class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none" data-field="degree_level">
                                <option value="">-- Chọn bậc học --</option>
                                <option value="trung_cap">Trung cấp</option>
                                <option value="cao_dang">Cao đẳng</option>
                                <option value="cu_nhan">Cử nhân (Đại học)</option>
                                <option value="ky_su">Kỹ sư (Đại học)</option>
                                <option value="thac_si">Thạc sĩ</option>
                                <option value="tien_si">Tiến sĩ</option>
                                <option value="bootcamp">Bootcamp / Tự học</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Ngành / Chuyên ngành</label>
                            <select class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none" data-field="major">
                                <option value="">-- Chọn ngành --</option>
                                <optgroup label="Công nghệ thông tin">
                                    <option value="Công nghệ thông tin">Công nghệ thông tin</option>
                                    <option value="Khoa học máy tính">Khoa học máy tính</option>
                                    <option value="Kỹ thuật phần mềm">Kỹ thuật phần mềm</option>
                                    <option value="Hệ thống thông tin">Hệ thống thông tin</option>
                                    <option value="An ninh mạng">An ninh mạng</option>
                                    <option value="Trí tuệ nhân tạo">Trí tuệ nhân tạo</option>
                                    <option value="Khoa học dữ liệu">Khoa học dữ liệu</option>
                                </optgroup>
                                <optgroup label="Marketing & Truyền thông">
                                    <option value="Marketing">Marketing</option>
                                    <option value="Marketing số">Marketing số</option>
                                    <option value="Quảng cáo">Quảng cáo</option>
                                    <option value="Quan hệ công chúng (PR)">Quan hệ công chúng (PR)</option>
                                    <option value="Báo chí">Báo chí</option>
                                    <option value="Truyền thông đa phương tiện">Truyền thông đa phương tiện</option>
                                    <option value="Thiết kế đồ họa">Thiết kế đồ họa</option>
                                    <option value="Thiết kế thời trang">Thiết kế thời trang</option>
                                </optgroup>
                                <optgroup label="Kinh tế & Quản trị">
                                    <option value="Quản trị kinh doanh">Quản trị kinh doanh</option>
                                    <option value="Kinh tế">Kinh tế</option>
                                    <option value="Tài chính - Ngân hàng">Tài chính - Ngân hàng</option>
                                    <option value="Kế toán - Kiểm toán">Kế toán - Kiểm toán</option>
                                    <option value="Quản trị nhân lực">Quản trị nhân lực</option>
                                </optgroup>
                                <optgroup label="Khác">
                                    <option value="Ngôn ngữ Anh">Ngôn ngữ Anh</option>
                                    <option value="Khác">Ngành khác</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Năm tốt nghiệp</label>
                            <input type="number" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none" placeholder="2024" data-field="graduation_year" min="1950" max="2100">
                        </div>
                    </div>
                    <button type="button" class="px-3 py-2 rounded-xl bg-gray-100 text-gray-600 hover:bg-red-600 hover:text-white transition-all" onclick="removeProfileEducationRow(this)">Xóa</button>
                </div>
            `;
            container.appendChild(row);
        }

        function removeProfileEducationRow(btn) {
            const row = btn?.closest('[data-idx]');
            if (row) row.remove();
        }

        function addProfileWorkRow() {
            const container = document.getElementById('profileWorkRows');
            if (!container) return;
            const idx = profileWorkIndex++;
            const row = document.createElement('div');
            row.className = 'rounded-2xl bg-white border border-gray-200 p-4';
            row.dataset.widx = String(idx);
            row.innerHTML = `
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tên công ty</label>
                            <input type="text" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none" placeholder="VD: FPT Software" data-wfield="company_name">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Vị trí / Chức vụ</label>
                            <select class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none" data-wfield="position_title">
                                <option value="">-- Chọn vị trí --</option>
                                <optgroup label="IT - Development">
                                    <option value="Intern Developer">Intern Developer</option>
                                    <option value="Junior Frontend Developer">Junior Frontend Developer</option>
                                    <option value="Junior Backend Developer">Junior Backend Developer</option>
                                    <option value="Frontend Developer">Frontend Developer</option>
                                    <option value="Backend Developer">Backend Developer</option>
                                    <option value="Full-stack Developer">Full-stack Developer</option>
                                    <option value="Senior Frontend Developer">Senior Frontend Developer</option>
                                    <option value="Senior Backend Developer">Senior Backend Developer</option>
                                    <option value="Senior Full-stack Developer">Senior Full-stack Developer</option>
                                    <option value="Tech Lead">Tech Lead</option>
                                    <option value="Team Leader">Team Leader</option>
                                    <option value="Software Architect">Software Architect</option>
                                    <option value="CTO">CTO (Chief Technology Officer)</option>
                                </optgroup>
                                <optgroup label="IT - Mobile & QA">
                                    <option value="Mobile Developer">Mobile Developer</option>
                                    <option value="iOS Developer">iOS Developer</option>
                                    <option value="Android Developer">Android Developer</option>
                                    <option value="QA Engineer">QA Engineer</option>
                                    <option value="QA Tester">QA Tester</option>
                                    <option value="QA Lead">QA Lead</option>
                                </optgroup>
                                <optgroup label="IT - DevOps & Data">
                                    <option value="DevOps Engineer">DevOps Engineer</option>
                                    <option value="System Administrator">System Administrator</option>
                                    <option value="Data Analyst">Data Analyst</option>
                                    <option value="Data Engineer">Data Engineer</option>
                                    <option value="Data Scientist">Data Scientist</option>
                                    <option value="ML Engineer">ML Engineer</option>
                                </optgroup>
                                <optgroup label="IT - Design & Security">
                                    <option value="UI/UX Designer">UI/UX Designer</option>
                                    <option value="Product Designer">Product Designer</option>
                                    <option value="Security Engineer">Security Engineer</option>
                                    <option value="Network Engineer">Network Engineer</option>
                                </optgroup>
                                <optgroup label="Marketing & Content">
                                    <option value="Marketing Intern">Marketing Intern</option>
                                    <option value="Digital Marketing Executive">Digital Marketing Executive</option>
                                    <option value="Digital Marketing Specialist">Digital Marketing Specialist</option>
                                    <option value="Digital Marketing Manager">Digital Marketing Manager</option>
                                    <option value="Content Writer">Content Writer</option>
                                    <option value="Content Marketing Specialist">Content Marketing Specialist</option>
                                    <option value="Content Marketing Manager">Content Marketing Manager</option>
                                    <option value="SEO Specialist">SEO Specialist</option>
                                    <option value="SEO Manager">SEO Manager</option>
                                </optgroup>
                                <optgroup label="Social Media & PR">
                                    <option value="Social Media Executive">Social Media Executive</option>
                                    <option value="Social Media Manager">Social Media Manager</option>
                                    <option value="Community Manager">Community Manager</option>
                                    <option value="PR Executive">PR Executive</option>
                                    <option value="PR Manager">PR Manager</option>
                                    <option value="Communications Manager">Communications Manager</option>
                                </optgroup>
                                <optgroup label="Creative & Design">
                                    <option value="Graphic Designer">Graphic Designer</option>
                                    <option value="Senior Graphic Designer">Senior Graphic Designer</option>
                                    <option value="Art Director">Art Director</option>
                                    <option value="Video Editor">Video Editor</option>
                                    <option value="Motion Graphics Designer">Motion Graphics Designer</option>
                                    <option value="Photographer">Photographer</option>
                                </optgroup>
                                <optgroup label="Business & Management">
                                    <option value="Business Analyst">Business Analyst</option>
                                    <option value="Product Manager">Product Manager</option>
                                    <option value="Project Manager">Project Manager</option>
                                    <option value="Scrum Master">Scrum Master</option>
                                    <option value="Account Manager">Account Manager</option>
                                    <option value="Sales Executive">Sales Executive</option>
                                </optgroup>
                                <optgroup label="Khác">
                                    <option value="Freelancer">Freelancer</option>
                                    <option value="Consultant">Consultant</option>
                                    <option value="Other">Vị trí khác</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Ngày bắt đầu</label>
                            <input type="date" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none" data-wfield="start_date">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Ngày kết thúc</label>
                            <input type="date" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none" data-wfield="end_date">
                            <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-600">
                                <input type="checkbox" class="w-4 h-4" data-wcurrent>
                                Đang làm việc tại đây
                            </label>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Mô tả</label>
                            <textarea rows="3" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:outline-none" placeholder="VD: Xây dựng..., tối ưu..." data-wfield="description"></textarea>
                        </div>
                    </div>
                    <button type="button" class="px-3 py-2 rounded-xl bg-gray-100 text-gray-600 hover:bg-red-600 hover:text-white transition-all" onclick="removeProfileWorkRow(this)">Xóa</button>
                </div>
            `;

            const endDateInput = row.querySelector('[data-wfield="end_date"]');
            const currentCb = row.querySelector('[data-wcurrent]');
            const applyCurrent = () => {
                const checked = !!currentCb?.checked;
                if (endDateInput) {
                    endDateInput.disabled = checked;
                    if (checked) endDateInput.value = '';
                }
            };
            currentCb?.addEventListener('change', applyCurrent);
            applyCurrent();

            container.appendChild(row);
        }

        function removeProfileWorkRow(btn) {
            const row = btn?.closest('[data-widx]');
            if (row) row.remove();
        }

        // Skills now using multi-select dropdowns

        function hydrateProfileCvFromHidden() {
            const selfHidden = document.getElementById('cv_quick_self_description')?.value ?? '';
            const selfEl = document.getElementById('profileSelfDescription');
            if (selfEl) selfEl.value = selfHidden;

            const edu = safeJsonParse(document.getElementById('cv_quick_education_json')?.value ?? '', []);
            const eduContainer = document.getElementById('profileEducationRows');
            if (eduContainer) {
                eduContainer.innerHTML = '';
                profileEducationIndex = 0;
                if (Array.isArray(edu) && edu.length > 0) {
                    edu.forEach((item) => {
                        addProfileEducationRow();
                        const row = eduContainer.lastElementChild;
                        if (!row) return;
                        const set = (field, value) => {
                            const el = row.querySelector(`[data-field="${field}"]`);
                            if (el) el.value = value ?? '';
                        };
                        set('school', item?.school ?? '');
                        set('degree_level', item?.degree_level ?? '');
                        set('major', item?.major ?? '');
                        set('graduation_year', item?.graduation_year ?? '');
                    });
                }
            }

            const work = safeJsonParse(document.getElementById('cv_quick_work_experiences_json')?.value ?? '', []);
            const workContainer = document.getElementById('profileWorkRows');
            if (workContainer) {
                workContainer.innerHTML = '';
                profileWorkIndex = 0;
                if (Array.isArray(work) && work.length > 0) {
                    work.forEach((item) => {
                        addProfileWorkRow();
                        const row = workContainer.lastElementChild;
                        if (!row) return;
                        const set = (field, value) => {
                            const el = row.querySelector(`[data-wfield="${field}"]`);
                            if (el) el.value = value ?? '';
                        };
                        set('company_name', item?.company_name ?? '');
                        set('position_title', item?.position_title ?? '');
                        set('start_date', item?.start_date ?? '');
                        set('end_date', item?.end_date ?? '');
                        set('description', item?.description ?? '');
                        const currentCb = row.querySelector('[data-wcurrent]');
                        if (currentCb) {
                            currentCb.checked = !!item?.is_current;
                            currentCb.dispatchEvent(new Event('change'));
                        }
                    });
                }
            }

            const skills = safeJsonParse(document.getElementById('cv_quick_skills_json')?.value ?? '', { hard: [], soft: [] });
            
            // Hydrate Hard Skills with chip UI
            selectedHardSkills = Array.isArray(skills?.hard) ? skills.hard.map(s => s?.name ?? s) : [];
            selectedHardSkills.forEach(skillName => {
                const btn = document.querySelector(`button[data-skill="${skillName}"][data-type="hard"]`);
                if (btn) {
                    btn.classList.remove('border-gray-200', 'bg-gray-50', 'text-gray-600');
                    btn.classList.add('border-indigo-500', 'bg-indigo-100', 'text-indigo-700');
                }
            });
            updateSelectedSkillsDisplay('hard');
            
            // Hydrate Soft Skills with chip UI
            selectedSoftSkills = Array.isArray(skills?.soft) ? skills.soft.map(s => s?.name ?? s) : [];
            selectedSoftSkills.forEach(skillName => {
                const btn = document.querySelector(`button[data-skill="${skillName}"][data-type="soft"]`);
                if (btn) {
                    btn.classList.remove('border-gray-200', 'bg-gray-50', 'text-gray-600');
                    btn.classList.add('border-pink-500', 'bg-pink-100', 'text-pink-700');
                }
            });
            updateSelectedSkillsDisplay('soft');

            // Certifications
            const certData = safeJsonParse(document.getElementById('cv_quick_certifications_json')?.value ?? '', {});
            const englishEl = document.getElementById('profileEnglishLevel');
            if (englishEl && certData?.english_level) englishEl.value = certData.english_level;
            
            const toeicEl = document.getElementById('profileToeicScore');
            if (toeicEl && certData?.toeic_score) toeicEl.value = certData.toeic_score;
            
            const ieltsEl = document.getElementById('profileIeltsScore');
            if (ieltsEl && certData?.ielts_score) ieltsEl.value = certData.ielts_score;
            
            const yearsEl = document.getElementById('profileYearsOfExperience');
            if (yearsEl && certData?.years_experience) yearsEl.value = certData.years_experience;
            
            const certsSelect = document.getElementById('profileCertifications');
            if (certsSelect && Array.isArray(certData?.certifications)) {
                Array.from(certsSelect.options).forEach(opt => {
                    opt.selected = certData.certifications.includes(opt.value);
                });
            }
        }

        function saveProfileCvDialog(evt = null, closeAfterSave = true) {
            const selfDesc = document.getElementById('profileSelfDescription')?.value ?? '';
            const eduRows = Array.from(document.querySelectorAll('#profileEducationRows [data-idx]'));
            const education = eduRows.map((row) => {
                const get = (field) => (row.querySelector(`[data-field="${field}"]`)?.value ?? '').trim();
                return {
                    school: get('school'),
                    degree_level: get('degree_level'),
                    major: get('major'),
                    graduation_year: get('graduation_year'),
                };
            });

            const workRows = Array.from(document.querySelectorAll('#profileWorkRows [data-widx]'));
            const work = workRows.map((row) => {
                const get = (field) => (row.querySelector(`[data-wfield="${field}"]`)?.value ?? '').trim();
                const isCurrent = row.querySelector('[data-wcurrent]')?.checked ?? false;
                return {
                    company_name: get('company_name'),
                    position_title: get('position_title'),
                    start_date: get('start_date'),
                    end_date: isCurrent ? null : get('end_date'),
                    is_current: isCurrent,
                    description: get('description'),
                };
            });

            // Collect Skills từ chip selection
            const hardSkills = selectedHardSkills.map(name => ({ name, level: '4' }));
            const softSkills = selectedSoftSkills.map(name => ({ name, level: '4' }));
            const skills = { hard: hardSkills, soft: softSkills };

            // Certifications
            const englishLevel = document.getElementById('profileEnglishLevel')?.value ?? '';
            const toeicScore = document.getElementById('profileToeicScore')?.value ?? '';
            const ieltsScore = document.getElementById('profileIeltsScore')?.value ?? '';
            const yearsExp = document.getElementById('profileYearsOfExperience')?.value ?? '';
            const certsSelect = document.getElementById('profileCertifications');
            const selectedCerts = certsSelect ? Array.from(certsSelect.selectedOptions).map(opt => opt.value) : [];
            
            const certifications = {
                english_level: englishLevel,
                toeic_score: toeicScore !== '' ? parseFloat(toeicScore) : null,
                ielts_score: ieltsScore !== '' ? parseFloat(ieltsScore) : null,
                years_experience: yearsExp !== '' ? parseFloat(yearsExp) : null,
                certifications: selectedCerts,
            };

            document.getElementById('cv_quick_self_description').value = selfDesc;
            document.getElementById('cv_quick_education_json').value = JSON.stringify(education);
            document.getElementById('cv_quick_work_experiences_json').value = JSON.stringify(work);
            document.getElementById('cv_quick_skills_json').value = JSON.stringify(skills);
            document.getElementById('cv_quick_certifications_json').value = JSON.stringify(certifications);

            // Hiển thị loading
            const btn = evt?.target;
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<svg class="animate-spin h-5 w-5 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
            }

            if (closeAfterSave) {
                closeProfileCvDialog();
            }
            
            // Submit form ngay lập tức - chọn đúng form profile, không phải form logout
            const form = document.querySelector('form[action*="profile"]');
            if (form) {
                console.log('Submitting form with CV data...');
                console.log('Form action:', form.action);
                form.submit();
            } else {
                console.error('Profile form not found!');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = 'Lưu CV nhanh';
                }
            }
        }

        // Auto-open CV nhanh dialog on validation errors
        const hasCvQuickErrors = @json($errors->has('cv_quick_self_description') || $errors->has('cv_quick_education_json') || $errors->has('cv_quick_work_experiences_json') || $errors->has('cv_quick_skills_json'));
        if (hasCvQuickErrors) {
            openProfileCvDialog();
        }
    </script>
</x-layouts.app>
