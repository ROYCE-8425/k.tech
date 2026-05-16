<x-mail::message>
# Xin chào {{ $candidate->name }}! 👋

Chúng tôi đã nhận được đơn ứng tuyển của bạn cho vị trí:

<x-mail::panel>
**{{ $job->title }}**

🏢 **Công ty:** {{ $company->name ?? 'Đang cập nhật' }}

📍 **Địa điểm:** {{ $job->location ?? 'Việt Nam' }}

💰 **Mức lương:** {{ $job->salary_min ? number_format($job->salary_min) . ' - ' . number_format($job->salary_max) . ' triệu' : 'Thỏa thuận' }}
</x-mail::panel>

## ⏳ Các bước tiếp theo

1. **Xem xét CV** - Đội ngũ tuyển dụng sẽ xem xét hồ sơ của bạn
2. **Liên hệ** - Nếu phù hợp, chúng tôi sẽ liên hệ trong 3-5 ngày làm việc
3. **Phỏng vấn** - Bạn sẽ được mời phỏng vấn nếu vượt qua vòng sơ tuyển

## 💡 Mẹo tăng cơ hội thành công

- Đảm bảo thông tin liên hệ (email, số điện thoại) luôn chính xác
- Chuẩn bị sẵn portfolio hoặc dự án để demo nếu cần
- Nghiên cứu về công ty trước khi phỏng vấn

<x-mail::button :url="config('app.url') . '/jobs/' . $job->id" color="primary">
Xem lại tin tuyển dụng
</x-mail::button>

---

Cảm ơn bạn đã quan tâm đến cơ hội việc làm tại **{{ $company->name ?? 'chúng tôi' }}**!

Chúc bạn may mắn! 🍀

**Đội ngũ Smart AI Recruitment System**

<x-mail::subcopy>
Đây là email tự động. Nếu bạn không nộp đơn này, vui lòng bỏ qua email này.
</x-mail::subcopy>
</x-mail::message>
