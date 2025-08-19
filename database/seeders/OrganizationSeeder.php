<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        \App\Models\Organization::create([
            'name' => 'ĐH Giao thông Vận tải',
            'code' => 'dh-giao-thong-van-tai',
            'contact_name' => 'Nguyễn Văn A',
            'contact_phone' => '0901234567',
            'status' => 'active',
        ]);
    }
}
