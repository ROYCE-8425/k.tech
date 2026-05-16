<?php if (isset($component)) { $__componentOriginal5863877a5171c196453bfa0bd807e410 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5863877a5171c196453bfa0bd807e410 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.app','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.app'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <!-- Header -->
    <div class="mb-10">
        <a href="<?php echo e(route('admin.dashboard')); ?>" class="inline-flex items-center text-gray-500 hover:text-indigo-600 mb-6 group transition-colors">
            <div class="w-10 h-10 rounded-xl bg-gray-100 group-hover:bg-indigo-100 flex items-center justify-center mr-3 transition-colors">
                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </div>
            <span class="font-medium">Quay lại Dashboard</span>
        </a>

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <div class="flex items-center space-x-4 mb-3">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center shadow-xl">
                        <span class="text-2xl font-bold text-white">
                            <?php echo e(strtoupper(substr($job->company->name ?? 'C', 0, 1))); ?>

                        </span>
                    </div>
                    <div>
                        <p class="text-indigo-600 font-semibold"><?php echo e($job->company->name ?? 'Công ty'); ?></p>
                        <h1 class="text-3xl font-bold text-gray-900"><?php echo e($job->title); ?></h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4 text-gray-500">
                    <span class="inline-flex items-center">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <?php echo e($applications->total()); ?> ứng viên
                    </span>
                    <?php if($job->location): ?>
                        <span class="text-gray-300">•</span>
                        <span><?php echo e($job->location); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                
                <a href="<?php echo e(route('admin.jobs.ai-shortlist', $job->id)); ?>" class="inline-flex items-center px-5 py-3 rounded-xl bg-gradient-to-r from-violet-500 to-purple-600 text-white font-semibold hover:from-violet-600 hover:to-purple-700 shadow-lg hover:shadow-xl transition-all duration-300">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                    🤖 AI Shortlist
                </a>

                
                <a href="<?php echo e(route('admin.jobs.export-pdf', $job->id)); ?>" class="inline-flex items-center px-5 py-3 rounded-xl bg-red-100 text-red-700 font-semibold hover:bg-red-600 hover:text-white transition-all duration-300">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Xuất PDF
                </a>
            </div>
        </div>
    </div>

    
    <?php if(config('app.demo_mode')): ?>
        <div class="flex items-start gap-3 p-3 rounded-2xl bg-purple-50 border border-purple-200 mb-6 animate-fade-in">
            <span class="text-lg">💡</span>
            <div class="text-sm text-purple-700">
                <span class="font-semibold">Demo tip:</span>
                Nhấn <span class="font-semibold">🤖 AI Shortlist</span> ở trên để xem AI xếp hạng tất cả ứng viên cho vị trí này.
                Bạn cũng có thể thay đổi trạng thái và thêm ghi chú trên từng ứng viên.
            </div>
        </div>
    <?php endif; ?>

    <!-- Score Legend -->
    <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-4 mb-6">
        <div class="flex flex-wrap items-center justify-center gap-6 text-sm">
            <span class="text-gray-500 font-medium">🤖 AI Fit Score:</span>
            <div class="flex items-center">
                <span class="w-3 h-3 rounded-full bg-emerald-500 mr-2"></span>
                <span class="text-gray-600">Phù hợp cao (≥80)</span>
            </div>
            <div class="flex items-center">
                <span class="w-3 h-3 rounded-full bg-amber-500 mr-2"></span>
                <span class="text-gray-600">Phù hợp vừa (60–79)</span>
            </div>
            <div class="flex items-center">
                <span class="w-3 h-3 rounded-full bg-red-500 mr-2"></span>
                <span class="text-gray-600">Phù hợp thấp (&lt;60)</span>
            </div>
            <div class="flex items-center">
                <span class="w-3 h-3 rounded-full bg-gray-300 mr-2"></span>
                <span class="text-gray-600">Chưa phân tích</span>
            </div>
        </div>
    </div>

    <!-- Applications Grid -->
    <?php if($applications->count() > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <?php $__currentLoopData = $applications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $application): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $aiResult = is_array($application->ai_match_result) ? $application->ai_match_result : [];
                    $score = $aiResult['fit_score'] ?? null;
                    $scoreColor = $score === null ? 'gray' : ($score >= 80 ? 'emerald' : ($score >= 60 ? 'amber' : 'red'));
                    $scoreBg = [
                        'gray' => 'from-gray-400 to-gray-500',
                        'emerald' => 'from-emerald-400 to-teal-500',
                        'amber' => 'from-amber-400 to-orange-500',
                        'red' => 'from-red-400 to-pink-500',
                    ][$scoreColor];
                ?>
                
                <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 overflow-hidden card-hover animate-fade-in" style="animation-delay: <?php echo e($index * 0.05); ?>s;">
                    <!-- Card Header -->
                    <div class="relative p-6 bg-gradient-to-br <?php echo e($scoreBg); ?>">
                        <div class="absolute inset-0 bg-black/5"></div>
                        <div class="relative flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <!-- Avatar -->
                                <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center">
                                    <span class="text-xl font-bold text-white">
                                        <?php echo e(strtoupper(substr($application->candidate->name ?? 'U', 0, 2))); ?>

                                    </span>
                                </div>
                                <div>
                                    <h3 class="font-bold text-white text-lg"><?php echo e($application->candidate->name ?? 'Ứng viên'); ?></h3>
                                    <p class="text-white/80 text-sm"><?php echo e($application->created_at->format('d/m/Y H:i')); ?></p>
                                </div>
                            </div>
                            <!-- Score Badge -->
                            <div class="text-center">
                                <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur flex flex-col items-center justify-center">
                                    <?php if($score !== null): ?>
                                        <span class="text-2xl font-bold text-white"><?php echo e(number_format($score, 0)); ?></span>
                                        <span class="text-xs text-white/80">điểm</span>
                                    <?php else: ?>
                                        <span class="text-lg font-bold text-white">--</span>
                                        <span class="text-xs text-white/80">N/A</span>
                                    <?php endif; ?>
                                </div>
                                <?php if($score !== null): ?>
                                    <div class="mt-2 text-xs text-white/90">
                                        AI: <span class="font-semibold"><?php echo e(number_format($score, 1)); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="mt-2 text-xs text-white/70">
                                        Chưa phân tích
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-6">
                        <!-- Contact Info -->
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center text-gray-600">
                                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <span class="text-sm truncate"><?php echo e($application->candidate->email ?? 'N/A'); ?></span>
                            </div>
                            <?php if($application->candidate->phone): ?>
                                <div class="flex items-center text-gray-600">
                                    <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    <span class="text-sm"><?php echo e($application->candidate->phone); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- CV Summary -->
                        <?php if($application->candidate->summary): ?>
                            <div class="mb-6">
                                <p class="text-sm text-gray-500 line-clamp-3"><?php echo e($application->candidate->summary); ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Quick CV (Dialog) -->
                        <?php if($application->cv_data): ?>
                            <?php
                                $cv = is_array($application->cv_data) ? $application->cv_data : [];
                                $education = is_array($cv) ? ($cv['education'] ?? []) : [];
                                $work = is_array($cv) ? ($cv['work_experiences'] ?? []) : [];
                                $skills = is_array($cv) ? ($cv['skills'] ?? []) : [];
                                $hardSkills = is_array($skills) ? ($skills['hard'] ?? []) : [];
                                $softSkills = is_array($skills) ? ($skills['soft'] ?? []) : [];
                            ?>
                            <div class="mb-6 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-sm font-semibold text-gray-700">CV nhanh</p>
                                    <span class="text-xs text-gray-500">Hộp thoại</span>
                                </div>

                                <?php if(!empty($cv['self_description'])): ?>
                                    <p class="text-sm text-gray-600 whitespace-pre-line"><?php echo e($cv['self_description']); ?></p>
                                <?php endif; ?>

                                <?php if(is_array($education) && count($education) > 0): ?>
                                    <div class="mt-3 space-y-3">
                                        <?php $__currentLoopData = $education; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $eduIndex => $edu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php
                                                $proofs = $application->cv_proof_files;
                                                $proofPath = is_array($proofs) ? ($proofs[$eduIndex] ?? null) : null;
                                            ?>
                                            <div class="text-sm text-gray-700">
                                                <div class="font-medium"><?php echo e($edu['school'] ?? 'Trường'); ?></div>
                                                <div class="text-gray-600">
                                                    <?php echo e($edu['degree_level'] ?? ''); ?>

                                                    <?php if(!empty($edu['major'])): ?>
                                                        • <?php echo e($edu['major']); ?>

                                                    <?php endif; ?>
                                                    <?php if(!empty($edu['graduation_year'])): ?>
                                                        • <?php echo e($edu['graduation_year']); ?>

                                                    <?php endif; ?>
                                                </div>
                                                <?php if($proofPath): ?>
                                                    <a href="<?php echo e(route('admin.applications.download-cv-proof', ['id' => $application->id, 'index' => $eduIndex])); ?>"
                                                       class="inline-flex items-center mt-1 text-xs text-indigo-600 hover:text-indigo-800 transition-colors">
                                                        Tải minh chứng
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if(is_array($work) && count($work) > 0): ?>
                                    <div class="mt-4 pt-4 border-t border-gray-200">
                                        <p class="text-sm font-semibold text-gray-700 mb-2">Kinh nghiệm làm việc</p>
                                        <div class="space-y-3">
                                            <?php $__currentLoopData = $work; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <div class="text-sm text-gray-700">
                                                    <div class="font-medium">
                                                        <?php echo e($w['company_name'] ?? 'Công ty'); ?>

                                                        <?php if(!empty($w['position_title'])): ?>
                                                            • <?php echo e($w['position_title']); ?>

                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-gray-600">
                                                        <?php echo e($w['start_date'] ?? ''); ?>

                                                        <?php if(!empty($w['is_current'])): ?>
                                                            • Hiện tại
                                                        <?php elseif(!empty($w['end_date'])): ?>
                                                            • <?php echo e($w['end_date']); ?>

                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if(!empty($w['description'])): ?>
                                                        <div class="mt-1 text-gray-600 whitespace-pre-line"><?php echo e($w['description']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if((is_array($hardSkills) && count($hardSkills) > 0) || (is_array($softSkills) && count($softSkills) > 0)): ?>
                                    <div class="mt-4 pt-4 border-t border-gray-200">
                                        <p class="text-sm font-semibold text-gray-700 mb-2">Kỹ năng</p>

                                        <?php if(is_array($hardSkills) && count($hardSkills) > 0): ?>
                                            <div class="mb-3">
                                                <p class="text-xs font-semibold text-gray-600 mb-1">Hard Skills</p>
                                                <div class="flex flex-wrap gap-2">
                                                    <?php $__currentLoopData = $hardSkills; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold">
                                                            <?php echo e($s['name'] ?? 'Skill'); ?>

                                                            <?php if(!empty($s['level'])): ?>
                                                                <span class="ml-1 text-indigo-500">(<?php echo e($s['level']); ?>/5)</span>
                                                            <?php endif; ?>
                                                        </span>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if(is_array($softSkills) && count($softSkills) > 0): ?>
                                            <div>
                                                <p class="text-xs font-semibold text-gray-600 mb-1">Soft Skills</p>
                                                <div class="flex flex-wrap gap-2">
                                                    <?php $__currentLoopData = $softSkills; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-purple-50 text-purple-700 text-xs font-semibold">
                                                            <?php echo e($s['name'] ?? 'Skill'); ?>

                                                            <?php if(!empty($s['level'])): ?>
                                                                <span class="ml-1 text-purple-500">(<?php echo e($s['level']); ?>/5)</span>
                                                            <?php endif; ?>
                                                        </span>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Status Dropdown -->
                        <form action="<?php echo e(route('admin.applications.update-status', $application->id)); ?>" method="POST" class="mb-4">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PATCH'); ?>
                            <select name="status" onchange="this.form.submit()" 
                                    class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-0 text-sm font-medium transition-colors
                                    <?php if($application->status === 'hired'): ?> bg-emerald-50 border-emerald-200 text-emerald-700
                                    <?php elseif($application->status === 'rejected'): ?> bg-red-50 border-red-200 text-red-700
                                    <?php elseif($application->status === 'shortlisted'): ?> bg-indigo-50 border-indigo-200 text-indigo-700
                                    <?php else: ?> bg-gray-50 <?php endif; ?>">
                                <option value="submitted" <?php echo e($application->status === 'submitted' ? 'selected' : ''); ?>>📨 Đã nộp</option>
                                <option value="reviewing" <?php echo e($application->status === 'reviewing' ? 'selected' : ''); ?>>👁️ Đang xem xét</option>
                                <option value="shortlisted" <?php echo e($application->status === 'shortlisted' ? 'selected' : ''); ?>>⭐ Vào vòng trong</option>
                                <option value="interviewed" <?php echo e($application->status === 'interviewed' ? 'selected' : ''); ?>>🎤 Đã phỏng vấn</option>
                                <option value="offered" <?php echo e($application->status === 'offered' ? 'selected' : ''); ?>>🎉 Có offer</option>
                                <option value="hired" <?php echo e($application->status === 'hired' ? 'selected' : ''); ?>>✅ Đã tuyển</option>
                                <option value="rejected" <?php echo e($application->status === 'rejected' ? 'selected' : ''); ?>>❌ Từ chối</option>
                            </select>
                        </form>

                        <!-- Notes Section -->
                        <div x-data="{ showNotes: false, notes: '<?php echo e(addslashes($application->notes ?? '')); ?>' }" class="mb-4">
                            <button @click="showNotes = !showNotes" class="flex items-center text-sm text-gray-500 hover:text-indigo-600 transition-colors">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                <span x-text="notes ? 'Xem/Sửa ghi chú' : 'Thêm ghi chú'"></span>
                            </button>
                            
                            <div x-show="showNotes" x-transition class="mt-3">
                                <form action="<?php echo e(route('admin.applications.update-notes', $application->id)); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <textarea 
                                        name="notes" 
                                        rows="3" 
                                        class="w-full px-3 py-2 text-sm border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none resize-none"
                                        placeholder="Ghi chú về ứng viên..."
                                    ><?php echo e($application->notes); ?></textarea>
                                    <button type="submit" class="mt-2 px-4 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                                        Lưu ghi chú
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col gap-3">
                            
                            <a href="<?php echo e(route('admin.jobs.ai-shortlist', $job->id)); ?>"
                               class="flex items-center justify-center w-full px-4 py-3 rounded-xl bg-gradient-to-r from-violet-50 to-purple-50 text-purple-700 font-semibold hover:from-violet-600 hover:to-purple-700 hover:text-white transition-all duration-300">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                </svg>
                                🤖 Xem AI Shortlist
                            </a>

                            <!-- Export PDF Button -->
                            <a href="<?php echo e(route('admin.applications.export-pdf', $application->id)); ?>" 
                               class="flex items-center justify-center w-full px-4 py-3 rounded-xl bg-red-50 text-red-600 font-semibold hover:bg-red-600 hover:text-white transition-all duration-300">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                Xuất PDF
                            </a>
                            
                            <!-- Download CV Button -->
                            <?php if($application->cv_file_path): ?>
                                <a href="<?php echo e(route('admin.applications.download-cv', $application->id)); ?>" 
                                   class="flex items-center justify-center w-full px-4 py-3 rounded-xl bg-indigo-50 text-indigo-600 font-semibold hover:bg-indigo-600 hover:text-white transition-all duration-300">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Tải CV gốc
                                </a>
                            <?php endif; ?>

                            <!-- Schedule Interview Button -->
                            <?php if(in_array($application->status, ['submitted', 'reviewing', 'shortlisted'], true)): ?>
                                <div x-data="{ showSchedule: false }">
                                    <button @click="showSchedule = !showSchedule" 
                                            class="flex items-center justify-center w-full px-4 py-3 rounded-xl bg-green-50 text-green-600 font-semibold hover:bg-green-600 hover:text-white transition-all duration-300">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        Lên lịch phỏng vấn
                                    </button>

                                    <!-- Schedule Form -->
                                    <div x-show="showSchedule" x-transition class="mt-3 p-4 bg-gray-50 rounded-xl border-2 border-gray-100">
                                        <form action="<?php echo e(route('admin.applications.schedule-interview', $application->id)); ?>" method="POST" class="space-y-3">
                                            <?php echo csrf_field(); ?>
                                            
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Thời gian phỏng vấn</label>
                                                <input type="datetime-local" name="scheduled_at" required
                                                       class="w-full px-3 py-2 text-sm border-2 border-gray-200 rounded-lg focus:border-green-500 focus:outline-none">
                                            </div>
                                            
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Thời lượng (phút)</label>
                                                <select name="duration_minutes" class="w-full px-3 py-2 text-sm border-2 border-gray-200 rounded-lg focus:border-green-500 focus:outline-none">
                                                    <option value="30">30 phút</option>
                                                    <option value="45">45 phút</option>
                                                    <option value="60" selected>60 phút</option>
                                                    <option value="90">90 phút</option>
                                                    <option value="120">120 phút</option>
                                                </select>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Hình thức</label>
                                                <select name="type" class="w-full px-3 py-2 text-sm border-2 border-gray-200 rounded-lg focus:border-green-500 focus:outline-none">
                                                    <option value="onsite">Trực tiếp</option>
                                                    <option value="online">Online</option>
                                                    <option value="phone">Điện thoại</option>
                                                </select>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Địa điểm / Link meeting</label>
                                                <input type="text" name="location" placeholder="VD: Phòng họp A hoặc link Zoom..."
                                                       class="w-full px-3 py-2 text-sm border-2 border-gray-200 rounded-lg focus:border-green-500 focus:outline-none">
                                            </div>
                                            
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Ghi chú</label>
                                                <textarea name="notes" rows="2" placeholder="Ghi chú thêm về buổi phỏng vấn..."
                                                          class="w-full px-3 py-2 text-sm border-2 border-gray-200 rounded-lg focus:border-green-500 focus:outline-none resize-none"></textarea>
                                            </div>
                                            
                                            <div class="flex gap-2">
                                                <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                                                    Xác nhận lịch
                                                </button>
                                                <button type="button" @click="showSchedule = false" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition-colors">
                                                    Hủy
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- View Scheduled Interviews -->
                            <?php if($application->interviews && $application->interviews->count() > 0): ?>
                                <div class="p-3 bg-blue-50 rounded-xl border-2 border-blue-100">
                                    <h4 class="text-sm font-semibold text-blue-700 mb-2 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        Lịch phỏng vấn
                                    </h4>
                                    <?php $__currentLoopData = $application->interviews->sortByDesc('scheduled_at')->take(2); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $interview): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="text-xs text-gray-600 mb-1 flex items-center justify-between">
                                            <span><?php echo e(\Carbon\Carbon::parse($interview->scheduled_at)->format('d/m/Y H:i')); ?></span>
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                                <?php echo e($interview->status === 'scheduled' ? 'bg-yellow-100 text-yellow-700' : ''); ?>

                                                <?php echo e($interview->status === 'completed' ? 'bg-green-100 text-green-700' : ''); ?>

                                                <?php echo e($interview->status === 'cancelled' ? 'bg-red-100 text-red-700' : ''); ?>

                                            ">
                                                <?php echo e($interview->status === 'scheduled' ? 'Đã lên lịch' : ($interview->status === 'completed' ? 'Hoàn thành' : 'Đã hủy')); ?>

                                            </span>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <a href="<?php echo e(route('admin.interviews')); ?>" class="text-xs text-blue-600 hover:underline mt-1 inline-block">
                                        Xem tất cả →
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center">
            <?php echo e($applications->links()); ?>

        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 p-16 text-center">
            <div class="relative inline-block mb-8">
                <div class="absolute inset-0 bg-indigo-100 rounded-full blur-2xl opacity-60"></div>
                <div class="relative w-32 h-32 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center">
                    <svg class="w-16 h-16 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-3">Chưa có ứng viên nào</h3>
            <p class="text-gray-500 max-w-md mx-auto">Hãy chờ các ứng viên nộp đơn ứng tuyển cho vị trí này. Bạn sẽ nhận được thông báo khi có CV mới.</p>
        </div>
    <?php endif; ?>
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
<?php /**PATH D:\web\cpanel_public_html\backend\resources\views/admin/job-applications.blade.php ENDPATH**/ ?>