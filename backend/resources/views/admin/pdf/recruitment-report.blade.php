<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Báo cáo tuyển dụng {{ $fromDate }} - {{ $toDate }}</title>
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
            padding: 25px 0;
            border-bottom: 3px solid #4f46e5;
            margin-bottom: 30px;
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
            margin: -20px -20px 30px -20px;
            padding: 30px 20px;
        }
        .header h1 {
            font-size: 28px;
            color: #4f46e5;
            margin-bottom: 8px;
        }
        .header .period {
            font-size: 14px;
            color: #6366f1;
            font-weight: 600;
        }
        .header .subtitle {
            color: #9ca3af;
            font-size: 11px;
            margin-top: 5px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 15px;
        }
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .stats-row {
            display: table-row;
        }
        .stat-box {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 15px 10px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
        }
        .stat-box .number {
            font-size: 32px;
            font-weight: bold;
            color: #4f46e5;
        }
        .stat-box .label {
            font-size: 11px;
            color: #6b7280;
            margin-top: 5px;
        }
        .stat-box.highlight {
            background: #eef2ff;
        }
        .stat-box.success {
            background: #dcfce7;
        }
        .stat-box.success .number {
            color: #16a34a;
        }
        .stat-box.warning {
            background: #fef3c7;
        }
        .stat-box.warning .number {
            color: #d97706;
        }
        .stat-box.danger {
            background: #fee2e2;
        }
        .stat-box.danger .number {
            color: #dc2626;
        }
        table {
            width: 100%;
            border-collapse: collapse;
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
        .rank-badge {
            display: inline-block;
            width: 24px;
            height: 24px;
            line-height: 24px;
            text-align: center;
            border-radius: 50%;
            font-weight: bold;
            font-size: 11px;
            color: white;
        }
        .rank-1 { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); }
        .rank-2 { background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%); }
        .rank-3 { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        .rank-default { background: #6366f1; }
        .chart-container {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .chart-bar-container {
            display: table;
            width: 100%;
            height: 150px;
        }
        .chart-bar-wrapper {
            display: table-cell;
            vertical-align: bottom;
            text-align: center;
            padding: 0 3px;
        }
        .chart-bar {
            background: linear-gradient(to top, #4f46e5, #818cf8);
            width: 100%;
            border-radius: 4px 4px 0 0;
            min-height: 5px;
        }
        .chart-label {
            font-size: 9px;
            color: #6b7280;
            margin-top: 5px;
            transform: rotate(-45deg);
            display: block;
        }
        .chart-value {
            font-size: 10px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 5px;
        }
        .summary-box {
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }
        .summary-box h3 {
            font-size: 14px;
            color: #4338ca;
            margin-bottom: 10px;
        }
        .summary-box p {
            font-size: 12px;
            color: #4b5563;
            line-height: 1.8;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 BÁO CÁO TUYỂN DỤNG</h1>
        <div class="period">Từ {{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }} đến {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }}</div>
        <div class="subtitle">Xuất ngày: {{ $generatedAt->format('d/m/Y H:i') }}</div>
    </div>

    <!-- Thống kê tổng quan -->
    <div class="section">
        <div class="section-title">📈 THỐNG KÊ TỔNG QUAN</div>
        <div class="stats-grid">
            <div class="stats-row">
                <div class="stat-box highlight">
                    <div class="number">{{ $stats['totalJobs'] }}</div>
                    <div class="label">Việc làm mới</div>
                </div>
                <div class="stat-box">
                    <div class="number">{{ $stats['totalApplications'] }}</div>
                    <div class="label">Tổng đơn ứng tuyển</div>
                </div>
                <div class="stat-box success">
                    <div class="number">{{ $stats['acceptedApplications'] }}</div>
                    <div class="label">Đã nhận</div>
                </div>
                <div class="stat-box danger">
                    <div class="number">{{ $stats['rejectedApplications'] }}</div>
                    <div class="label">Từ chối</div>
                </div>
            </div>
        </div>
        <div class="stats-grid">
            <div class="stats-row">
                <div class="stat-box warning">
                    <div class="number">{{ $stats['pendingApplications'] }}</div>
                    <div class="label">Chờ xử lý</div>
                </div>
                <div class="stat-box">
                    <div class="number">{{ $stats['interviewsScheduled'] }}</div>
                    <div class="label">Phỏng vấn đã lên lịch</div>
                </div>
                <div class="stat-box success">
                    <div class="number">{{ $stats['interviewsCompleted'] }}</div>
                    <div class="label">Phỏng vấn hoàn thành</div>
                </div>
                <div class="stat-box highlight">
                    <div class="number">
                        {{ $stats['totalApplications'] > 0 ? round(($stats['acceptedApplications'] / $stats['totalApplications']) * 100) : 0 }}%
                    </div>
                    <div class="label">Tỷ lệ nhận việc</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ ứng tuyển theo ngày -->
    @if(count($applicationsByDay) > 0 && count($applicationsByDay) <= 31)
    <div class="section">
        <div class="section-title">📅 ĐƠN ỨNG TUYỂN THEO NGÀY</div>
        <div class="chart-container">
            @php
                $maxCount = max(array_column($applicationsByDay, 'count'));
                $maxCount = $maxCount > 0 ? $maxCount : 1;
            @endphp
            <div class="chart-bar-container">
                @foreach($applicationsByDay as $day)
                    @php
                        $height = ($day['count'] / $maxCount) * 120;
                    @endphp
                    <div class="chart-bar-wrapper">
                        <div class="chart-value">{{ $day['count'] }}</div>
                        <div class="chart-bar" style="height: {{ max($height, 5) }}px;"></div>
                    </div>
                @endforeach
            </div>
            <div style="display: table; width: 100%; margin-top: 10px;">
                @foreach($applicationsByDay as $day)
                    <div style="display: table-cell; text-align: center; font-size: 9px; color: #6b7280;">
                        {{ $day['date'] }}
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Top việc làm -->
    @if($topJobs->count() > 0)
    <div class="section">
        <div class="section-title">🏆 TOP VIỆC LÀM ĐƯỢC ỨNG TUYỂN NHIỀU NHẤT</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">Hạng</th>
                    <th style="width: 40%;">Vị trí</th>
                    <th style="width: 30%;">Công ty</th>
                    <th style="width: 22%;">Số ứng viên</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topJobs as $index => $job)
                    <tr>
                        <td>
                            <span class="rank-badge {{ $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : 'rank-default')) }}">
                                {{ $index + 1 }}
                            </span>
                        </td>
                        <td><strong>{{ $job->title }}</strong></td>
                        <td>{{ $job->company->name ?? 'N/A' }}</td>
                        <td>
                            <strong style="color: #4f46e5; font-size: 14px;">{{ $job->applications_count }}</strong>
                            <span style="color: #6b7280;"> ứng viên</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Tổng kết -->
    <div class="summary-box">
        <h3>📝 TÓM TẮT</h3>
        <p>
            Trong giai đoạn từ <strong>{{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }}</strong> đến <strong>{{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }}</strong>, 
            hệ thống đã ghi nhận <strong>{{ $stats['totalApplications'] }}</strong> đơn ứng tuyển cho <strong>{{ $stats['totalJobs'] }}</strong> vị trí việc làm.
            <br><br>
            Tỷ lệ nhận việc đạt <strong>{{ $stats['totalApplications'] > 0 ? round(($stats['acceptedApplications'] / $stats['totalApplications']) * 100) : 0 }}%</strong>.
            Có <strong>{{ $stats['interviewsCompleted'] }}</strong> buổi phỏng vấn đã hoàn thành trong tổng số <strong>{{ $stats['interviewsScheduled'] }}</strong> buổi được lên lịch.
            @if($stats['pendingApplications'] > 0)
                <br><br>
                ⚠️ Hiện còn <strong>{{ $stats['pendingApplications'] }}</strong> đơn ứng tuyển đang chờ xử lý.
            @endif
        </p>
    </div>

    <div class="footer">
        IT Solo Leveling - Smart Recruitment System | {{ $generatedAt->format('d/m/Y H:i') }}
    </div>
</body>
</html>
