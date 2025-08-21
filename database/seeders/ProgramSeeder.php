<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Program;

class ProgramSeeder extends Seeder {
    public function run(): void {
        $data = [
            ['code' => 'REGULAR', 'name' => 'Chính quy', 'direct_commission_amount' => 1750000],
            ['code' => 'PART_TIME', 'name' => 'Vừa học vừa làm', 'direct_commission_amount' => 750000],
            ['code' => 'DISTANCE', 'name' => 'Đào tạo từ xa', 'direct_commission_amount' => 500000],
        ];

        foreach ($data as $item) {
            Program::updateOrCreate(['code' => $item['code']], $item);
        }
    }
}
