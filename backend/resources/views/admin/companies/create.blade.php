<x-layouts.app title="Tạo công ty">
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
            <div class="w-24 h-24 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-4xl font-bold mx-auto mb-4 shadow-xl">
                🏢
            </div>
            <h1 class="text-3xl font-bold text-gray-900">Tạo công ty</h1>
            <p class="text-gray-600 mt-2">Nhập thông tin công ty để dùng khi đăng việc.</p>
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

            <form action="{{ route('admin.companies.store') }}" method="POST" enctype="multipart/form-data" class="p-8 space-y-8">
                @csrf

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
                    <h3 class="text-lg font-bold text-gray-900 mb-1">0) Chọn nhanh</h3>
                    <p class="text-sm text-gray-500 mb-6">Chọn ngành và công ty có sẵn (hoặc bấm “Ngoài ra” để nhập tay).</p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Ngành</label>
                            <select id="presetSector" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none transition-all">
                                <option value="it">CNTT</option>
                                <option value="media">Truyền thông / Marketing</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Công ty gợi ý</label>
                            <div class="flex gap-3">
                                <select id="presetCompany" class="flex-1 px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none transition-all">
                                    <option value="">— Chọn công ty —</option>
                                </select>
                                <button type="button" id="presetOther" class="px-5 py-3 rounded-xl bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition-all whitespace-nowrap">Ngoài ra</button>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Chọn xong bạn vẫn có thể chỉnh sửa lại thông tin bên dưới.</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">1) Thông tin cơ bản</h3>
                    <p class="text-sm text-gray-500 mb-6">Tên/website/địa chỉ của công ty.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tên công ty <span class="text-red-500">*</span></label>
                            <input id="companyName" type="text" name="name" value="{{ old('name') }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none transition-all" required>
                            @error('name')
                                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Website</label>
                            <input id="companyWebsite" type="text" name="website" value="{{ old('website') }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none transition-all" placeholder="https://...">
                            @error('website')
                                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Địa chỉ</label>
                        <input id="companyAddress" type="text" name="address" value="{{ old('address') }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none transition-all" placeholder="VD: Hà Nội">
                        @error('address')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">2) Giới thiệu</h3>
                    <p class="text-sm text-gray-500 mb-6">Mô tả ngắn về công ty (tùy chọn).</p>

                    <textarea id="companyDescription" name="description" rows="6" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none transition-all" placeholder="VD: Công ty sản phẩm, môi trường trẻ...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-2xl border border-gray-100 bg-gray-50/40 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">3) Logo</h3>
                    <p class="text-sm text-gray-500 mb-6">PNG/JPG (tối đa 5MB).</p>

                    <input type="file" name="logo" accept="image/*" class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-indigo-50 file:text-indigo-700 file:font-semibold hover:file:bg-indigo-600 hover:file:text-white">
                    @error('logo')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.companies.index') }}" class="px-5 py-3 rounded-xl bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition-all">Huỷ</a>
                    <button type="submit" class="px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold hover:shadow-lg transition-all">Lưu công ty</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const presets = {
                it: [
                    { name: 'FPT Software', website: 'https://fptsoftware.com', address: 'Việt Nam', description: 'Công ty dịch vụ CNTT và chuyển đổi số.' },
                    { name: 'Viettel Digital Services', website: 'https://viettel.com.vn', address: 'Việt Nam', description: 'Hệ sinh thái sản phẩm số và dịch vụ viễn thông.' },
                    { name: 'VNG Corporation', website: 'https://vng.com.vn', address: 'TP. Hồ Chí Minh', description: 'Công ty công nghệ với sản phẩm Internet và nền tảng số.' },
                    { name: 'VNPT', website: 'https://vnpt.com.vn', address: 'Việt Nam', description: 'Tập đoàn viễn thông và dịch vụ số.' },
                    { name: 'CMC Corporation', website: 'https://cmc.com.vn', address: 'Việt Nam', description: 'Tập đoàn công nghệ và dịch vụ số.' },
                    { name: 'NashTech', website: 'https://www.nashtechglobal.com', address: 'Việt Nam', description: 'Dịch vụ phát triển phần mềm và chuyển đổi số.' },
                    { name: 'KMS Technology', website: 'https://kms-technology.com', address: 'Việt Nam', description: 'Phát triển phần mềm và giải pháp công nghệ.' },
                    { name: 'Tiki', website: 'https://tiki.vn', address: 'TP. Hồ Chí Minh', description: 'Thương mại điện tử và nền tảng sản phẩm số.' },
                    { name: 'Shopee', website: 'https://shopee.vn', address: 'Việt Nam', description: 'Nền tảng thương mại điện tử.' },
                    { name: 'Grab', website: 'https://www.grab.com', address: 'Việt Nam', description: 'Nền tảng siêu ứng dụng và dịch vụ vận chuyển.' },
                    { name: 'MoMo', website: 'https://momo.vn', address: 'Việt Nam', description: 'Ví điện tử và dịch vụ tài chính số.' },
                    { name: 'Zalo (Zalo Group)', website: 'https://zalo.me', address: 'Việt Nam', description: 'Sản phẩm nhắn tin và nền tảng số.' },
                    { name: 'FPT Telecom', website: 'https://fpt.vn', address: 'Việt Nam', description: 'Dịch vụ internet và giải pháp số.' },
                    { name: 'Sapo', website: 'https://www.sapo.vn', address: 'Việt Nam', description: 'Nền tảng quản lý bán hàng và thương mại.' },
                    { name: 'Haravan', website: 'https://www.haravan.com', address: 'TP. Hồ Chí Minh', description: 'Nền tảng omnichannel và thương mại điện tử.' },
                    { name: 'TMA Solutions', website: 'https://www.tmasolutions.com', address: 'TP. Hồ Chí Minh', description: 'Gia công phần mềm và dịch vụ CNTT.' },
                    { name: 'Rikkeisoft', website: 'https://rikkeisoft.com', address: 'Việt Nam', description: 'Dịch vụ phát triển phần mềm.' },
                    { name: 'Axon Active', website: 'https://axonactive.com', address: 'Việt Nam', description: 'Phát triển phần mềm và dịch vụ công nghệ.' },
                    { name: 'Ahamove', website: 'https://ahamove.com', address: 'Việt Nam', description: 'Nền tảng logistics và giao hàng.' },
                    { name: 'Base.vn', website: 'https://base.vn', address: 'Việt Nam', description: 'Nền tảng SaaS cho doanh nghiệp.' },
                ],
                media: [
                    { name: 'VCCorp', website: 'https://vccorp.vn', address: 'Việt Nam', description: 'Hệ sinh thái truyền thông và quảng cáo số.' },
                    { name: 'Admicro (VCCorp)', website: 'https://admicro.vn', address: 'Việt Nam', description: 'Mạng quảng cáo và giải pháp marketing số.' },
                    { name: 'Yeah1 Group', website: 'https://yeah1.com', address: 'Việt Nam', description: 'Truyền thông, giải trí và nền tảng nội dung.' },
                    { name: 'DatVietVAC', website: 'https://datvietvac.vn', address: 'Việt Nam', description: 'Sản xuất nội dung và giải trí.' },
                    { name: 'Dentsu', website: 'https://www.dentsu.com', address: 'Việt Nam', description: 'Tập đoàn truyền thông và marketing toàn cầu.' },
                    { name: 'Ogilvy', website: 'https://www.ogilvy.com', address: 'Việt Nam', description: 'Agency quảng cáo và truyền thông.' },
                    { name: 'Publicis Groupe', website: 'https://www.publicisgroupe.com', address: 'Việt Nam', description: 'Tập đoàn truyền thông và marketing.' },
                    { name: 'WPP', website: 'https://www.wpp.com', address: 'Việt Nam', description: 'Tập đoàn dịch vụ marketing và truyền thông.' },
                    { name: 'GroupM', website: 'https://www.groupm.com', address: 'Việt Nam', description: 'Mạng lưới media agency.' },
                    { name: 'Mindshare', website: 'https://www.mindshareworld.com', address: 'Việt Nam', description: 'Media agency thuộc GroupM.' },
                    { name: 'MediaCom', website: 'https://www.mediacom.com', address: 'Việt Nam', description: 'Media agency thuộc GroupM.' },
                    { name: 'mCanvas', website: 'https://www.mcanvas.com', address: 'Việt Nam', description: 'Quảng cáo hiển thị và giải pháp dữ liệu.' },
                    { name: 'Novaon Digital', website: 'https://novaon.asia', address: 'Việt Nam', description: 'Giải pháp marketing và chuyển đổi số.' },
                    { name: 'CleverAds', website: 'https://cleverads.vn', address: 'Việt Nam', description: 'Giải pháp quảng cáo số và performance.' },
                    { name: 'Metub Network', website: 'https://metub.net', address: 'Việt Nam', description: 'MCN và mạng lưới sáng tạo nội dung.' },
                    { name: 'DigiPencil MVV', website: 'https://digipencil.vn', address: 'Việt Nam', description: 'Agency digital marketing.' },
                    { name: 'Goldsun Focus Media', website: 'https://goldsunfocusmedia.com', address: 'Việt Nam', description: 'Truyền thông và quảng cáo.' },
                    { name: 'Blue Communications', website: 'https://blue.com.vn', address: 'Việt Nam', description: 'PR, truyền thông và marketing.' },
                    { name: 'TBWA', website: 'https://tbwa.com', address: 'Việt Nam', description: 'Agency quảng cáo.' },
                    { name: 'VML', website: 'https://www.vml.com', address: 'Việt Nam', description: 'Agency digital và brand experience.' },
                ],
            };

            const sectorEl = document.getElementById('presetSector');
            const companyEl = document.getElementById('presetCompany');
            const otherBtn = document.getElementById('presetOther');

            const nameEl = document.getElementById('companyName');
            const websiteEl = document.getElementById('companyWebsite');
            const addressEl = document.getElementById('companyAddress');
            const descriptionEl = document.getElementById('companyDescription');

            let manualMode = false;

            function fillCompanySelect(sector) {
                const list = presets[sector] || [];
                companyEl.innerHTML = '<option value="">— Chọn công ty —</option>';
                list.forEach((c, idx) => {
                    const opt = document.createElement('option');
                    opt.value = String(idx);
                    opt.textContent = c.name;
                    companyEl.appendChild(opt);
                });
            }

            function applyPreset(sector, index) {
                const list = presets[sector] || [];
                const c = list[index];
                if (!c) return;

                // When a preset is selected, always fill/overwrite fields.
                manualMode = false;
                if (nameEl) nameEl.value = c.name || '';
                if (websiteEl) websiteEl.value = c.website || '';
                if (addressEl) addressEl.value = c.address || '';
                if (descriptionEl) descriptionEl.value = c.description || '';
            }

            sectorEl.addEventListener('change', function () {
                fillCompanySelect(sectorEl.value);
            });

            companyEl.addEventListener('change', function () {
                if (!companyEl.value) return;
                const idx = parseInt(companyEl.value, 10);
                if (Number.isNaN(idx)) return;
                applyPreset(sectorEl.value, idx);
            });

            otherBtn.addEventListener('click', function () {
                companyEl.value = '';
                manualMode = true;
                if (nameEl) nameEl.focus();
            });

            // If user starts typing after choosing "Ngoài ra", keep manual mode.
            [nameEl, websiteEl, addressEl, descriptionEl].forEach(function (el) {
                if (!el) return;
                el.addEventListener('input', function () {
                    if (companyEl.value === '') {
                        manualMode = true;
                    }
                });
            });

            // Init
            fillCompanySelect(sectorEl.value);
        })();
    </script>
</x-layouts.app>
