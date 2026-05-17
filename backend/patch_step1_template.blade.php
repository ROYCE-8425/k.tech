{{-- ═══ STEP 1: CV Confirmation POPUP ═══ --}}
                                        <div x-show="step === 1" x-transition class="px-6 pb-4">
                                            <div class="text-center py-4">
                                                <div class="w-14 h-14 rounded-2xl mx-auto mb-3 flex items-center justify-center" style="background: linear-gradient(135deg, #eef2ff, #e0e7ff);">
                                                    <span class="text-2xl">📄</span>
                                                </div>
                                                <p class="text-sm font-semibold text-gray-700">AI đã đọc CV của bạn</p>
                                                <p class="text-xs text-gray-400 mt-1">Nhấn để xem và xác nhận thông tin</p>
                                                <button @click="$refs.cvModal.showModal()" type="button"
                                                    class="mt-4 w-full py-3 rounded-xl text-white font-bold text-sm transition-all hover:scale-[1.02]"
                                                    style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                                                    👁️ Xem thông tin CV đã trích xuất
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Fullscreen CV Preview Modal --}}
                                        <dialog x-ref="cvModal" class="fixed inset-0 w-full h-full max-w-full max-h-full m-0 p-0 bg-transparent z-50"
                                                style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);"
                                                @click.self="$refs.cvModal.close()">
                                            <div class="flex items-center justify-center min-h-screen p-4">
                                                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
                                                    {{-- Modal Header --}}
                                                    <div class="sticky top-0 z-10 p-5 rounded-t-3xl" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                                                        <div class="flex items-center justify-between">
                                                            <div>
                                                                <h2 class="text-lg font-bold text-white">🤖 AI đã quét CV của bạn</h2>
                                                                <p class="text-indigo-100 text-xs mt-1">Kiểm tra thông tin bên dưới</p>
                                                            </div>
                                                            <button @click="$refs.cvModal.close()" class="w-9 h-9 rounded-xl bg-white/15 text-white hover:bg-white/25 transition-all text-lg">✕</button>
                                                        </div>
                                                    </div>

                                                    {{-- Modal Body --}}
                                                    <div class="p-5 space-y-3">
                                                        <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 border border-gray-100">
                                                            <span class="text-xl">👤</span>
                                                            <div class="flex-1"><p class="text-[10px] text-gray-400 font-semibold uppercase">Họ tên</p><p class="text-base font-bold text-gray-800" x-text="cvInfo.name || 'Không phát hiện'"></p></div>
                                                        </div>
                                                        <div class="grid grid-cols-2 gap-2">
                                                            <div class="p-3 rounded-xl bg-gray-50 border border-gray-100"><span class="text-base">📧</span><p class="text-[10px] text-gray-400 font-semibold uppercase mt-1">Email</p><p class="text-xs font-semibold text-gray-700 truncate" x-text="cvInfo.email || '—'"></p></div>
                                                            <div class="p-3 rounded-xl bg-gray-50 border border-gray-100"><span class="text-base">📱</span><p class="text-[10px] text-gray-400 font-semibold uppercase mt-1">SĐT</p><p class="text-xs font-semibold text-gray-700" x-text="cvInfo.phone || 'Không tìm thấy'"></p></div>
                                                        </div>
                                                        <div class="p-3 rounded-xl bg-gray-50 border border-gray-100">
                                                            <div class="flex items-center gap-2 mb-2"><span class="text-base">🛠️</span><p class="text-[10px] text-gray-400 font-semibold uppercase">Kỹ năng phát hiện từ CV</p></div>
                                                            <div class="flex flex-wrap gap-1.5">
                                                                <template x-if="cvInfo.skills && cvInfo.skills.length > 0"><template x-for="skill in cvInfo.skills" :key="skill"><span class="px-2.5 py-1 rounded-lg text-xs font-semibold" style="background: #ede9fe; color: #6d28d9;" x-text="skill"></span></template></template>
                                                                <template x-if="!cvInfo.skills || cvInfo.skills.length === 0"><span class="text-xs text-gray-400 italic">Không phát hiện kỹ năng từ CV</span></template>
                                                            </div>
                                                        </div>
                                                        <div class="grid grid-cols-2 gap-2">
                                                            <div class="p-3 rounded-xl bg-gray-50 border border-gray-100"><span class="text-lg">📊</span><p class="text-[10px] text-gray-400 font-semibold uppercase mt-1">Kinh nghiệm</p><p class="text-sm font-bold text-gray-800" x-text="cvInfo.experience_years ? cvInfo.experience_years + ' năm' : 'Chưa rõ'"></p></div>
                                                            <div class="p-3 rounded-xl bg-gray-50 border border-gray-100"><span class="text-lg">🎓</span><p class="text-[10px] text-gray-400 font-semibold uppercase mt-1">Học vấn</p><p class="text-sm font-bold text-gray-800 leading-tight" x-text="cvInfo.education || 'Chưa rõ'"></p></div>
                                                        </div>
                                                        <div class="p-3 rounded-xl bg-gray-50 border border-gray-100">
                                                            <p class="text-[10px] text-gray-400 font-semibold uppercase mb-1.5">📝 Nội dung CV trích xuất</p>
                                                            <p class="text-xs text-gray-600 leading-relaxed max-h-32 overflow-y-auto" x-text="cvInfo.summary ? cvInfo.summary + '...' : 'Không trích xuất được'"></p>
                                                        </div>
                                                    </div>

                                                    {{-- Modal Footer --}}
                                                    <div class="sticky bottom-0 p-5 bg-white border-t border-gray-100 rounded-b-3xl">
                                                        <button @click="$refs.cvModal.close(); confirmCv();" type="button"
                                                            class="w-full py-3.5 rounded-xl text-white font-bold text-sm transition-all hover:scale-[1.02] hover:shadow-lg"
                                                            style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                                                            ✅ Xác nhận & tiếp tục chấm điểm AI
                                                        </button>
                                                        <p class="text-center text-[10px] text-gray-400 mt-2">AI sẽ chấm điểm sau khi bạn xác nhận</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </dialog>
