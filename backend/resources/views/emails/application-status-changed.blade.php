<x-mail::message>
# Xin chào {{ $candidate->name }}! 📬

Có cập nhật mới về đơn ứng tuyển của bạn cho vị trí:

<x-mail::panel>
**{{ $job->title }}** tại **{{ $company->name ?? 'Công ty' }}**
</x-mail::panel>

@php
$statusConfig = [
    'reviewing' => ['icon' => '👀', 'text' => 'Đang được xem xét', 'message' => 'Đội ngũ tuyển dụng đang xem xét hồ sơ của bạn. Chúng tôi sẽ liên hệ sớm nhất có thể.'],
    'shortlisted' => ['icon' => '⭐', 'text' => 'Đã được chọn', 'message' => 'Chúc mừng! Bạn đã được chọn vào vòng tiếp theo. Chúng tôi sẽ liên hệ để sắp xếp lịch phỏng vấn.'],
    'interviewed' => ['icon' => '🎤', 'text' => 'Đã phỏng vấn', 'message' => 'Cảm ơn bạn đã tham gia phỏng vấn. Chúng tôi đang đánh giá và sẽ phản hồi sớm.'],
    'offered' => ['icon' => '🎉', 'text' => 'Đã có offer', 'message' => 'Chúc mừng! Bạn đã được chọn và chúng tôi muốn gửi offer cho bạn. Vui lòng kiểm tra email để biết thêm chi tiết.'],
    'rejected' => ['icon' => '😔', 'text' => 'Không phù hợp', 'message' => 'Rất tiếc, hồ sơ của bạn chưa phù hợp với yêu cầu vị trí này. Đừng nản, hãy tiếp tục tìm kiếm cơ hội phù hợp!'],
];
$status = $statusConfig[$newStatus] ?? ['icon' => '📋', 'text' => $newStatus, 'message' => 'Trạng thái đơn ứng tuyển của bạn đã được cập nhật.'];
@endphp

## {{ $status['icon'] }} Trạng thái mới: {{ $status['text'] }}

{{ $status['message'] }}

@if($newStatus === 'shortlisted' || $newStatus === 'offered')
## 💡 Bước tiếp theo

- Kiểm tra email thường xuyên để nhận thông tin phỏng vấn
- Chuẩn bị các câu hỏi về công ty và vị trí
- Review lại CV và kinh nghiệm của bạn
@endif

@if($newStatus === 'rejected')
## 🌟 Đừng từ bỏ!

- Tiếp tục tìm kiếm các cơ hội việc làm khác phù hợp
- Cập nhật CV với các kỹ năng và kinh nghiệm mới
- Tham khảo feedback (nếu có) để cải thiện
@endif

<x-mail::button :url="config('app.url')" color="primary">
Tìm việc làm khác
</x-mail::button>

---

Cảm ơn bạn đã sử dụng **Smart AI Recruitment System**!

**Đội ngũ Smart AI Recruitment System**

<x-mail::subcopy>
Đây là email tự động từ hệ thống. Nếu bạn có thắc mắc, vui lòng liên hệ nhà tuyển dụng trực tiếp.
</x-mail::subcopy>
</x-mail::message>
