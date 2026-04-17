<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Major;

class MajorSeeder extends Seeder {
    public function run(): void {
        $majors = [
            ['code' => 'IT', 'name' => 'Công nghệ thông tin'],
            ['code' => 'BA', 'name' => 'Quản trị kinh doanh'],
            ['code' => 'AC', 'name' => 'Kế toán'],
        ];

        foreach ($majors as $item) {
            Major::updateOrCreate(['code' => $item['code']], $item);
        }
    }
}
