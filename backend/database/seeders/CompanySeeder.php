<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user or create one
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        $companies = [
            [
                'name' => 'FPT Software',
                'description' => 'Công ty phần mềm hàng đầu Việt Nam với hơn 30.000 nhân viên, chuyên phát triển giải pháp chuyển đổi số cho doanh nghiệp.',
                'website' => 'https://fptsoftware.com',
                'address' => 'Tòa nhà FPT, Đường D1, Khu Công nghệ cao, Quận 9, TP. Hồ Chí Minh',
            ],
            [
                'name' => 'VNG Corporation',
                'description' => 'Tập đoàn công nghệ Internet hàng đầu Việt Nam, sở hữu Zalo, ZaloPay, Zing MP3 và nhiều sản phẩm công nghệ khác.',
                'website' => 'https://vng.com.vn',
                'address' => 'Tòa nhà Z06, Đường số 13, Phường Tân Thuận Đông, Quận 7, TP. Hồ Chí Minh',
            ],
            [
                'name' => 'Tiki Corporation',
                'description' => 'Nền tảng thương mại điện tử và dịch vụ công nghệ hàng đầu Việt Nam với hơn 5 triệu đơn hàng mỗi tháng.',
                'website' => 'https://tiki.vn',
                'address' => '52 Út Tịch, Phường 4, Quận Tân Bình, TP. Hồ Chí Minh',
            ],
            [
                'name' => 'Momo Technology',
                'description' => 'Ví điện tử và siêu ứng dụng tài chính số với hơn 30 triệu người dùng, cung cấp giải pháp thanh toán toàn diện.',
                'website' => 'https://momo.vn',
                'address' => 'Lầu 6, Tòa nhà Phú Mỹ Hưng Tower, 08 Hoàng Văn Thái, Quận 7, TP. Hồ Chí Minh',
            ],
            [
                'name' => 'Shopee Vietnam',
                'description' => 'Nền tảng thương mại điện tử di động hàng đầu Đông Nam Á và Đài Loan, với hơn 100 triệu lượt tải xuống.',
                'website' => 'https://shopee.vn',
                'address' => 'Tầng 4-5-6, Tòa nhà Capital Place, 29 Liễu Giai, Quận Ba Đình, Hà Nội',
            ],
            [
                'name' => 'Base.vn',
                'description' => 'Công ty công nghệ chuyên phát triển hệ thống CRM, HRM và giải pháp quản lý doanh nghiệp cho SME Việt Nam.',
                'website' => 'https://base.vn',
                'address' => 'Tầng 8, Tòa nhà Sông Đà, 18 Phạm Hùng, Nam Từ Liêm, Hà Nội',
            ],
            [
                'name' => 'VinAI Research',
                'description' => 'Viện nghiên cứu công nghệ hàng đầu Việt Nam, tập trung vào Computer Vision, NLP và các ứng dụng thực tiễn.',
                'website' => 'https://vinai.io',
                'address' => 'Tầng 10, Tòa nhà Vinhomes Metropolis, 29 Liễu Giai, Ba Đình, Hà Nội',
            ],
            [
                'name' => 'Grab Vietnam',
                'description' => 'Siêu ứng dụng hàng đầu Đông Nam Á cung cấp dịch vụ giao thông, giao đồ ăn, thanh toán và dịch vụ tài chính.',
                'website' => 'https://grab.com/vn',
                'address' => 'Lầu 8, Tòa nhà Centec Tower, 72-74 Nguyễn Thị Minh Khai, Quận 3, TP. Hồ Chí Minh',
            ],
        ];

        foreach ($companies as $company) {
            Company::query()->updateOrCreate(
                ['user_id' => $user->id, 'name' => $company['name']],
                [
                    'description' => $company['description'],
                    'website' => $company['website'],
                    'address' => $company['address'],
                    'logo_path' => null,
                ]
            );
        }
    }
}
