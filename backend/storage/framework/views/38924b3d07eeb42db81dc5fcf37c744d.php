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
    
    <?php if(auth()->guard()->check()): ?>
        <?php if(config('app.demo_mode')): ?>
            <div class="mb-8 animate-fade-in">
                <?php if(Auth::user()->role === 'candidate'): ?>
                    <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center">
                                <span class="text-lg">👤</span>
                            </div>
                            <div>
                                <p class="font-bold text-indigo-900">Bạn đang xem với vai Ứng viên</p>
                                <p class="text-indigo-600 text-sm">Chọn một công việc bên dưới → Ứng tuyển → Xem AI phân tích</p>
                            </div>
                        </div>
                        <a href="<?php echo e(route('candidate.applications')); ?>" class="hidden sm:inline-flex items-center px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-all">
                            📋 Đơn đã nộp
                        </a>
                    </div>
                <?php elseif(Auth::user()->role === 'recruiter' || Auth::user()->role === 'admin'): ?>
                    <div class="bg-purple-50 border border-purple-200 rounded-2xl p-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
                                <span class="text-lg">🏢</span>
                            </div>
                            <div>
                                <p class="font-bold text-purple-900">Bạn đang xem với vai Nhà tuyển dụng</p>
                                <p class="text-purple-600 text-sm">Chọn job → Nhấn "AI Shortlist" → Xem xếp hạng ứng viên</p>
                            </div>
                        </div>
                        <div class="hidden sm:flex items-center gap-2">
                            <a href="<?php echo e(route('admin.dashboard')); ?>" class="inline-flex items-center px-4 py-2 rounded-xl bg-purple-100 text-purple-700 text-sm font-semibold hover:bg-purple-200 transition-all">
                                📊 Dashboard
                            </a>
                            <a href="<?php echo e(route('admin.jobs.create')); ?>" class="inline-flex items-center px-4 py-2 rounded-xl bg-purple-600 text-white text-sm font-semibold hover:bg-purple-700 transition-all">
                                + Đăng tuyển
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Việc làm đang tuyển</h1>
                <p class="text-gray-500 mt-1 text-sm"><?php echo e($jobs->total()); ?> vị trí · Chọn để xem chi tiết và ứng tuyển</p>
            </div>

            
            <form action="<?php echo e(route('home')); ?>" method="GET" class="hidden md:flex items-center gap-2">
                <input type="hidden" name="sector" value="<?php echo e($sector ?? 'it'); ?>">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" name="keyword" value="<?php echo e(request('keyword')); ?>" placeholder="Tìm kiếm..." class="pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent w-48">
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-all">Tìm</button>
                <?php if(request()->hasAny(['keyword'])): ?>
                    <a href="<?php echo e(route('home')); ?>" class="text-xs text-gray-400 hover:text-red-500">✕</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if($jobs->count() > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-10">
            <?php $__currentLoopData = $jobs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $job): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(Auth::check() && (Auth::user()->role === 'recruiter' || Auth::user()->role === 'admin') ? route('admin.jobs.ai-shortlist', $job->id) : route('jobs.show', $job->id)); ?>"
                   class="block bg-white rounded-2xl border border-gray-100 hover:border-indigo-300 hover:shadow-xl hover:shadow-indigo-100/50 transition-all duration-300 overflow-hidden group animate-fade-in"
                   style="animation-delay: <?php echo e($index * 0.05); ?>s;">

                    
                    <div class="h-1.5 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

                    <div class="p-5">
                        
                        <div class="flex items-start gap-3 mb-3">
                            <div class="w-11 h-11 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center flex-shrink-0 overflow-hidden">
                                <?php if($job->company && $job->company->logo_path): ?>
                                    <img src="<?php echo e(asset('storage/' . $job->company->logo_path)); ?>" alt="<?php echo e($job->company->name ?? ''); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="text-sm font-bold gradient-text"><?php echo e(strtoupper(substr($job->company->name ?? 'C', 0, 1))); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs text-indigo-600 font-semibold"><?php echo e($job->company->name ?? 'Công ty'); ?></p>
                                <h3 class="text-base font-bold text-gray-900 group-hover:text-indigo-600 transition-colors line-clamp-2">
                                    <?php echo e($job->title); ?>

                                </h3>
                            </div>
                        </div>

                        
                        <p class="text-gray-500 text-sm mb-3 line-clamp-2">
                            <?php echo e(Str::limit(strip_tags($job->description), 90)); ?>

                        </p>

                        
                        <?php if(config('app.demo_mode') && !empty($demoSeedInfo[$job->id])): ?>
                            <?php $seedInfo = $demoSeedInfo[$job->id]; ?>
                            <div class="flex flex-wrap gap-1.5 mb-3">
                                <?php if(auth()->guard()->check()): ?>
                                    <?php if(Auth::user()->role === 'candidate'): ?>
                                        <?php if(!empty($seedInfo['applied']) && !empty($seedInfo['has_ai_result'])): ?>
                                            <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-xs font-semibold rounded-lg border border-emerald-200">✅ Đã ứng tuyển</span>
                                            <span class="px-2 py-0.5 bg-violet-50 text-violet-600 text-xs font-medium rounded-lg border border-violet-200">🤖 Có AI follow-up</span>
                                        <?php elseif(!empty($seedInfo['applied'])): ?>
                                            <span class="px-2 py-0.5 bg-amber-50 text-amber-700 text-xs font-semibold rounded-lg border border-amber-200">📝 Đã ứng tuyển</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 bg-blue-50 text-blue-600 text-xs font-medium rounded-lg border border-blue-200">🆕 Nên thử apply</span>
                                        <?php endif; ?>
                                    <?php elseif(Auth::user()->role === 'recruiter' || Auth::user()->role === 'admin'): ?>
                                        <?php if(($seedInfo['app_count'] ?? 0) > 0 && ($seedInfo['ai_count'] ?? 0) > 0): ?>
                                            <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-xs font-semibold rounded-lg border border-emerald-200">🤖 Có AI shortlist sẵn</span>
                                            <?php if(($seedInfo['app_count'] ?? 0) > ($seedInfo['ai_count'] ?? 0)): ?>
                                                <span class="px-2 py-0.5 bg-amber-50 text-amber-600 text-xs font-medium rounded-lg border border-amber-200">+<?php echo e(($seedInfo['app_count'] ?? 0) - ($seedInfo['ai_count'] ?? 0)); ?> chưa chấm AI</span>
                                            <?php endif; ?>
                                        <?php elseif(($seedInfo['app_count'] ?? 0) > 0): ?>
                                            <span class="px-2 py-0.5 bg-amber-50 text-amber-700 text-xs font-semibold rounded-lg border border-amber-200">📋 Có ứng viên, chưa chấm AI</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 bg-gray-50 text-gray-500 text-xs font-medium rounded-lg border border-gray-200">Chưa có ứng viên</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        
                        <div class="flex flex-wrap gap-1.5 mb-4">
                            <?php if($job->seniority): ?>
                                <span class="px-2 py-0.5 bg-violet-50 text-violet-700 text-xs font-semibold rounded-lg"><?php echo e(ucfirst($job->seniority)); ?></span>
                            <?php endif; ?>
                            <?php if($job->location): ?>
                                <span class="px-2 py-0.5 bg-gray-50 text-gray-600 text-xs rounded-lg">📍 <?php echo e($job->location); ?></span>
                            <?php endif; ?>
                            <?php if($job->salary_min || $job->salary_max): ?>
                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-xs rounded-lg">
                                    <?php if($job->salary_min && $job->salary_max): ?>
                                        <?php echo e(number_format($job->salary_min/1000000)); ?>-<?php echo e(number_format($job->salary_max/1000000)); ?>M
                                    <?php elseif($job->salary_min): ?>
                                        Từ <?php echo e(number_format($job->salary_min/1000000)); ?>M
                                    <?php else: ?>
                                        Tới <?php echo e(number_format($job->salary_max/1000000)); ?>M
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        
                        <?php if(is_array($job->required_skills) && count($job->required_skills) > 0): ?>
                            <div class="flex flex-wrap gap-1 mb-3">
                                <?php $__currentLoopData = array_slice($job->required_skills, 0, 4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $skill): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <span class="px-2 py-0.5 bg-indigo-50 text-indigo-600 text-xs font-medium rounded-md"><?php echo e($skill); ?></span>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php if(count($job->required_skills) > 4): ?>
                                    <span class="px-2 py-0.5 bg-gray-50 text-gray-500 text-xs rounded-md">+<?php echo e(count($job->required_skills) - 4); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        
                        <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                            <span class="text-xs text-gray-400"><?php echo e($job->created_at->diffForHumans()); ?></span>
                            <?php if(auth()->guard()->check()): ?>
                                <?php if(Auth::user()->role === 'recruiter' || Auth::user()->role === 'admin'): ?>
                                    <?php if(config('app.demo_mode') && !empty($demoSeedInfo[$job->id]) && ($demoSeedInfo[$job->id]['ai_count'] ?? 0) > 0): ?>
                                        <span class="text-xs font-semibold text-emerald-600 group-hover:text-emerald-700">
                                            🤖 Xem AI Shortlist →
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs font-semibold text-purple-600 group-hover:text-purple-700">
                                            🤖 AI Shortlist →
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if(config('app.demo_mode') && !empty($demoSeedInfo[$job->id]) && !empty($demoSeedInfo[$job->id]['applied'])): ?>
                                        <span class="text-xs font-semibold text-emerald-600 group-hover:text-emerald-700">
                                            Xem kết quả AI →
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs font-semibold text-indigo-600 group-hover:text-indigo-700">
                                            Xem & Ứng tuyển →
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-xs font-semibold text-indigo-600 group-hover:text-indigo-700">
                                    Xem chi tiết →
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <div class="flex justify-center">
            <?php echo e($jobs->links()); ?>

        </div>
    <?php else: ?>
        
        <div class="text-center py-16">
            <div class="w-20 h-20 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Chưa có việc làm nào</h3>
            <p class="text-gray-500 text-sm">Hãy quay lại sau để xem các cơ hội việc làm mới nhất.</p>
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
<?php /**PATH D:\web\cpanel_public_html\backend\resources\views/welcome.blade.php ENDPATH**/ ?>