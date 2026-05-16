<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Danh sách ứng viên - {{ $job->title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #4f46e5;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 22px;
            color: #4f46e5;
            margin-bottom: 5px;
        }
        .header p {
            color: #666;
            font-size: 11px;
        }
        .job-info {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .job-info h2 {
            font-size: 16px;
            color: #1f2937;
            margin-bottom: 8px;
        }
        .job-info .detail {
            font-size: 11px;
            color: #6b7280;
        }
        .stats {
            display: flex;
            margin-bottom: 20px;
        }
        .stat-box {
            flex: 1;
            text-align: center;
            padding: 12px;
            background: #eef2ff;
            border-radius: 8px;
            margin-right: 10px;
        }
        .stat-box:last-child {
            margin-right: 0;
        }
        .stat-box .number {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5;
        }
        .stat-box .label {
            font-size: 10px;
            color: #6366f1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th {
            background: #4f46e5;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
        }
        td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        .match-score {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 10px;
        }
        .score-high { background: #dcfce7; color: #16a34a; }
        .score-medium { background: #fef3c7; color: #d97706; }
        .score-low { background: #fee2e2; color: #dc2626; }
        .status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 600;
        }
        .status-submitted { background: #dbeafe; color: #1e40af; }
        .status-reviewing { background: #fef3c7; color: #92400e; }
        .status-shortlisted { background: #dcfce7; color: #166534; }
        .status-interviewed { background: #ede9fe; color: #5b21b6; }
        .status-offered { background: #dcfce7; color: #166534; }
        .status-hired { background: #dcfce7; color: #166534; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
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
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 DANH SÁCH ỨNG VIÊN</h1>
        <p>Xuất ngày: {{ $generatedAt->format('d/m/Y H:i') }}</p>
    </div>

    <div class="job-info">
        <h2>{{ $job->title }}</h2>
        <div class="detail">
            <strong>Công ty:</strong> {{ $job->company->name ?? 'N/A' }} &nbsp;|&nbsp;
            <strong>Địa điểm:</strong> {{ $job->location ?? 'N/A' }} &nbsp;|&nbsp;
            <strong>Mức lương:</strong> 
            @if($job->salary_min && $job->salary_max)
                {{ number_format($job->salary_min) }} - {{ number_format($job->salary_max) }} {{ $job->currency ?? 'VND' }}
            @else
                Thỏa thuận
            @endif
        </div>
    </div>

    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="width: 25%; background: #eef2ff; text-align: center; padding: 12px; border-radius: 8px 0 0 8px;">
                <div style="font-size: 24px; font-weight: bold; color: #4f46e5;">{{ $applications->count() }}</div>
                <div style="font-size: 10px; color: #6366f1;">Tổng ứng viên</div>
            </td>
            <td style="width: 25%; background: #dcfce7; text-align: center; padding: 12px;">
                <div style="font-size: 24px; font-weight: bold; color: #16a34a;">{{ $applications->where('status', 'hired')->count() }}</div>
                <div style="font-size: 10px; color: #15803d;">Đã nhận</div>
            </td>
            <td style="width: 25%; background: #fef3c7; text-align: center; padding: 12px;">
                <div style="font-size: 24px; font-weight: bold; color: #d97706;">{{ $applications->whereIn('status', ['submitted', 'reviewing'])->count() }}</div>
                <div style="font-size: 10px; color: #b45309;">Chờ xử lý</div>
            </td>
            <td style="width: 25%; background: #fee2e2; text-align: center; padding: 12px; border-radius: 0 8px 8px 0;">
                <div style="font-size: 24px; font-weight: bold; color: #dc2626;">{{ $applications->where('status', 'rejected')->count() }}</div>
                <div style="font-size: 10px; color: #b91c1c;">Từ chối</div>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 25%;">Họ tên</th>
                <th style="width: 20%;">Email</th>
                <th style="width: 15%;">Điện thoại</th>
                <th style="width: 10%;">Điểm</th>
                <th style="width: 12%;">Trạng thái</th>
                <th style="width: 13%;">Ngày nộp</th>
            </tr>
        </thead>
        <tbody>
            @forelse($applications as $index => $application)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $application->candidate->name ?? 'N/A' }}</strong></td>
                    <td>{{ $application->candidate->email ?? 'N/A' }}</td>
                    <td>{{ $application->candidate->phone ?? 'N/A' }}</td>
                    <td>
                        @if($application->cv_manual_score !== null)
                            <span class="match-score {{ $application->cv_manual_score >= 70 ? 'score-high' : ($application->cv_manual_score >= 40 ? 'score-medium' : 'score-low') }}">
                                {{ number_format($application->cv_manual_score, 0) }}
                            </span>
                        @else
                            <span style="color: #9ca3af;">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="status status-{{ $application->status }}">
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
                    </td>
                    <td>{{ $application->created_at->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #9ca3af; padding: 30px;">
                        Chưa có ứng viên nào
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        IT Solo Leveling - Smart Recruitment System | Báo cáo được tạo tự động
    </div>
</body>
</html>
