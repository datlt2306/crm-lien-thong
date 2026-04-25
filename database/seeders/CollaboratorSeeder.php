<?php

namespace Database\Seeders;

use App\Models\Collaborator;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CollaboratorSeeder extends Seeder {
    public function run(): void {
        // Master Collaborator (Đạt)
        $datUser = User::updateOrCreate(
            ['email' => 'datletrong2306@gmail.com'],
            [
                'name' => 'Lê Trọng Đạt',
                'password' => Hash::make('password'),
            ]
        );
        $datUser->assignRole('ctv');

        $dat = Collaborator::updateOrCreate(
            ['email' => 'datletrong2306@gmail.com'],
            [
                'user_id' => $datUser->id,
                'full_name' => 'Lê Trọng Đạt',
                'phone' => '0987654321',
                'ref_id' => 'DAT',
                'status' => 'active',
                'telegram_chat_id' => '233224973', // ID của mày
            ]
        );

        // Tạo sẵn các Proxy Ref cho Đạt
        $dat->refCodes()->updateOrCreate(['code' => 'L8A2M'], ['name' => 'Nguồn Long', 'telegram_chat_id' => '233224973']); // Test với cùng ID của mày
        $dat->refCodes()->updateOrCreate(['code' => 'S3B8X'], ['name' => 'Nguồn Sơn']);
    }
}
