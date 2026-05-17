<?php
/**
 * Patch: Replace "Already Applied" section in show.blade.php with 3-step AI review flow.
 * Run: php patch_show_blade.php
 */

$file = __DIR__ . '/resources/views/jobs/show.blade.php';
$content = file_get_contents($file);
$contentNorm = str_replace("\r\n", "\n", $content);

// Find the section boundaries
$startMarker = '@if(!empty($alreadyApplied) && Auth::user()->role === \'candidate\')';
$endMarker = '@elseif(Auth::user()->role === \'candidate\')';

$startPos = strpos($contentNorm, $startMarker);
$endPos = strpos($contentNorm, $endMarker);

if ($startPos === false || $endPos === false) {
    echo "❌ Could not find section boundaries\n";
    echo "Start: " . ($startPos !== false ? 'found' : 'NOT FOUND') . "\n";
    echo "End: " . ($endPos !== false ? 'found' : 'NOT FOUND') . "\n";
    exit(1);
}

// Find the line start of the @if
$lineStart = strrpos(substr($contentNorm, 0, $startPos), "\n");
$lineStart = $lineStart !== false ? $lineStart + 1 : $startPos;

// Find indentation
$indent = '';
$lineContent = substr($contentNorm, $lineStart, $startPos - $lineStart);
if (preg_match('/^(\s+)/', $lineContent, $m)) {
    $indent = $m[1];
}

$newSection = <<<'BLADE'
                            @if(!empty($alreadyApplied) && Auth::user()->role === 'candidate')
                                <!-- 3-Step AI CV Review Flow -->
                                <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 overflow-hidden" id="apply-form">
                                    @php
                                        $activeFollowupFields = session('ai_followup_fields', $followupFields ?? []);
                                        $activeAdvisory = session('ai_advisory', $persistedAdvisory ?? null);
                                        $cvExtracted = session('cv_extracted_info');
                                        $isJustApplied = session('status') && $cvExtracted;
                                        
                                        if ($activeAdvisory && isset($activeAdvisory['fit_score'])) {
                                            $rawScore = $activeAdvisory['fit_score'];
                                            $activeAdvisory['fit_score'] = $rawScore > 10 ? $rawScore / 10 : $rawScore;
                                        }

                                        // Mapping follow-up fields to AI questions
                                        $chatQuestions = [
                                            'years_experience' => ['q' => '⏱️ Bạn có bao nhiêu năm kinh nghiệm làm việc trong lĩnh vực này?', 'type' => 'number', 'placeholder' => 'VD: 3'],
                                            'key_skills' => ['q' => '🛠️ Hãy liệt kê vài kỹ năng công nghệ chính của bạn (cách nhau bằng dấu phẩy).', 'type' => 'text', 'placeholder' => 'VD: PHP, Laravel, MySQL'],
                                            'education_level' => ['q' => '🎓 Trình độ học vấn cao nhất của bạn hiện tại là gì?', 'type' => 'select', 'options' => ['THPT', 'Trung cấp', 'Cao đẳng', 'Đại học', 'Thạc sĩ', 'Tiến sĩ', 'Bootcamp/Tự học']],
                                            'primary_role' => ['q' => '💼 Đâu là vai trò chính mà bạn tự tin nhất?', 'type' => 'select', 'options' => ['Backend Developer', 'Frontend Developer', 'Fullstack Developer', 'Mobile Developer', 'QA/Tester', 'DevOps Engineer', 'Data Analyst', 'ML Engineer', 'Product/Business Analyst']],
                                            'english_level' => ['q' => '🇬🇧 Khả năng tiếng Anh của bạn đang ở mức nào?', 'type' => 'select', 'options' => ['Cơ bản (A1-A2)', 'Trung cấp (B1-B2)', 'Nâng cao (C1-C2)', 'Bản ngữ / Native']],
                                            'phone' => ['q' => '📱 Số điện thoại liên hệ?', 'type' => 'text', 'placeholder' => 'VD: 0912 345 678'],
                                        ];
                                        $activeQuestions = [];
                                        foreach($activeFollowupFields as $field) {
                                            if(isset($chatQuestions[$field])) {
                                                $activeQuestions[] = array_merge(['id' => $field], $chatQuestions[$field]);
                                            }
                                        }
                                        
                                        // Determine initial step
                                        $initialStep = 3; // default: show result
                                        if ($isJustApplied && $cvExtracted) {
                                            $initialStep = 1; // just applied: show CV confirmation
                                        } elseif ($isJustApplied && !empty($activeFollowupFields)) {
                                            $initialStep = 2; // has follow-up questions
                                        }
                                    @endphp

                                    <div x-data="{
                                        step: {{ $initialStep }},
                                        cvInfo: {{ json_encode($cvExtracted ?? []) }},
                                        editMode: false,
                                        questions: {{ json_encode($activeQuestions) }},
                                        currentQIndex: 0,
                                        messages: [],
                                        currentInput: '',
                                        isSubmitting: false,

                                        initChat() {
                                            if (this.questions.length === 0) {
                                                this.step = 3;
                                                return;
                                            }
                                            this.messages = [
                                                { type: 'ai', text: '🤖 Xin chào! Tôi cần hỏi thêm <strong>' + this.questions.length + ' câu</strong> để đánh giá chính xác hơn.' }
                                            ];
                                            setTimeout(() => this.askNextQuestion(), 500);
                                        },

                                        askNextQuestion() {
                                            if (this.currentQIndex < this.questions.length) {
                                                const q = this.questions[this.currentQIndex];
                                                this.messages.push({ type: 'ai', text: q.q, field: q.id });
                                                this.scrollChat();
                                            } else {
                                                this.messages.push({ type: 'ai', text: '✅ Cảm ơn! Đang gửi thông tin cho AI chấm điểm...' });
                                                this.scrollChat();
                                                setTimeout(() => this.submitFollowup(), 500);
                                            }
                                        },

                                        answer(text, value = null) {
                                            if (!text) return;
                                            const val = value !== null ? value : text;
                                            this.messages.push({ type: 'user', text: text });
                                            const fieldId = this.questions[this.currentQIndex].id;
                                            const inputEl = document.getElementById('followup_' + fieldId);
                                            if (inputEl) inputEl.value = val;
                                            this.currentInput = '';
                                            this.currentQIndex++;
                                            this.scrollChat();
                                            setTimeout(() => this.askNextQuestion(), 400);
                                        },

                                        scrollChat() {
                                            this.$nextTick(() => {
                                                const box = document.getElementById('ai-chat-box');
                                                if (box) box.scrollTop = box.scrollHeight;
                                            });
                                        },

                                        submitFollowup() {
                                            this.isSubmitting = true;
                                            document.getElementById('followupForm_{{ $job->id }}').submit();
                                        },

                                        confirmCv() {
                                            if (this.questions.length > 0) {
                                                this.step = 2;
                                                this.$nextTick(() => this.initChat());
                                            } else {
                                                this.step = 3;
                                            }
                                        }
                                    }">
                                        {{-- ═══ STEP INDICATOR ═══ --}}
                                        <div class="px-6 pt-5 pb-3">
                                            <div class="flex items-center justify-center gap-1 text-xs">
                                                <template x-for="s in [1,2,3]" :key="s">
                                                    <div class="flex items-center">
                                                        <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs transition-all duration-300"
                                                             :class="step >= s ? 'text-white' : 'bg-gray-100 text-gray-400'"
                                                             :style="step >= s ? 'background: linear-gradient(135deg, #6366f1, #8b5cf6)' : ''">
                                                            <span x-text="s"></span>
                                                        </div>
                                                        <div x-show="s < 3" class="w-8 h-0.5 mx-1 transition-all duration-300"
                                                             :class="step > s ? 'bg-indigo-400' : 'bg-gray-200'"></div>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="flex justify-between text-[10px] text-gray-400 mt-1 px-1">
                                                <span :class="step === 1 && 'text-indigo-600 font-bold'">Xác nhận CV</span>
                                                <span :class="step === 2 && 'text-indigo-600 font-bold'">Hỏi thêm</span>
                                                <span :class="step === 3 && 'text-indigo-600 font-bold'">Kết quả AI</span>
                                            </div>
                                        </div>

                                        {{-- ═══ STEP 1: CV Confirmation ═══ --}}
                                        <div x-show="step === 1" x-transition class="px-6 pb-6">
                                            <div class="p-5 rounded-2xl border border-indigo-100" style="background: linear-gradient(135deg, #eef2ff, #e0e7ff);">
                                                <div class="flex items-center gap-2 mb-3">
                                                    <span class="text-lg">📄</span>
                                                    <h3 class="font-bold text-gray-800">AI đã đọc CV của bạn</h3>
                                                </div>
                                                <p class="text-xs text-gray-500 mb-4">Kiểm tra thông tin dưới đây. Nếu sai, nhấn "Sửa" để chỉnh sửa.</p>

                                                <div class="space-y-2.5">
                                                    {{-- Name --}}
                                                    <div class="flex items-center gap-3 p-2.5 rounded-xl bg-white border border-gray-100">
                                                        <span class="text-base">👤</span>
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-[10px] text-gray-400 font-semibold uppercase">Họ tên</p>
                                                            <p class="text-sm font-bold text-gray-800" x-text="cvInfo.name || 'Chưa rõ'"></p>
                                                        </div>
                                                    </div>

                                                    {{-- Skills --}}
                                                    <div class="p-2.5 rounded-xl bg-white border border-gray-100">
                                                        <div class="flex items-center gap-2 mb-1.5">
                                                            <span class="text-base">🛠️</span>
                                                            <p class="text-[10px] text-gray-400 font-semibold uppercase">Kỹ năng phát hiện</p>
                                                        </div>
                                                        <div class="flex flex-wrap gap-1">
                                                            <template x-if="cvInfo.skills && cvInfo.skills.length > 0">
                                                                <template x-for="skill in cvInfo.skills" :key="skill">
                                                                    <span class="px-2 py-0.5 rounded-lg text-xs font-medium" style="background: #ede9fe; color: #6d28d9;" x-text="skill"></span>
                                                                </template>
                                                            </template>
                                                            <template x-if="!cvInfo.skills || cvInfo.skills.length === 0">
                                                                <span class="text-xs text-gray-400 italic">Không phát hiện được từ CV</span>
                                                            </template>
                                                        </div>
                                                        <template x-if="cvInfo.missing_skills && cvInfo.missing_skills.length > 0">
                                                            <div class="mt-2 pt-2 border-t border-gray-50">
                                                                <p class="text-[10px] text-amber-500 font-semibold mb-1">⚠️ Kỹ năng yêu cầu chưa thấy trong CV:</p>
                                                                <div class="flex flex-wrap gap-1">
                                                                    <template x-for="ms in cvInfo.missing_skills" :key="ms">
                                                                        <span class="px-2 py-0.5 rounded-lg text-xs font-medium" style="background: #fef3c7; color: #92400e;" x-text="ms"></span>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>

                                                    {{-- Experience + Education row --}}
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <div class="p-2.5 rounded-xl bg-white border border-gray-100">
                                                            <span class="text-base">📊</span>
                                                            <p class="text-[10px] text-gray-400 font-semibold uppercase mt-1">Kinh nghiệm</p>
                                                            <p class="text-sm font-bold text-gray-800">
                                                                <span x-text="cvInfo.experience_years ? cvInfo.experience_years + ' năm' : 'Chưa rõ'"></span>
                                                            </p>
                                                        </div>
                                                        <div class="p-2.5 rounded-xl bg-white border border-gray-100">
                                                            <span class="text-base">🎓</span>
                                                            <p class="text-[10px] text-gray-400 font-semibold uppercase mt-1">Học vấn</p>
                                                            <p class="text-sm font-bold text-gray-800" x-text="cvInfo.education || 'Chưa rõ'"></p>
                                                        </div>
                                                    </div>

                                                    {{-- Summary --}}
                                                    <template x-if="cvInfo.summary">
                                                        <div class="p-2.5 rounded-xl bg-white border border-gray-100">
                                                            <p class="text-[10px] text-gray-400 font-semibold uppercase mb-1">📝 Tóm tắt CV</p>
                                                            <p class="text-xs text-gray-600 leading-relaxed" x-text="cvInfo.summary + '...'"></p>
                                                        </div>
                                                    </template>
                                                </div>

                                                {{-- Action buttons --}}
                                                <div class="flex gap-2 mt-4">
                                                    <button @click="confirmCv()" type="button"
                                                        class="flex-1 py-3 rounded-xl text-white font-bold text-sm transition-all hover:scale-[1.02]"
                                                        style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                                                        ✅ Xác nhận đúng
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- ═══ STEP 2: AI Chat Follow-up ═══ --}}
                                        <div x-show="step === 2" x-transition class="flex flex-col">
                                            {{-- Chat Header --}}
                                            <div class="p-4 flex items-center gap-3" style="background: linear-gradient(to right, #6366f1, #9333ea);">
                                                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-xl">🤖</div>
                                                <div>
                                                    <h2 class="text-lg font-bold text-white leading-tight">Trợ lý AI</h2>
                                                    <p class="text-indigo-100 text-xs">Hỏi thêm thông tin để chấm điểm chính xác</p>
                                                </div>
                                            </div>

                                            {{-- Chat Messages --}}
                                            <div id="ai-chat-box" class="p-5 bg-gray-50/50 space-y-3 max-h-[350px] overflow-y-auto scroll-smooth">
                                                <template x-for="(msg, idx) in messages" :key="idx">
                                                    <div class="flex w-full" :class="msg.type === 'user' ? 'justify-end' : 'justify-start'">
                                                        <template x-if="msg.type === 'ai'">
                                                            <div class="flex gap-2 max-w-[85%]">
                                                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0 mt-1" style="background: linear-gradient(135deg, #818cf8, #a855f7);">AI</div>
                                                                <div class="bg-white px-3 py-2.5 rounded-2xl rounded-tl-sm shadow-sm border border-gray-100 text-sm text-gray-700" x-html="msg.text"></div>
                                                            </div>
                                                        </template>
                                                        <template x-if="msg.type === 'user'">
                                                            <div class="px-3 py-2.5 rounded-2xl rounded-tr-sm shadow-sm text-sm text-white max-w-[85%]" style="background: #6366f1;" x-text="msg.text"></div>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>

                                            {{-- Chat Input --}}
                                            <div class="p-4 bg-white border-t border-gray-100" x-show="currentQIndex < questions.length && !isSubmitting">
                                                <template x-if="questions[currentQIndex]">
                                                    <div>
                                                        <template x-if="questions[currentQIndex].type === 'text' || questions[currentQIndex].type === 'number'">
                                                            <div class="flex gap-2">
                                                                <input :type="questions[currentQIndex].type"
                                                                       x-model="currentInput"
                                                                       @keydown.enter="answer(currentInput)"
                                                                       :placeholder="questions[currentQIndex].placeholder"
                                                                       class="flex-1 px-4 py-2.5 bg-gray-100 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 rounded-xl transition-all text-sm outline-none border border-transparent">
                                                                <button @click="answer(currentInput)" :disabled="!currentInput"
                                                                        class="w-10 h-10 rounded-xl text-white flex items-center justify-center transition-colors disabled:opacity-50"
                                                                        style="background: #6366f1;">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                                                </button>
                                                            </div>
                                                        </template>
                                                        <template x-if="questions[currentQIndex].type === 'select'">
                                                            <div class="flex flex-wrap gap-2">
                                                                <template x-for="opt in questions[currentQIndex].options">
                                                                    <button @click="answer(opt, opt)" class="px-3 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white rounded-xl text-sm font-medium transition-colors border border-indigo-100" x-text="opt"></button>
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>

                                            {{-- Hidden Form --}}
                                            <form action="{{ route('jobs.ai-followup', $job->id) }}" method="POST" id="followupForm_{{ $job->id }}" class="hidden">
                                                @csrf
                                                @foreach(['phone', 'years_experience', 'primary_role', 'key_skills', 'education_level', 'english_level', 'portfolio_url', 'github_url'] as $field)
                                                    <input type="hidden" name="followup_{{ $field }}" id="followup_{{ $field }}">
                                                @endforeach
                                            </form>
                                        </div>

                                        {{-- ═══ STEP 3: AI Result ═══ --}}
                                        <div x-show="step === 3" x-transition>
                                            @if($activeAdvisory && isset($activeAdvisory['fit_score']))
                                                @php
                                                    $score = $activeAdvisory['fit_score'];
                                                    if ($score >= 8) { $label = 'Xuất sắc'; $emoji = '🏆'; $color = '#10b981'; $bg = '#d1fae5'; }
                                                    elseif ($score >= 6.5) { $label = 'Tốt'; $emoji = '👍'; $color = '#3b82f6'; $bg = '#dbeafe'; }
                                                    elseif ($score >= 5) { $label = 'Khá'; $emoji = '✅'; $color = '#f59e0b'; $bg = '#fef3c7'; }
                                                    else { $label = 'Cần cải thiện'; $emoji = '💪'; $color = '#ef4444'; $bg = '#fee2e2'; }
                                                @endphp
                                                <div class="p-6">
                                                    <div class="text-center mb-4">
                                                        <div class="text-4xl mb-2">{{ $emoji }}</div>
                                                        <div class="flex items-baseline justify-center gap-1">
                                                            <span class="text-4xl font-black" style="color: {{ $color }};">{{ number_format($score, 1) }}</span>
                                                            <span class="text-xl font-bold text-gray-400">/10</span>
                                                        </div>
                                                        <p class="text-sm font-bold mt-1" style="color: {{ $color }};">{{ $label }}</p>
                                                    </div>

                                                    @if(!empty($activeAdvisory['matched_skills']))
                                                        <div class="p-3 rounded-xl border border-gray-100 mb-3">
                                                            <p class="text-xs font-semibold text-gray-500 mb-2">✅ Kỹ năng khớp</p>
                                                            <div class="flex flex-wrap gap-1">
                                                                @foreach($activeAdvisory['matched_skills'] as $skill)
                                                                    <span class="px-2 py-0.5 rounded-lg text-xs font-medium" style="background: #d1fae5; color: #065f46;">{{ $skill }}</span>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif

                                                    @if(!empty($activeAdvisory['missing_skills']))
                                                        <div class="p-3 rounded-xl border border-gray-100 mb-3">
                                                            <p class="text-xs font-semibold text-gray-500 mb-2">❌ Kỹ năng thiếu</p>
                                                            <div class="flex flex-wrap gap-1">
                                                                @foreach($activeAdvisory['missing_skills'] as $skill)
                                                                    <span class="px-2 py-0.5 rounded-lg text-xs font-medium" style="background: #fee2e2; color: #991b1b;">{{ $skill }}</span>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="p-6 text-center">
                                                    <div class="text-4xl mb-2">📝</div>
                                                    <p class="text-sm font-semibold text-gray-700">Đơn đã được ghi nhận</p>
                                                    <p class="text-xs text-gray-500 mt-1">Nhà tuyển dụng sẽ xem xét sớm.</p>
                                                </div>
                                            @endif

                                            <div class="px-6 pb-6 space-y-3">
                                                <a href="{{ route('candidate.applications') }}" class="inline-flex items-center justify-center w-full py-3 rounded-xl text-white font-semibold transition-all hover:scale-[1.02]" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                                                    Xem đơn ứng tuyển của tôi
                                                </a>
                                                <a href="{{ route('home') }}" class="inline-flex items-center justify-center w-full py-3 rounded-xl bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition-all">
                                                    Tìm việc khác
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
BLADE;

// Replace the section
$beforeSection = substr($contentNorm, 0, $lineStart);
$afterSection = substr($contentNorm, $endPos);

// Find the actual start of the @elseif line (need its indentation too)
$afterSection = $indent . $afterSection;

$contentNorm = $beforeSection . $newSection . "\n" . $afterSection;

// Write back with CRLF
$contentFinal = str_replace("\n", "\r\n", $contentNorm);
$contentFinal = str_replace("\r\r\n", "\r\n", $contentFinal);

file_put_contents($file, $contentFinal);
echo "✅ show.blade.php patched with 3-step AI review flow\n";
echo "Lines: " . substr_count($contentFinal, "\n") . "\n";
