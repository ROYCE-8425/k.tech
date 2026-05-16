<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Hồ sơ ứng viên - {{ $application->candidate->name ?? 'Ứng viên' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 3px solid #4f46e5;
            margin-bottom: 25px;
        }
        .header h1 {
            font-size: 26px;
            color: #4f46e5;
            margin-bottom: 5px;
        }
        .header .subtitle {
            color: #6b7280;
            font-size: 12px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #4f46e5;
            padding-bottom: 8px;
            border-bottom: 2px solid #e0e7ff;
            margin-bottom: 12px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            width: 30%;
            padding: 8px 10px;
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-value {
            display: table-cell;
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        .match-score-box {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
            border-radius: 12px;
            margin-bottom: 25px;
        }
        .match-score-box .score {
            font-size: 48px;
            font-weight: bold;
            color: #4f46e5;
        }
        .match-score-box .label {
            font-size: 12px;
            color: #6366f1;
            margin-top: 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-submitted { background: #dbeafe; color: #1e40af; }
        .status-reviewing { background: #fef3c7; color: #92400e; }
        .status-shortlisted { background: #dcfce7; color: #166534; }
        .status-interviewed { background: #ede9fe; color: #5b21b6; }
        .status-offered { background: #dcfce7; color: #166534; }
        .status-hired { background: #dcfce7; color: #166534; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .cv-content {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            white-space: pre-wrap;
            font-size: 11px;
            line-height: 1.7;
            max-height: 400px;
            overflow: hidden;
        }
        .interview-item {
            background: #f8fafc;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #4f46e5;
        }
        .interview-item .date {
            font-weight: bold;
            color: #1f2937;
        }
        .interview-item .details {
            font-size: 11px;
            color: #6b7280;
            margin-top: 5px;
        }
        .notes-box {
            background: #fffbeb;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #fde68a;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            padding: 10px;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            background: white;
        }
        .two-column {
            display: table;
            width: 100%;
        }
        .column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }
        .column:last-child {
            padding-right: 0;
            padding-left: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📄 HỒ SƠ ỨNG VIÊN</h1>
        <div class="subtitle">Xuất ngày: {{ $generatedAt->format('d/m/Y H:i') }}</div>
    </div>

    @php
        $candidate = $application->candidate;
        $job = $application->job;
    @endphp

    <!-- CV Score -->
    @if($application->cv_manual_score !== null)
    <div class="match-score-box">
        <div class="score">{{ number_format($application->cv_manual_score, 0) }}</div>
        <div class="label">Điểm chấm CV</div>
    </div>
    @endif

    <!-- Thông tin cơ bản -->
    <div class="section">
        <div class="section-title">👤 THÔNG TIN CÁ NHÂN</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Họ và tên</div>
                <div class="info-value"><strong>{{ $candidate->name ?? 'N/A' }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Email</div>
                <div class="info-value">{{ $candidate->email ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Số điện thoại</div>
                <div class="info-value">{{ $candidate->phone ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Trạng thái</div>
                <div class="info-value">
                    <span class="status-badge status-{{ $application->status }}">
                        @switch($application->status)
                            @case('submitted') Đã nộp @break
                            @case('reviewing') Đang xem xét @break
                            @case('shortlisted') Vào vòng trong @break
                            @case('interviewed') Đã phỏng vấn @break
                            @case('offered') Có offer @break
                            @case('hired') Đã nhận @break
                            @case('rejected') Từ chối @break
                            @default {{ $application->status }}
                        @endswitch
                    </span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Ngày ứng tuyển</div>
                <div class="info-value">{{ $application->created_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>

    <!-- Vị trí ứng tuyển -->
    <div class="section">
        <div class="section-title">💼 VỊ TRÍ ỨNG TUYỂN</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Vị trí</div>
                <div class="info-value"><strong>{{ $job->title ?? 'N/A' }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Công ty</div>
                <div class="info-value">{{ $job->company->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Địa điểm</div>
                <div class="info-value">{{ $job->location ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Mức lương</div>
                <div class="info-value">
                    @if($job->salary_min && $job->salary_max)
                        {{ number_format($job->salary_min) }} - {{ number_format($job->salary_max) }} {{ $job->currency ?? 'VND' }}
                    @else
                        Thỏa thuận
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Kỹ năng & Kinh nghiệm -->
    @if($candidate->skills || $candidate->experience_years)
    <div class="section">
        <div class="section-title">🎯 KỸ NĂNG & KINH NGHIỆM</div>
        <div class="info-grid">
            @if($candidate->skills)
            <div class="info-row">
                <div class="info-label">Kỹ năng</div>
                <div class="info-value">{{ $candidate->skills }}</div>
            </div>
            @endif
            @if($candidate->experience_years)
            <div class="info-row">
                <div class="info-label">Kinh nghiệm</div>
                <div class="info-value">{{ $candidate->experience_years }} năm</div>
            </div>
            @endif
            @if($candidate->education)
            <div class="info-row">
                <div class="info-label">Học vấn</div>
                <div class="info-value">{{ $candidate->education }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Nội dung CV -->
    @if($application->cv_text)
    <div class="section">
        <div class="section-title">📋 NỘI DUNG CV</div>
        <div class="cv-content">{{ Str::limit($application->cv_text, 3000) }}</div>
    </div>
    @endif

    <!-- Ghi chú -->
    @if($application->notes)
    <div class="section">
        <div class="section-title">📝 GHI CHÚ CỦA NHÀ TUYỂN DỤNG</div>
        <div class="notes-box">
            {{ $application->notes }}
        </div>
    </div>
    @endif

    <!-- Lịch sử phỏng vấn -->
    @if($application->interviews && $application->interviews->count() > 0)
    <div class="section">
        <div class="section-title">📅 LỊCH SỬ PHỎNG VẤN</div>
        @foreach($application->interviews as $interview)
        <div class="interview-item">
            <div class="date">
                {{ \Carbon\Carbon::parse($interview->scheduled_at)->format('d/m/Y H:i') }}
                @if($interview->duration_minutes)
                    ({{ $interview->duration_minutes }} phút)
                @endif
            </div>
            <div class="details">
                <strong>Hình thức:</strong> 
                @switch($interview->type)
                    @case('onsite') Trực tiếp @break
                    @case('online') Online @break
                    @case('phone') Điện thoại @break
                    @default {{ $interview->type }}
                @endswitch
                @if($interview->location)
                    &nbsp;|&nbsp; <strong>Địa điểm:</strong> {{ $interview->location }}
                @endif
                <br>
                <strong>Trạng thái:</strong>
                @switch($interview->status)
                    @case('scheduled') Đã lên lịch @break
                    @case('completed') Hoàn thành @break
                    @case('cancelled') Đã hủy @break
                    @default {{ $interview->status }}
                @endswitch
            </div>
            @if($interview->notes)
            <div class="details" style="margin-top: 8px;">
                <strong>Ghi chú:</strong> {{ $interview->notes }}
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <div class="footer">
        IT Solo Leveling - Smart Recruitment System | Hồ sơ ứng viên được tạo tự động
    </div>
</body>
</html>
