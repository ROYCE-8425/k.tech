<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mã xác thực đăng nhập</title>
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f7fa; margin: 0; padding: 20px;">
    <div style="max-width: 500px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); padding: 30px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 700;">
                🔐 Xác thực 2 yếu tố
            </h1>
            <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 14px;">
                IT Solo Leveling - Smart Recruitment System
            </p>
        </div>
        
        <!-- Content -->
        <div style="padding: 40px 30px; text-align: center;">
            <p style="color: #374151; font-size: 16px; margin: 0 0 10px 0;">
                Xin chào <strong>{{ $user->name }}</strong>,
            </p>
            <p style="color: #6b7280; font-size: 14px; margin: 0 0 30px 0;">
                Đây là mã xác thực để hoàn tất đăng nhập vào hệ thống:
            </p>
            
            <!-- OTP Code -->
            <div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 2px dashed #6366f1; border-radius: 12px; padding: 25px; margin: 0 0 30px 0;">
                <p style="color: #6b7280; font-size: 12px; margin: 0 0 10px 0; text-transform: uppercase; letter-spacing: 1px;">
                    Mã xác thực của bạn
                </p>
                <p style="color: #4f46e5; font-size: 42px; font-weight: 800; letter-spacing: 8px; margin: 0; font-family: 'Courier New', monospace;">
                    {{ $code }}
                </p>
            </div>
            
            <!-- Warning -->
            <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; text-align: left; border-radius: 0 8px 8px 0; margin: 0 0 20px 0;">
                <p style="color: #92400e; font-size: 13px; margin: 0;">
                    ⏱️ <strong>Mã này có hiệu lực trong 10 phút.</strong><br>
                    🚫 Không chia sẻ mã này với bất kỳ ai.<br>
                    ❓ Nếu bạn không yêu cầu mã này, hãy bỏ qua email và kiểm tra tài khoản.
                </p>
            </div>
        </div>
        
        <!-- Footer -->
        <div style="background: #f9fafb; padding: 20px 30px; text-align: center; border-top: 1px solid #e5e7eb;">
            <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                © {{ date('Y') }} IT Solo Leveling. All rights reserved.<br>
                Email này được gửi tự động, vui lòng không trả lời.
            </p>
        </div>
    </div>
</body>
</html>
