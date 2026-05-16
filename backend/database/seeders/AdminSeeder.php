<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo hoặc update tài khoản admin
        User::updateOrCreate(
            ['email' => 'admin@jobmatch.com'],
            [
                'name' => 'Admin JobMatch',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        echo "✅ Admin account created/updated successfully!\n";
        echo "📧 Email: admin@jobmatch.com\n";
        echo "🔑 Password: admin123\n";
    }
}
