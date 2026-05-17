<?php if (isset($component)) { $__componentOriginal5863877a5171c196453bfa0bd807e410 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5863877a5171c196453bfa0bd807e410 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.app','data' => ['title' => 'Đăng việc làm mới — Smart CV Matcher']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.app'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Đăng việc làm mới — Smart CV Matcher']); ?>
    <div class="max-w-4xl mx-auto space-y-8">
        <a href="<?php echo e(route('admin.dashboard')); ?>" class="inline-flex items-center text-gray-500 hover:text-indigo-600 group transition-colors">
            <div class="w-10 h-10 rounded-xl bg-gray-100 group-hover:bg-indigo-100 flex items-center justify-center mr-3 transition-colors">
                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </div>
            <span class="font-medium">Quay lại Dashboard</span>
        </a>

        <div class="text-center">
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-white mx-auto mb-4 shadow-xl" style="background: linear-gradient(to bottom right, #8b5cf6, #9333ea);">
                <span class="text-3xl">🤖</span>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">Đăng việc làm mới</h1>
            <p class="text-gray-500 mt-2 max-w-lg mx-auto">Điền chi tiết để AI phân tích và so khớp CV ứng viên chính xác hơn.</p>
        </div>

        
        <?php if(config('app.demo_mode')): ?>
            <div class="flex items-start gap-3 p-4 rounded-2xl bg-indigo-50 border border-indigo-200 mb-2 animate-fade-in">
                <span class="text-lg mt-0.5">💡</span>
                <div class="text-sm text-indigo-700">
                    <span class="font-semibold">Demo tip:</span>
                    Điền tiêu đề + chọn vài kỹ năng bắt buộc → nhấn <span class="font-semibold">🔍 Kiểm tra chất lượng JD</span> (ở dưới) để hệ thống phân tích chất lượng mô tả công việc (không cần AI service).
                    Sau khi đăng, quay lại Dashboard để thấy job mới.
                </div>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-violet-600 to-purple-600 px-8 py-5" style="background: linear-gradient(to right, #7c3aed, #9333ea);">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    🤖 Tạo Job Description cho AI Matching
                </h2>
                <p class="text-violet-200 text-sm mt-1">Thông tin càng đầy đủ → AI so khớp CV càng chính xác.</p>
            </div>

            <form method="POST" action="<?php echo e(route('admin.jobs.store')); ?>" class="p-8 space-y-8">
                <?php echo csrf_field(); ?>

                <?php if(session('status')): ?>
                    <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3">
                        <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <p class="text-emerald-700 font-medium"><?php echo e(session('status')); ?></p>
                    </div>
                <?php endif; ?>

                <?php if($errors->any()): ?>
                    <div class="p-4 bg-red-50 border border-red-200 rounded-xl">
                        <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                <?php endif; ?>

                
                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">1. Công ty</h3>
                    <p class="text-sm text-gray-500 mb-4">Chọn công ty đăng tuyển.</p>

                    
                    <input type="hidden" name="cv_scoring_profile_id" value="">

                    <?php if(($companies ?? collect())->count() > 0): ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $company): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="company_id" value="<?php echo e($company->id); ?>" class="peer sr-only" <?php echo e(old('company_id') == $company->id ? 'checked' : ($loop->first ? 'checked' : '')); ?>>
                                    <div class="p-4 rounded-2xl border-2 border-gray-200 peer-checked:border-violet-500 peer-checked:bg-violet-50 hover:border-violet-300 transition-all duration-200">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-12 h-12 rounded-xl bg-white border border-gray-200 overflow-hidden flex items-center justify-center">
                                                <?php if($company->logo_path): ?>
                                                    <img src="<?php echo e(asset('storage/' . $company->logo_path)); ?>" alt="<?php echo e($company->name); ?>" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <span class="text-lg font-bold gradient-text"><?php echo e(strtoupper(substr($company->name, 0, 1))); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-semibold text-gray-900 truncate"><?php echo e($company->name); ?></p>
                                                <p class="text-sm text-gray-500 truncate"><?php echo e($company->address ?? 'Việt Nam'); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php else: ?>
                        <div class="p-6 rounded-2xl bg-yellow-50 border border-yellow-200">
                            <p class="text-yellow-800 font-medium mb-3">⚠️ Chưa có công ty nào.</p>
                            <a href="<?php echo e(route('admin.companies.create')); ?>" class="inline-flex items-center px-5 py-3 rounded-2xl bg-gradient-to-r from-violet-600 to-purple-600 text-white font-bold shadow-xl hover:shadow-2xl transition-all" style="background: linear-gradient(to right, #7c3aed, #9333ea);">+ Tạo công ty</a>
                        </div>
                    <?php endif; ?>
                    <?php $__errorArgs = ['company_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="mt-2 text-sm text-red-500"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                
                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">2. Chi tiết công việc</h3>
                    <p class="text-sm text-gray-500 mb-6">Mô tả rõ ràng giúp AI phân tích chính xác hơn.</p>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tiêu đề công việc <span class="text-red-500">*</span></label>
                            <input type="text" name="title" value="<?php echo e(old('title')); ?>" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-400 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" placeholder="VD: Senior Laravel Developer" required>
                            <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="mt-2 text-sm text-red-500"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Cấp bậc (Seniority)</label>
                                <select name="seniority" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all">
                                    <option value="">Không chỉ định</option>
                                    <option value="intern" <?php echo e(old('seniority') === 'intern' ? 'selected' : ''); ?>>Intern / Thực tập</option>
                                    <option value="fresher" <?php echo e(old('seniority') === 'fresher' ? 'selected' : ''); ?>>Fresher</option>
                                    <option value="junior" <?php echo e(old('seniority') === 'junior' ? 'selected' : ''); ?>>Junior</option>
                                    <option value="mid" <?php echo e(old('seniority') === 'mid' ? 'selected' : ''); ?>>Mid-level</option>
                                    <option value="senior" <?php echo e(old('seniority') === 'senior' ? 'selected' : ''); ?>>Senior</option>
                                    <option value="lead" <?php echo e(old('seniority') === 'lead' ? 'selected' : ''); ?>>Tech Lead</option>
                                    <option value="principal" <?php echo e(old('seniority') === 'principal' ? 'selected' : ''); ?>>Principal / Architect</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Địa điểm</label>
                                <input type="text" name="location" value="<?php echo e(old('location')); ?>" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all" placeholder="Hà Nội, TP.HCM, Remote...">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Kinh nghiệm tối thiểu (năm)</label>
                                <select name="min_experience_years" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all">
                                    <option value="">Không yêu cầu</option>
                                    <option value="0" <?php echo e(old('min_experience_years') === '0' ? 'selected' : ''); ?>>Fresher (0)</option>
                                    <option value="1" <?php echo e(old('min_experience_years') === '1' ? 'selected' : ''); ?>>1 năm</option>
                                    <option value="2" <?php echo e(old('min_experience_years') === '2' ? 'selected' : ''); ?>>2 năm</option>
                                    <option value="3" <?php echo e(old('min_experience_years') === '3' ? 'selected' : ''); ?>>3 năm</option>
                                    <option value="5" <?php echo e(old('min_experience_years') === '5' ? 'selected' : ''); ?>>5 năm</option>
                                    <option value="7" <?php echo e(old('min_experience_years') === '7' ? 'selected' : ''); ?>>7+ năm</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Kinh nghiệm tối đa (năm)</label>
                                <select name="max_experience_years" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all">
                                    <option value="">Không giới hạn</option>
                                    <option value="1" <?php echo e(old('max_experience_years') === '1' ? 'selected' : ''); ?>>1 năm</option>
                                    <option value="2" <?php echo e(old('max_experience_years') === '2' ? 'selected' : ''); ?>>2 năm</option>
                                    <option value="3" <?php echo e(old('max_experience_years') === '3' ? 'selected' : ''); ?>>3 năm</option>
                                    <option value="5" <?php echo e(old('max_experience_years') === '5' ? 'selected' : ''); ?>>5 năm</option>
                                    <option value="7" <?php echo e(old('max_experience_years') === '7' ? 'selected' : ''); ?>>7 năm</option>
                                    <option value="10" <?php echo e(old('max_experience_years') === '10' ? 'selected' : ''); ?>>10+ năm</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Mô tả công việc</label>
                            <textarea name="description" rows="6" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all" placeholder="Mô tả chi tiết về công việc, vai trò, trách nhiệm..."><?php echo e(old('description')); ?></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Yêu cầu ứng viên (mô tả thêm)</label>
                            <textarea name="requirements" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all" placeholder="Các yêu cầu khác ngoài kỹ năng đã chọn..."><?php echo e(old('requirements')); ?></textarea>
                        </div>
                    </div>
                </div>

                
                <div class="rounded-2xl border border-violet-100 bg-violet-50/30 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">
                        3. Kỹ năng bắt buộc
                        <span id="selectedSkillsCount" class="text-violet-600 font-normal text-base"></span>
                    </h3>
                    <p class="text-sm text-gray-500 mb-6">AI sẽ dùng danh sách này để tính điểm <strong>Required Skill Coverage (40%)</strong>.</p>

                    <?php
                        $skillGroups = [
                            ['label' => 'Backend & Server', 'icon' => '⚙️', 'color' => 'indigo', 'skills' => ['PHP', 'Laravel', 'Node.js', 'Python', 'Django', 'Java', 'Spring Boot', '.NET', 'C#', 'Go', 'Ruby', 'Rails']],
                            ['label' => 'Frontend & UI', 'icon' => '🎨', 'color' => 'emerald', 'skills' => ['JavaScript', 'TypeScript', 'React', 'Vue.js', 'Angular', 'HTML/CSS', 'Tailwind CSS', 'Bootstrap', 'jQuery', 'Next.js', 'Nuxt.js', 'Svelte']],
                            ['label' => 'Database & Storage', 'icon' => '🗄️', 'color' => 'amber', 'skills' => ['MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'Elasticsearch', 'SQLite', 'SQL Server', 'Oracle', 'Firebase', 'DynamoDB']],
                            ['label' => 'DevOps & Cloud', 'icon' => '☁️', 'color' => 'purple', 'skills' => ['Docker', 'Kubernetes', 'AWS', 'Azure', 'GCP', 'CI/CD', 'Jenkins', 'Git', 'Linux', 'Nginx', 'Terraform']],
                            ['label' => 'Mobile', 'icon' => '📱', 'color' => 'pink', 'skills' => ['React Native', 'Flutter', 'Swift', 'Kotlin', 'iOS', 'Android']],
                            ['label' => 'Khác', 'icon' => '🔧', 'color' => 'gray', 'skills' => ['REST API', 'GraphQL', 'Microservices', 'Agile/Scrum', 'Unit Testing', 'TDD', 'Design Patterns', 'OOP', 'AI/ML', 'Data Analysis']],
                        ];
                    ?>

                    <?php $__currentLoopData = $skillGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="mb-5">
                            <h4 class="font-semibold text-gray-700 mb-2 flex items-center gap-2 text-sm">
                                <span><?php echo e($group['icon']); ?></span> <?php echo e($group['label']); ?>

                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                                <?php $__currentLoopData = $group['skills']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $skill): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <label class="flex items-center gap-2 p-2.5 rounded-xl border-2 border-gray-200 hover:border-violet-300 cursor-pointer transition-all has-[:checked]:border-violet-500 has-[:checked]:bg-violet-50 text-sm">
                                        <input type="checkbox" name="required_skills[]" value="<?php echo e($skill); ?>" class="w-4 h-4 text-violet-600 rounded focus:ring-violet-500" <?php echo e(in_array($skill, old('required_skills', [])) ? 'checked' : ''); ?>>
                                        <span class="font-medium text-gray-700"><?php echo e($skill); ?></span>
                                    </label>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                
                <div class="rounded-2xl border border-amber-100 bg-amber-50/30 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">
                        4. Kỹ năng ưu tiên (nice-to-have)
                        <span id="selectedPreferredCount" class="text-amber-600 font-normal text-base"></span>
                    </h3>
                    <p class="text-sm text-gray-500 mb-6">AI sẽ dùng danh sách này để tính điểm <strong>Preferred Skill Coverage (15%)</strong>. Không bắt buộc nhưng giúp xếp hạng chính xác hơn.</p>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                        <?php
                            $preferredOptions = ['Communication', 'Leadership', 'Problem Solving', 'Teamwork', 'Time Management',
                                'Machine Learning', 'Deep Learning', 'NLP', 'Computer Vision', 'Data Science',
                                'Blockchain', 'IoT', 'AR/VR', 'Game Dev', 'Security',
                                'AWS Certified', 'GCP Certified', 'Azure Certified', 'PMP', 'Scrum Master'];
                        ?>
                        <?php $__currentLoopData = $preferredOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $skill): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border-2 border-gray-200 hover:border-amber-300 cursor-pointer transition-all has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50 text-sm">
                                <input type="checkbox" name="preferred_skills[]" value="<?php echo e($skill); ?>" class="w-4 h-4 text-amber-600 rounded focus:ring-amber-500" <?php echo e(in_array($skill, old('preferred_skills', [])) ? 'checked' : ''); ?>>
                                <span class="font-medium text-gray-700"><?php echo e($skill); ?></span>
                            </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>

                
                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">5. Ghi chú cho AI</h3>
                    <p class="text-sm text-gray-500 mb-4">Thông tin bổ sung giúp AI hiểu ngữ cảnh tuyển dụng tốt hơn. Ví dụ: "Ưu tiên ứng viên biết tiếng Hàn", "Team size nhỏ, cần người tự chủ".</p>

                    <textarea name="ai_recruiter_notes" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all" placeholder="Ghi chú thêm cho AI (tuỳ chọn)..."><?php echo e(old('ai_recruiter_notes')); ?></textarea>
                </div>

                
                <div id="jdQualitySection" class="rounded-2xl border-2 border-dashed border-indigo-200 bg-indigo-50/20 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                🔍 Kiểm tra chất lượng JD
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">AI sẽ phân tích mức độ đầy đủ của JD trước khi đăng — giúp tối ưu chất lượng so khớp CV.</p>
                        </div>
                        <button type="button" id="btnCheckQuality"
                            class="inline-flex items-center px-5 py-2.5 rounded-xl text-white font-semibold text-sm transition-all shadow-md hover:shadow-lg hover:scale-105" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                            🤖 Phân tích AI
                        </button>
                    </div>

                    
                    <div id="jdQualityError" class="hidden p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm"></div>

                    
                    <div id="jdQualityResult" class="hidden"></div>

                    
                    <div id="jdQualityLoading" class="hidden py-8">
                        <div class="flex flex-col items-center gap-4">
                            <div class="relative w-16 h-16">
                                <div class="absolute inset-0 rounded-full border-4 border-indigo-100"></div>
                                <div class="absolute inset-0 rounded-full border-4 border-indigo-500 border-t-transparent animate-spin"></div>
                                <div class="absolute inset-2 rounded-full flex items-center justify-center" style="background: linear-gradient(135deg, #eef2ff, #e0e7ff);">
                                    <span class="text-lg">🤖</span>
                                </div>
                            </div>
                            <div class="text-center">
                                <p class="text-sm font-semibold text-indigo-700">Đang phân tích chất lượng JD...</p>
                                <p class="text-xs text-gray-400 mt-1">Kiểm tra cấu trúc, kỹ năng, và mức độ đầy đủ</p>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">6. Lương &amp; trạng thái</h3>
                    <p class="text-sm text-gray-500 mb-6">Thông tin hiển thị cho ứng viên.</p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Lương tối thiểu (VNĐ)</label>
                            <input type="number" step="100000" name="salary_min" value="<?php echo e(old('salary_min')); ?>" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all" placeholder="10,000,000">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Lương tối đa (VNĐ)</label>
                            <input type="number" step="100000" name="salary_max" value="<?php echo e(old('salary_max')); ?>" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all" placeholder="25,000,000">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tiền tệ</label>
                            <input type="text" name="currency" value="<?php echo e(old('currency', 'VND')); ?>" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Trạng thái</label>
                        <select name="status" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-violet-500 focus:outline-none transition-all">
                            <option value="published" <?php echo e(old('status', 'published') === 'published' ? 'selected' : ''); ?>>🟢 Đăng tuyển ngay</option>
                            <option value="draft" <?php echo e(old('status') === 'draft' ? 'selected' : ''); ?>>📝 Lưu nháp</option>
                            <option value="closed" <?php echo e(old('status') === 'closed' ? 'selected' : ''); ?>>🔴 Đã đóng</option>
                        </select>
                    </div>
                </div>

                
                <div class="flex items-center justify-between gap-4 pt-2">
                    <p class="text-gray-400 text-sm hidden sm:block">
                        Tin tuyển dụng sẽ hiển thị ngay sau khi đăng.
                    </p>
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 rounded-2xl bg-gradient-to-r from-violet-600 to-purple-600 text-white font-bold text-lg shadow-xl hover:shadow-2xl hover:shadow-violet-500/30 hover:scale-[1.02] transition-all duration-300" style="background: linear-gradient(to right, #7c3aed, #9333ea);">
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
                    const resp = await fetch('<?php echo e(route("admin.jobs.check-quality")); ?>', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>', 'Accept': 'application/json' },
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
                const resultEl = document.getElementById('jdQualityResult');
                const score = data.quality_score;
                const label = data.quality_label;

                // Color schemes
                const colorMap = {
                    excellent: { bg: '#10b981', light: '#d1fae5', text: '#065f46', grad: 'linear-gradient(135deg, #10b981, #059669)' },
                    good:      { bg: '#3b82f6', light: '#dbeafe', text: '#1e40af', grad: 'linear-gradient(135deg, #3b82f6, #2563eb)' },
                    needs_improvement: { bg: '#f59e0b', light: '#fef3c7', text: '#92400e', grad: 'linear-gradient(135deg, #f59e0b, #d97706)' },
                    poor:      { bg: '#ef4444', light: '#fee2e2', text: '#991b1b', grad: 'linear-gradient(135deg, #ef4444, #dc2626)' },
                };
                const c = colorMap[label] || colorMap.good;
                const labelText = { excellent: 'Tuyệt vời', good: 'Tốt', needs_improvement: 'Cần cải thiện', poor: 'Chưa đủ' };
                const labelEmoji = { excellent: '🏆', good: '👍', needs_improvement: '⚠️', poor: '❌' };

                // SVG circular gauge params
                const radius = 54, circumference = 2 * Math.PI * radius;
                const offset = circumference - (score / 100) * circumference;

                // Count issues by severity
                const issues = data.issues || [];
                const errorCount = issues.filter(i => i.severity === 'error').length;
                const warnCount = issues.filter(i => i.severity === 'warning').length;
                const infoCount = issues.filter(i => i.severity === 'info').length;

                // AI weight analysis — compute field completeness
                const reqSkillsCount = document.querySelectorAll('input[name="required_skills[]"]:checked').length;
                const prefSkillsCount = document.querySelectorAll('input[name="preferred_skills[]"]:checked').length;
                const hasSeniority = !!document.querySelector('select[name="seniority"]')?.value;
                const hasExperience = !!document.querySelector('select[name="min_experience_years"]')?.value;
                const descLen = (document.querySelector('textarea[name="description"]')?.value || '').length;

                const weights = [
                    { label: 'Kỹ năng bắt buộc', weight: '40%', pct: Math.min(100, reqSkillsCount >= 3 ? 100 : (reqSkillsCount / 3 * 100)), color: '#8b5cf6', icon: '⚙️' },
                    { label: 'Mô tả công việc', weight: '20%', pct: Math.min(100, descLen >= 50 ? 100 : (descLen / 50 * 100)), color: '#6366f1', icon: '📝' },
                    { label: 'Kỹ năng ưu tiên', weight: '15%', pct: prefSkillsCount > 0 ? 100 : 0, color: '#f59e0b', icon: '⭐' },
                    { label: 'Kinh nghiệm', weight: '15%', pct: hasExperience ? 100 : 0, color: '#14b8a6', icon: '📊' },
                    { label: 'Cấp bậc', weight: '10%', pct: hasSeniority ? 100 : 0, color: '#ec4899', icon: '🎯' },
                ];

                let html = `
                <div style="animation: fadeIn 0.5s ease-out;">
                <style>
                    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
                    @keyframes drawCircle { from { stroke-dashoffset: ${circumference}; } to { stroke-dashoffset: ${offset}; } }
                    @keyframes growBar { from { width: 0; } }
                    .quality-bar { animation: growBar 0.8s ease-out forwards; }
                </style>

                <!-- ═══ TOP: Score Gauge + Summary ═══ -->
                <div class="flex flex-col md:flex-row items-center gap-6 p-6 rounded-2xl border" style="background: ${c.light}; border-color: ${c.bg}30;">
                    <!-- Circular Gauge -->
                    <div class="relative flex-shrink-0" style="width: 140px; height: 140px;">
                        <svg viewBox="0 0 120 120" class="w-full h-full" style="transform: rotate(-90deg);">
                            <circle cx="60" cy="60" r="${radius}" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                            <circle cx="60" cy="60" r="${radius}" fill="none" stroke="${c.bg}" stroke-width="8"
                                stroke-linecap="round" stroke-dasharray="${circumference}" stroke-dashoffset="${offset}"
                                style="animation: drawCircle 1.2s ease-out forwards; filter: drop-shadow(0 2px 4px ${c.bg}40);"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-black" style="color: ${c.bg};">${score}</span>
                            <span class="text-xs font-semibold text-gray-500">/100</span>
                        </div>
                    </div>
                    <!-- Summary -->
                    <div class="flex-1 text-center md:text-left">
                        <div class="flex items-center justify-center md:justify-start gap-2 mb-2">
                            <span class="text-2xl">${labelEmoji[label] || '📋'}</span>
                            <h4 class="text-xl font-bold" style="color: ${c.text};">${labelText[label] || label}</h4>
                        </div>
                        <p class="text-sm text-gray-600 mb-3">Điểm chất lượng JD: <strong>${score}/100</strong>. AI sẽ sử dụng thông tin này để so khớp CV ứng viên.</p>
                        <div class="flex flex-wrap gap-2">
                            ${errorCount > 0 ? `<span class="px-3 py-1 rounded-full text-xs font-bold" style="background: #fee2e2; color: #991b1b;">❌ ${errorCount} lỗi</span>` : ''}
                            ${warnCount > 0 ? `<span class="px-3 py-1 rounded-full text-xs font-bold" style="background: #fef3c7; color: #92400e;">⚠️ ${warnCount} cảnh báo</span>` : ''}
                            ${infoCount > 0 ? `<span class="px-3 py-1 rounded-full text-xs font-bold" style="background: #dbeafe; color: #1e40af;">ℹ️ ${infoCount} gợi ý</span>` : ''}
                            ${issues.length === 0 ? `<span class="px-3 py-1 rounded-full text-xs font-bold" style="background: #d1fae5; color: #065f46;">✅ Không có vấn đề</span>` : ''}
                        </div>
                    </div>
                </div>

                <!-- ═══ AI WEIGHT BREAKDOWN ═══ -->
                <div class="mt-6 p-5 rounded-2xl bg-white border border-gray-100 shadow-sm">
                    <h4 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2">
                        🤖 Mức độ đầy đủ theo trọng số AI Matching
                    </h4>
                    <div class="space-y-3">
                        ${weights.map((w, i) => `
                        <div class="flex items-center gap-3" style="animation: fadeIn ${0.3 + i * 0.1}s ease-out;">
                            <span class="text-base flex-shrink-0 w-6">${w.icon}</span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold text-gray-700">${w.label}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold" style="color: ${w.color};">${Math.round(w.pct)}%</span>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded-md font-bold" style="background: ${w.color}15; color: ${w.color};">×${w.weight}</span>
                                    </div>
                                </div>
                                <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                                    <div class="quality-bar h-full rounded-full" style="width: ${w.pct}%; background: ${w.color}; animation-delay: ${i * 0.1}s;"></div>
                                </div>
                            </div>
                        </div>
                        `).join('')}
                    </div>
                </div>`;

                // ═══ ISSUES ═══
                if (issues.length > 0) {
                    const severityOrder = { error: 1, warning: 2, info: 3 };
                    const sorted = [...issues].sort((a, b) => (severityOrder[a.severity] || 9) - (severityOrder[b.severity] || 9));
                    const fieldLabels = {
                        title: '📌 Tiêu đề', required_skills: '⚙️ Kỹ năng bắt buộc', preferred_skills: '⭐ Kỹ năng ưu tiên',
                        seniority: '🎯 Cấp bậc', experience: '📊 Kinh nghiệm', description: '📝 Mô tả',
                    };
                    const severityStyles = {
                        error:   { bg: '#fef2f2', border: '#fecaca', text: '#991b1b', icon: '❌', label: 'Lỗi' },
                        warning: { bg: '#fffbeb', border: '#fde68a', text: '#92400e', icon: '⚠️', label: 'Cảnh báo' },
                        info:    { bg: '#eff6ff', border: '#bfdbfe', text: '#1e40af', icon: 'ℹ️', label: 'Gợi ý' },
                    };

                    html += `
                    <div class="mt-6">
                        <h4 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">📋 Chi tiết phân tích</h4>
                        <div class="space-y-2">
                            ${sorted.map((issue, i) => {
                                const s = severityStyles[issue.severity] || severityStyles.info;
                                const fieldTag = fieldLabels[issue.field] || '';
                                return `
                                <div class="p-3 rounded-xl border flex items-start gap-3" style="background: ${s.bg}; border-color: ${s.border}; animation: fadeIn ${0.2 + i * 0.08}s ease-out;">
                                    <span class="text-base mt-0.5 flex-shrink-0">${s.icon}</span>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-0.5">
                                            ${fieldTag ? `<span class="text-[10px] px-1.5 py-0.5 rounded-md font-bold" style="background: ${s.border}80; color: ${s.text};">${fieldTag}</span>` : ''}
                                            <span class="text-[10px] px-1.5 py-0.5 rounded-md font-bold" style="background: ${s.border}60; color: ${s.text};">${s.label}</span>
                                        </div>
                                        <p class="text-sm" style="color: ${s.text};">${issue.message}</p>
                                    </div>
                                </div>`;
                            }).join('')}
                        </div>
                    </div>`;
                }

                // ═══ SUGGESTIONS ═══
                const suggestions = data.suggestions || [];
                if (suggestions.length > 0) {
                    html += `
                    <div class="mt-6">
                        <h4 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">💡 Gợi ý cải thiện</h4>
                        <div class="space-y-2">
                            ${suggestions.map(sug => `
                            <div class="p-3 rounded-xl border flex items-start gap-3" style="background: #f0fdf4; border-color: #bbf7d0;">
                                <span class="text-base mt-0.5 flex-shrink-0">💡</span>
                                <p class="text-sm" style="color: #166534;">${sug}</p>
                            </div>
                            `).join('')}
                        </div>
                    </div>`;
                }

                // ═══ SUGGESTED SKILLS ═══
                const sugSkills = data.suggested_skills;
                if (sugSkills && (sugSkills.required?.length || sugSkills.preferred?.length)) {
                    html += `
                    <div class="mt-6 p-5 rounded-2xl border border-violet-100" style="background: linear-gradient(135deg, #f5f3ff, #ede9fe);">
                        <h4 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">🧩 Phân tích kỹ năng — Gợi ý từ AI</h4>
                        <p class="text-xs text-gray-500 mb-3">Dựa trên tiêu đề JD, AI gợi ý thêm các kỹ năng phổ biến cho vị trí này. Click để thêm vào form.</p>
                        <div class="flex flex-wrap gap-2">
                            ${(sugSkills.required || []).map(s => `
                                <button type="button" onclick="addSkillFromSuggestion(this, '${s}', 'required')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border-2 border-violet-300 hover:border-violet-500 transition-all cursor-pointer hover:scale-105"
                                    style="background: white; color: #6d28d9;">
                                    <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px]" style="background: #8b5cf6; color: white;">+</span>
                                    ${s}
                                    <span class="text-[10px] px-1 py-0.5 rounded" style="background: #ede9fe;">bắt buộc</span>
                                </button>`).join('')}
                            ${(sugSkills.preferred || []).map(s => `
                                <button type="button" onclick="addSkillFromSuggestion(this, '${s}', 'preferred')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border-2 border-amber-200 hover:border-amber-400 transition-all cursor-pointer hover:scale-105"
                                    style="background: white; color: #92400e;">
                                    <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px]" style="background: #f59e0b; color: white;">+</span>
                                    ${s}
                                    <span class="text-[10px] px-1 py-0.5 rounded" style="background: #fef3c7;">ưu tiên</span>
                                </button>`).join('')}
                        </div>
                    </div>`;
                }

                // ═══ INFERRED SENIORITY / EXPERIENCE ═══
                let inferParts = [];
                if (data.suggested_seniority) {
                    const seniorityLabels = { intern: 'Thực tập', fresher: 'Fresher', junior: 'Junior', mid: 'Mid-level', senior: 'Senior', lead: 'Tech Lead', principal: 'Principal' };
                    inferParts.push(`<div class="flex items-center gap-3 p-3 rounded-xl" style="background: white; border: 1px solid #c7d2fe;">
                        <span class="w-10 h-10 rounded-xl flex items-center justify-center text-lg" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;">🎯</span>
                        <div>
                            <p class="text-xs font-semibold text-gray-500">Cấp bậc gợi ý</p>
                            <p class="text-sm font-bold" style="color: #4338ca;">${seniorityLabels[data.suggested_seniority] || data.suggested_seniority}</p>
                        </div>
                    </div>`);
                }
                if (data.suggested_experience) {
                    inferParts.push(`<div class="flex items-center gap-3 p-3 rounded-xl" style="background: white; border: 1px solid #99f6e4;">
                        <span class="w-10 h-10 rounded-xl flex items-center justify-center text-lg" style="background: linear-gradient(135deg, #14b8a6, #0d9488); color: white;">📊</span>
                        <div>
                            <p class="text-xs font-semibold text-gray-500">Kinh nghiệm gợi ý</p>
                            <p class="text-sm font-bold" style="color: #0f766e;">${data.suggested_experience.min}–${data.suggested_experience.max} năm</p>
                        </div>
                    </div>`);
                }
                if (inferParts.length) {
                    html += `
                    <div class="mt-6">
                        <h4 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">🎯 AI tự suy luận từ tiêu đề</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">${inferParts.join('')}</div>
                    </div>`;
                }

                html += `</div>`; // close fadeIn wrapper
                resultEl.innerHTML = html;
                resultEl.classList.remove('hidden');
            }

            // Click-to-add skill from suggestion
            window.addSkillFromSuggestion = function(btn, skill, type) {
                const inputName = type === 'required' ? 'required_skills[]' : 'preferred_skills[]';
                const checkbox = document.querySelector('input[name="' + inputName + '"][value="' + skill + '"]');
                if (checkbox) {
                    checkbox.checked = true;
                    checkbox.dispatchEvent(new Event('change'));
                    // Flash the label
                    const label = checkbox.closest('label');
                    if (label) {
                        label.style.transition = 'all 0.3s';
                        label.style.boxShadow = '0 0 0 3px ' + (type === 'required' ? '#8b5cf680' : '#f59e0b80');
                        setTimeout(() => { label.style.boxShadow = ''; }, 1500);
                        label.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
                // Remove the button
                btn.style.transition = 'all 0.3s';
                btn.style.opacity = '0';
                btn.style.transform = 'scale(0.8)';
                setTimeout(() => btn.remove(), 300);
            };
        });
    </script>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5863877a5171c196453bfa0bd807e410)): ?>
<?php $attributes = $__attributesOriginal5863877a5171c196453bfa0bd807e410; ?>
<?php unset($__attributesOriginal5863877a5171c196453bfa0bd807e410); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5863877a5171c196453bfa0bd807e410)): ?>
<?php $component = $__componentOriginal5863877a5171c196453bfa0bd807e410; ?>
<?php unset($__componentOriginal5863877a5171c196453bfa0bd807e410); ?>
<?php endif; ?>
<?php /**PATH D:\web\cpanel_public_html\backend\resources\views/admin/post-job.blade.php ENDPATH**/ ?>