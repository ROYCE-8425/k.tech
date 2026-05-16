<x-layouts.app title="Đăng việc làm mới — Smart CV Matcher">
    <div class="max-w-4xl mx-auto space-y-8">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center text-gray-500 hover:text-indigo-600 group transition-colors">
            <div class="w-10 h-10 rounded-xl bg-gray-100 group-hover:bg-indigo-100 flex items-center justify-center mr-3 transition-colors">
                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </div>
            <span class="font-medium">Quay lại Dashboard</span>
        </a>

        <div class="text-center">
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-white mx-auto mb-4 shadow-xl">
                <span class="text-3xl">🤖</span>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">Đăng việc làm mới</h1>
            <p class="text-gray-500 mt-2 max-w-lg mx-auto">Điền chi tiết để AI phân tích và so khớp CV ứng viên chính xác hơn.</p>
        </div>

        {{-- Demo guidance --}}
        @if(config('app.demo_mode'))
            <div class="flex items-start gap-3 p-4 rounded-2xl bg-indigo-50 border border-indigo-200 mb-2 animate-fade-in">
                <span class="text-lg mt-0.5">💡</span>
                <div class="text-sm text-indigo-700">
                    <span class="font-semibold">Demo tip:</span>
                    Điền tiêu đề + chọn vài kỹ năng bắt buộc → nhấn <span class="font-semibold">🔍 Kiểm tra chất lượng JD</span> (ở dưới) để hệ thống phân tích chất lượng mô tả công việc (không cần AI service).
                    Sau khi đăng, quay lại Dashboard để thấy job mới.
                </div>
            </div>
        @endif

        <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-violet-600 to-purple-600 px-8 py-5">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    🤖 Tạo Job Description cho AI Matching
                </h2>
                <p class="text-violet-200 text-sm mt-1">Thông tin càng đầy đủ → AI so khớp CV càng chính xác.</p>
            </div>

            <form method="POST" action="{{ route('admin.jobs.store') }}" class="p-8 space-y-8">
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

                {{-- SECTION 1: Company --}}
                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">1. Công ty</h3>
                    <p class="text-sm text-gray-500 mb-4">Chọn công ty đăng tuyển.</p>

                    {{-- Legacy rubric selector — hidden --}}
                    <input type="hidden" name="cv_scoring_profile_id" value="">

                    @if(($companies ?? collect())->count() > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($companies as $company)
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="company_id" value="{{ $company->id }}" class="peer sr-only" {{ old('company_id') == $company->id ? 'checked' : ($loop->first ? 'checked' : '') }}>
                                    <div class="p-4 rounded-2xl border-2 border-gray-200 peer-checked:border-violet-500 peer-checked:bg-violet-50 hover:border-violet-300 transition-all duration-200">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-12 h-12 rounded-xl bg-white border border-gray-200 overflow-hidden flex items-center justify-center">
                                                @if($company->logo_path)
                                                    <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" class="w-full h-full object-cover">
                                                @else
                                                    <span class="text-lg font-bold gradient-text">{{ strtoupper(substr($company->name, 0, 1)) }}</span>
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-semibold text-gray-900 truncate">{{ $company->name }}</p>
                                                <p class="text-sm text-gray-500 truncate">{{ $company->address ?? 'Việt Nam' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="p-6 rounded-2xl bg-yellow-50 border border-yellow-200">
                            <p class="text-yellow-800 font-medium mb-3">⚠️ Chưa có công ty nào.</p>
                            <a href="{{ route('admin.companies.create') }}" class="inline-flex items-center px-5 py-3 rounded-2xl bg-gradient-to-r from-violet-600 to-purple-600 text-white font-bold shadow-xl hover:shadow-2xl transition-all">+ Tạo công ty</a>
                        </div>
                    @endif
                    @error('company_id')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- SECTION 2: Job Details --}}
                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">2. Chi tiết công việc</h3>
                    <p class="text-sm text-gray-500 mb-6">Mô tả rõ ràng giúp AI phân tích chính xác hơn.</p>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tiêu đề công việc <span class="text-red-500">*</span></label>
                            <input type="text" name="title" value="{{ old('title') }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all @error('title') border-red-400 @enderror" placeholder="VD: Senior Laravel Developer" required>
                            @error('title')
                                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Cấp bậc (Seniority)</label>
                                <select name="seniority" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all">
                                    <option value="">Không chỉ định</option>
                                    <option value="intern" {{ old('seniority') === 'intern' ? 'selected' : '' }}>Intern / Thực tập</option>
                                    <option value="fresher" {{ old('seniority') === 'fresher' ? 'selected' : '' }}>Fresher</option>
                                    <option value="junior" {{ old('seniority') === 'junior' ? 'selected' : '' }}>Junior</option>
                                    <option value="mid" {{ old('seniority') === 'mid' ? 'selected' : '' }}>Mid-level</option>
                                    <option value="senior" {{ old('seniority') === 'senior' ? 'selected' : '' }}>Senior</option>
                                    <option value="lead" {{ old('seniority') === 'lead' ? 'selected' : '' }}>Tech Lead</option>
                                    <option value="principal" {{ old('seniority') === 'principal' ? 'selected' : '' }}>Principal / Architect</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Địa điểm</label>
                                <input type="text" name="location" value="{{ old('location') }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all" placeholder="Hà Nội, TP.HCM, Remote...">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Kinh nghiệm tối thiểu (năm)</label>
                                <select name="min_experience_years" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all">
                                    <option value="">Không yêu cầu</option>
                                    <option value="0" {{ old('min_experience_years') === '0' ? 'selected' : '' }}>Fresher (0)</option>
                                    <option value="1" {{ old('min_experience_years') === '1' ? 'selected' : '' }}>1 năm</option>
                                    <option value="2" {{ old('min_experience_years') === '2' ? 'selected' : '' }}>2 năm</option>
                                    <option value="3" {{ old('min_experience_years') === '3' ? 'selected' : '' }}>3 năm</option>
                                    <option value="5" {{ old('min_experience_years') === '5' ? 'selected' : '' }}>5 năm</option>
                                    <option value="7" {{ old('min_experience_years') === '7' ? 'selected' : '' }}>7+ năm</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Kinh nghiệm tối đa (năm)</label>
                                <select name="max_experience_years" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all">
                                    <option value="">Không giới hạn</option>
                                    <option value="1" {{ old('max_experience_years') === '1' ? 'selected' : '' }}>1 năm</option>
                                    <option value="2" {{ old('max_experience_years') === '2' ? 'selected' : '' }}>2 năm</option>
                                    <option value="3" {{ old('max_experience_years') === '3' ? 'selected' : '' }}>3 năm</option>
                                    <option value="5" {{ old('max_experience_years') === '5' ? 'selected' : '' }}>5 năm</option>
                                    <option value="7" {{ old('max_experience_years') === '7' ? 'selected' : '' }}>7 năm</option>
                                    <option value="10" {{ old('max_experience_years') === '10' ? 'selected' : '' }}>10+ năm</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Mô tả công việc</label>
                            <textarea name="description" rows="6" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all" placeholder="Mô tả chi tiết về công việc, vai trò, trách nhiệm...">{{ old('description') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Yêu cầu ứng viên (mô tả thêm)</label>
                            <textarea name="requirements" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all" placeholder="Các yêu cầu khác ngoài kỹ năng đã chọn...">{{ old('requirements') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- SECTION 3: Required Skills --}}
                <div class="rounded-2xl border border-violet-100 bg-violet-50/30 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">
                        3. Kỹ năng bắt buộc
                        <span id="selectedSkillsCount" class="text-violet-600 font-normal text-base"></span>
                    </h3>
                    <p class="text-sm text-gray-500 mb-6">AI sẽ dùng danh sách này để tính điểm <strong>Required Skill Coverage (40%)</strong>.</p>

                    @php
                        $skillGroups = [
                            ['label' => 'Backend & Server', 'icon' => '⚙️', 'color' => 'indigo', 'skills' => ['PHP', 'Laravel', 'Node.js', 'Python', 'Django', 'Java', 'Spring Boot', '.NET', 'C#', 'Go', 'Ruby', 'Rails']],
                            ['label' => 'Frontend & UI', 'icon' => '🎨', 'color' => 'emerald', 'skills' => ['JavaScript', 'TypeScript', 'React', 'Vue.js', 'Angular', 'HTML/CSS', 'Tailwind CSS', 'Bootstrap', 'jQuery', 'Next.js', 'Nuxt.js', 'Svelte']],
                            ['label' => 'Database & Storage', 'icon' => '🗄️', 'color' => 'amber', 'skills' => ['MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'Elasticsearch', 'SQLite', 'SQL Server', 'Oracle', 'Firebase', 'DynamoDB']],
                            ['label' => 'DevOps & Cloud', 'icon' => '☁️', 'color' => 'purple', 'skills' => ['Docker', 'Kubernetes', 'AWS', 'Azure', 'GCP', 'CI/CD', 'Jenkins', 'Git', 'Linux', 'Nginx', 'Terraform']],
                            ['label' => 'Mobile', 'icon' => '📱', 'color' => 'pink', 'skills' => ['React Native', 'Flutter', 'Swift', 'Kotlin', 'iOS', 'Android']],
                            ['label' => 'Khác', 'icon' => '🔧', 'color' => 'gray', 'skills' => ['REST API', 'GraphQL', 'Microservices', 'Agile/Scrum', 'Unit Testing', 'TDD', 'Design Patterns', 'OOP', 'AI/ML', 'Data Analysis']],
                        ];
                    @endphp

                    @foreach($skillGroups as $group)
                        <div class="mb-5">
                            <h4 class="font-semibold text-gray-700 mb-2 flex items-center gap-2 text-sm">
                                <span>{{ $group['icon'] }}</span> {{ $group['label'] }}
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                                @foreach($group['skills'] as $skill)
                                    <label class="flex items-center gap-2 p-2.5 rounded-xl border-2 border-gray-200 hover:border-violet-300 cursor-pointer transition-all has-[:checked]:border-violet-500 has-[:checked]:bg-violet-50 text-sm">
                                        <input type="checkbox" name="required_skills[]" value="{{ $skill }}" class="w-4 h-4 text-violet-600 rounded focus:ring-violet-500" {{ in_array($skill, old('required_skills', [])) ? 'checked' : '' }}>
                                        <span class="font-medium text-gray-700">{{ $skill }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- SECTION 4: Preferred Skills --}}
                <div class="rounded-2xl border border-amber-100 bg-amber-50/30 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">
                        4. Kỹ năng ưu tiên (nice-to-have)
                        <span id="selectedPreferredCount" class="text-amber-600 font-normal text-base"></span>
                    </h3>
                    <p class="text-sm text-gray-500 mb-6">AI sẽ dùng danh sách này để tính điểm <strong>Preferred Skill Coverage (15%)</strong>. Không bắt buộc nhưng giúp xếp hạng chính xác hơn.</p>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                        @php
                            $preferredOptions = ['Communication', 'Leadership', 'Problem Solving', 'Teamwork', 'Time Management',
                                'Machine Learning', 'Deep Learning', 'NLP', 'Computer Vision', 'Data Science',
                                'Blockchain', 'IoT', 'AR/VR', 'Game Dev', 'Security',
                                'AWS Certified', 'GCP Certified', 'Azure Certified', 'PMP', 'Scrum Master'];
                        @endphp
                        @foreach($preferredOptions as $skill)
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border-2 border-gray-200 hover:border-amber-300 cursor-pointer transition-all has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50 text-sm">
                                <input type="checkbox" name="preferred_skills[]" value="{{ $skill }}" class="w-4 h-4 text-amber-600 rounded focus:ring-amber-500" {{ in_array($skill, old('preferred_skills', [])) ? 'checked' : '' }}>
                                <span class="font-medium text-gray-700">{{ $skill }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- SECTION 5: AI Recruiter Notes --}}
                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">5. Ghi chú cho AI</h3>
                    <p class="text-sm text-gray-500 mb-4">Thông tin bổ sung giúp AI hiểu ngữ cảnh tuyển dụng tốt hơn. Ví dụ: "Ưu tiên ứng viên biết tiếng Hàn", "Team size nhỏ, cần người tự chủ".</p>

                    <textarea name="ai_recruiter_notes" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all" placeholder="Ghi chú thêm cho AI (tuỳ chọn)...">{{ old('ai_recruiter_notes') }}</textarea>
                </div>

                {{-- SECTION: AI Quality Check (inline) --}}
                <div id="jdQualitySection" class="rounded-2xl border-2 border-dashed border-indigo-200 bg-indigo-50/20 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                🔍 Kiểm tra chất lượng JD
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">Hệ thống sẽ phân tích và gợi ý cải thiện trước khi đăng.</p>
                        </div>
                        <button type="button" id="btnCheckQuality"
                            class="inline-flex items-center px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-semibold text-sm hover:bg-indigo-700 transition-all shadow-md hover:shadow-lg">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                            Kiểm tra ngay
                        </button>
                    </div>

                    {{-- Error container --}}
                    <div id="jdQualityError" class="hidden p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm"></div>

                    {{-- Results container (hidden initially) --}}
                    <div id="jdQualityResult" class="hidden space-y-4">
                        {{-- Score badge --}}
                        <div class="flex items-center gap-4">
                            <div id="qualityScoreBadge" class="w-16 h-16 rounded-2xl flex items-center justify-center text-white font-bold text-xl shadow-lg"></div>
                            <div>
                                <p id="qualityLabel" class="font-bold text-lg"></p>
                                <p id="qualitySummary" class="text-sm text-gray-500"></p>
                            </div>
                        </div>

                        {{-- Issues list --}}
                        <div id="qualityIssues" class="space-y-2"></div>

                        {{-- Suggestions --}}
                        <div id="qualitySuggestions" class="space-y-2"></div>

                        {{-- Suggested skills --}}
                        <div id="qualitySuggestedSkills" class="hidden">
                            <p class="text-sm font-semibold text-gray-700 mb-2">💡 Kỹ năng gợi ý thêm:</p>
                            <div id="suggestedSkillsContent" class="flex flex-wrap gap-2"></div>
                        </div>

                        {{-- Suggested seniority / experience --}}
                        <div id="qualityInferred" class="hidden">
                            <p id="inferredContent" class="text-sm text-indigo-700 bg-indigo-50 rounded-xl px-4 py-2"></p>
                        </div>
                    </div>

                    {{-- Loading state --}}
                    <div id="jdQualityLoading" class="hidden flex items-center gap-3 py-4">
                        <div class="w-5 h-5 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                        <span class="text-sm text-gray-500">Đang phân tích chất lượng JD...</span>
                    </div>
                </div>

                {{-- SECTION 6: Salary & Status --}}
                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">6. Lương &amp; trạng thái</h3>
                    <p class="text-sm text-gray-500 mb-6">Thông tin hiển thị cho ứng viên.</p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Lương tối thiểu (VNĐ)</label>
                            <input type="number" step="100000" name="salary_min" value="{{ old('salary_min') }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all" placeholder="10,000,000">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Lương tối đa (VNĐ)</label>
                            <input type="number" step="100000" name="salary_max" value="{{ old('salary_max') }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all" placeholder="25,000,000">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tiền tệ</label>
                            <input type="text" name="currency" value="{{ old('currency', 'VND') }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Trạng thái</label>
                        <select name="status" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all">
                            <option value="published" {{ old('status', 'published') === 'published' ? 'selected' : '' }}>🟢 Đăng tuyển ngay</option>
                            <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>📝 Lưu nháp</option>
                            <option value="closed" {{ old('status') === 'closed' ? 'selected' : '' }}>🔴 Đã đóng</option>
                        </select>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex items-center justify-between gap-4 pt-2">
                    <p class="text-gray-400 text-sm hidden sm:block">
                        Tin tuyển dụng sẽ hiển thị ngay sau khi đăng.
                    </p>
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 rounded-2xl bg-gradient-to-r from-violet-600 to-purple-600 text-white font-bold text-lg shadow-xl hover:shadow-2xl hover:shadow-violet-500/30 hover:scale-[1.02] transition-all duration-300">
                        🤖 Đăng tuyển ngay
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function setupCounter(inputName, counterId) {
                const checkboxes = document.querySelectorAll('input[name="' + inputName + '"]');
                const countEl = document.getElementById(counterId);
                function update() {
                    const n = document.querySelectorAll('input[name="' + inputName + '"]:checked').length;
                    if (countEl) countEl.textContent = n > 0 ? '(' + n + ' đã chọn)' : '';
                }
                checkboxes.forEach(cb => cb.addEventListener('change', update));
                update();
            }
            setupCounter('required_skills[]', 'selectedSkillsCount');
            setupCounter('preferred_skills[]', 'selectedPreferredCount');

            // ── JD Quality Checker ──────────────────────────────────
            const btnCheck = document.getElementById('btnCheckQuality');
            const resultEl = document.getElementById('jdQualityResult');
            const loadingEl = document.getElementById('jdQualityLoading');
            const errorEl = document.getElementById('jdQualityError');

            btnCheck.addEventListener('click', async function() {
                // Collect form data
                const formData = new FormData();
                formData.append('title', document.querySelector('input[name="title"]')?.value || '');
                formData.append('description', document.querySelector('textarea[name="description"]')?.value || '');
                formData.append('requirements', document.querySelector('textarea[name="requirements"]')?.value || '');
                formData.append('seniority', document.querySelector('select[name="seniority"]')?.value || '');
                formData.append('min_experience_years', document.querySelector('select[name="min_experience_years"]')?.value || '');
                formData.append('max_experience_years', document.querySelector('select[name="max_experience_years"]')?.value || '');

                document.querySelectorAll('input[name="required_skills[]"]:checked').forEach(cb => {
                    formData.append('required_skills[]', cb.value);
                });
                document.querySelectorAll('input[name="preferred_skills[]"]:checked').forEach(cb => {
                    formData.append('preferred_skills[]', cb.value);
                });

                // Show loading
                resultEl.classList.add('hidden');
                errorEl.classList.add('hidden');
                loadingEl.classList.remove('hidden');
                btnCheck.disabled = true;
                btnCheck.classList.add('opacity-50');

                try {
                    const resp = await fetch('{{ route("admin.jobs.check-quality") }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: formData,
                    });
                    if (!resp.ok) throw new Error('HTTP ' + resp.status);
                    const data = await resp.json();
                    errorEl.classList.add('hidden');
                    renderQualityResult(data);
                } catch (err) {
                    console.error('JD quality check failed:', err);
                    errorEl.textContent = '⚠️ Không thể kiểm tra chất lượng JD lúc này. Vui lòng thử lại sau.';
                    errorEl.classList.remove('hidden');
                    resultEl.classList.add('hidden');
                } finally {
                    loadingEl.classList.add('hidden');
                    btnCheck.disabled = false;
                    btnCheck.classList.remove('opacity-50');
                }
            });

            function renderQualityResult(data) {
                // Score badge
                const badge = document.getElementById('qualityScoreBadge');
                badge.textContent = data.quality_score;
                const colors = {
                    excellent: 'bg-emerald-500', good: 'bg-blue-500',
                    needs_improvement: 'bg-amber-500', poor: 'bg-red-500'
                };
                badge.className = 'w-16 h-16 rounded-2xl flex items-center justify-center text-white font-bold text-xl shadow-lg ' + (colors[data.quality_label] || 'bg-gray-500');

                // Label
                const labels = {
                    excellent: '✅ Tuyệt vời', good: '👍 Tốt',
                    needs_improvement: '⚠️ Cần cải thiện', poor: '❌ Chưa đủ thông tin'
                };
                document.getElementById('qualityLabel').textContent = labels[data.quality_label] || data.quality_label;
                document.getElementById('qualitySummary').textContent = data.quality_score + '/100 điểm chất lượng JD';

                // Issues
                const issuesEl = document.getElementById('qualityIssues');
                issuesEl.innerHTML = '';
                (data.issues || []).forEach(issue => {
                    const colors = { error: 'bg-red-50 border-red-200 text-red-800', warning: 'bg-amber-50 border-amber-200 text-amber-800', info: 'bg-blue-50 border-blue-200 text-blue-700' };
                    const icons = { error: '❌', warning: '⚠️', info: 'ℹ️' };
                    const div = document.createElement('div');
                    div.className = 'p-3 rounded-xl border text-sm ' + (colors[issue.severity] || colors.info);
                    div.innerHTML = '<span class="mr-1">' + (icons[issue.severity] || 'ℹ️') + '</span> ' + issue.message;
                    issuesEl.appendChild(div);
                });

                // Suggestions
                const sugEl = document.getElementById('qualitySuggestions');
                sugEl.innerHTML = '';
                (data.suggestions || []).forEach(sug => {
                    const div = document.createElement('div');
                    div.className = 'p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm';
                    div.innerHTML = '💡 ' + sug;
                    sugEl.appendChild(div);
                });

                // Suggested skills
                const sugSkillsEl = document.getElementById('qualitySuggestedSkills');
                const sugSkillsContent = document.getElementById('suggestedSkillsContent');
                if (data.suggested_skills && (data.suggested_skills.required?.length || data.suggested_skills.preferred?.length)) {
                    sugSkillsEl.classList.remove('hidden');
                    sugSkillsContent.innerHTML = '';
                    (data.suggested_skills.required || []).forEach(s => {
                        sugSkillsContent.innerHTML += '<span class="px-3 py-1 bg-violet-100 text-violet-700 rounded-full text-xs font-semibold">' + s + ' (bắt buộc)</span>';
                    });
                    (data.suggested_skills.preferred || []).forEach(s => {
                        sugSkillsContent.innerHTML += '<span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-semibold">' + s + ' (ưu tiên)</span>';
                    });
                } else {
                    sugSkillsEl.classList.add('hidden');
                }

                // Inferred seniority / experience
                const inferEl = document.getElementById('qualityInferred');
                const inferContent = document.getElementById('inferredContent');
                let inferParts = [];
                if (data.suggested_seniority) {
                    inferParts.push('Cấp bậc gợi ý: <strong>' + data.suggested_seniority + '</strong>');
                }
                if (data.suggested_experience) {
                    inferParts.push('Kinh nghiệm gợi ý: <strong>' + data.suggested_experience.min + '-' + data.suggested_experience.max + ' năm</strong>');
                }
                if (inferParts.length) {
                    inferEl.classList.remove('hidden');
                    inferContent.innerHTML = '🎯 ' + inferParts.join(' · ');
                } else {
                    inferEl.classList.add('hidden');
                }

                resultEl.classList.remove('hidden');
            }
        });
    </script>
</x-layouts.app>
