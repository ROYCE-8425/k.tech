<?php if (isset($component)) { $__componentOriginal5863877a5171c196453bfa0bd807e410 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5863877a5171c196453bfa0bd807e410 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.app','data' => ['title' => 'Demo — Smart CV Matcher']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.app'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Demo — Smart CV Matcher']); ?>
    <div class="max-w-2xl mx-auto py-4">

        
        <div class="text-center mb-8 animate-slide-up">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center text-white mx-auto mb-4 shadow-xl shadow-indigo-500/30">
                <span class="text-2xl">🤖</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Smart CV Matcher</h1>
            <p class="text-gray-500 text-sm max-w-md mx-auto">
                AI tự động phân tích CV ứng viên, so khớp với Job Description, và xếp hạng cho nhà tuyển dụng.
            </p>
        </div>

        
        <div class="flex items-center justify-center gap-3 mb-8 animate-fade-in" style="animation-delay: 0.15s;">
            <div class="flex items-center gap-1.5 px-3 py-1.5 bg-white rounded-full border border-gray-200 text-xs text-gray-600">
                <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[10px] font-bold">1</span>
                Ứng viên nộp CV
            </div>
            <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            <div class="flex items-center gap-1.5 px-3 py-1.5 bg-white rounded-full border border-gray-200 text-xs text-gray-600">
                <span class="w-5 h-5 rounded-full bg-violet-100 text-violet-600 flex items-center justify-center text-[10px] font-bold">2</span>
                AI phân tích
            </div>
            <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            <div class="flex items-center gap-1.5 px-3 py-1.5 bg-white rounded-full border border-gray-200 text-xs text-gray-600">
                <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-[10px] font-bold">3</span>
                Xếp hạng ứng viên
            </div>
        </div>

        
        <?php if(session('status')): ?>
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 mb-4 text-center animate-fade-in">
                <p class="text-emerald-800 font-semibold text-sm"><?php echo e(session('status')); ?></p>
            </div>
        <?php endif; ?>
        <?php if(session('error')): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-4 text-center animate-fade-in">
                <p class="text-red-800 font-semibold text-sm"><?php echo e(session('error')); ?></p>
            </div>
        <?php endif; ?>

        
        <div class="bg-violet-50 border border-violet-200 rounded-xl px-4 py-3 mb-6 text-center animate-fade-in" style="animation-delay: 0.2s;">
            <p class="text-violet-800 font-bold text-sm">🧪 Chế độ Demo — Dữ liệu đã chuẩn bị sẵn, chọn vai trò để bắt đầu</p>
        </div>

        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6 animate-slide-up" style="animation-delay: 0.25s;">

            
            <form action="<?php echo e(route('demo.enter-candidate')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <button type="submit" id="btn-enter-candidate"
                    class="w-full bg-white rounded-2xl border-2 border-gray-200 hover:border-indigo-500 hover:shadow-xl hover:shadow-indigo-100/50 p-6 text-left transition-all duration-300 group cursor-pointer">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 rounded-xl bg-indigo-100 group-hover:bg-indigo-500 flex items-center justify-center transition-colors">
                            <span class="text-xl group-hover:scale-110 transition-transform">👤</span>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Vào vai Ứng viên</h2>
                            <p class="text-xs text-gray-500">Xem job → Ứng tuyển → AI phân tích CV</p>
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3 text-xs text-gray-500 space-y-1 mb-3">
                        <p class="font-semibold text-gray-700">Tài khoản demo:</p>
                        <p>Nguyễn Văn Demo · Backend Developer</p>
                        <p class="text-green-600">✓ Hồ sơ & CV có sẵn · 2 đơn đã nộp</p>
                    </div>
                    <div class="text-sm font-bold text-indigo-600 group-hover:text-indigo-700 flex items-center gap-1">
                        Bắt đầu
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </div>
                </button>
            </form>

            
            <form action="<?php echo e(route('demo.enter-recruiter')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <button type="submit" id="btn-enter-recruiter"
                    class="w-full bg-white rounded-2xl border-2 border-gray-200 hover:border-purple-500 hover:shadow-xl hover:shadow-purple-100/50 p-6 text-left transition-all duration-300 group cursor-pointer">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 rounded-xl bg-purple-100 group-hover:bg-purple-500 flex items-center justify-center transition-colors">
                            <span class="text-xl group-hover:scale-110 transition-transform">🏢</span>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Vào vai Nhà tuyển dụng</h2>
                            <p class="text-xs text-gray-500">Xem AI Shortlist → Đánh giá ứng viên</p>
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3 text-xs text-gray-500 space-y-1 mb-3">
                        <p class="font-semibold text-gray-700">Tài khoản demo:</p>
                        <p>Demo Recruiter · KTC Demo Corp</p>
                        <p class="text-green-600">✓ 4 job đã đăng · Có đơn ứng tuyển sẵn</p>
                    </div>
                    <div class="text-sm font-bold text-purple-600 group-hover:text-purple-700 flex items-center gap-1">
                        Bắt đầu
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </div>
                </button>
            </form>
        </div>

        
        <div class="bg-white rounded-2xl border border-gray-100 p-5 animate-fade-in" style="animation-delay: 0.35s;">
            <h3 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
                📋 Hướng dẫn demo <span class="text-gray-400 font-normal">(~2 phút)</span>
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-indigo-50/50 rounded-xl p-3">
                    <p class="font-bold text-indigo-700 text-xs mb-2">👤 Ứng viên</p>
                    <ol class="list-decimal list-inside text-xs text-gray-600 space-y-1.5">
                        <li>Xem danh sách — <strong>Backend</strong> đã apply + có AI</li>
                        <li>Mở <strong>Data Analyst</strong> hoặc <strong>AI/ML</strong> → thử apply mới</li>
                        <li>Upload CV hoặc dùng hồ sơ có sẵn</li>
                        <li>Xem AI advisory ngay sau khi nộp</li>
                    </ol>
                    <div class="mt-2 p-2 bg-white rounded-lg border border-indigo-100 text-[11px] text-indigo-600">
                        💡 Các job đánh dấu <span class="font-semibold">✅ Đã ứng tuyển</span> có sẵn kết quả AI
                    </div>
                </div>
                <div class="bg-purple-50/50 rounded-xl p-3">
                    <p class="font-bold text-purple-700 text-xs mb-2">🏢 Nhà tuyển dụng</p>
                    <ol class="list-decimal list-inside text-xs text-gray-600 space-y-1.5">
                        <li>Dashboard → chọn job có badge <strong>🤖 AI sẵn</strong></li>
                        <li>Nhấn "AI Shortlist" → xem xếp hạng ứng viên</li>
                        <li>Thử "Tính lại AI" để chạy pipeline thật</li>
                        <li>Tạo job mới + kiểm tra JD quality</li>
                    </ol>
                    <div class="mt-2 p-2 bg-white rounded-lg border border-purple-100 text-[11px] text-purple-600">
                        💡 Job <strong>Backend</strong> có AI sẵn · <strong>Frontend</strong> cần chấm AI mới
                    </div>
                </div>
            </div>
        </div>

        
        <div class="mt-6 text-center animate-fade-in" style="animation-delay: 0.45s;">
            <form action="<?php echo e(route('demo.reset')); ?>" method="POST" onsubmit="return confirm('⚠️ Reset sẽ xoá toàn bộ dữ liệu và khôi phục về trạng thái ban đầu. Bạn chắc chắn?');">
                <?php echo csrf_field(); ?>
                <button type="submit" id="btn-reset-demo"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-gray-500 hover:text-red-600 bg-gray-50 hover:bg-red-50 border border-gray-200 hover:border-red-300 rounded-xl transition-all duration-300 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Reset Demo về dữ liệu gốc
                </button>
            </form>
            <p class="text-[11px] text-gray-400 mt-1">Xoá sạch và khôi phục 4 jobs + 4 applications demo</p>
        </div>

    </div>
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
<?php /**PATH D:\web\cpanel_public_html\backend\resources\views/demo/landing.blade.php ENDPATH**/ ?>