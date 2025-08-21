<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Major;
use App\Models\Organization;

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

        // Gán tất cả majors cho tổ chức hiện có làm ví dụ
        $orgs = Organization::all();
        foreach ($orgs as $org) {
            $org->majors()->sync(Major::pluck('id')->toArray());
        }
    }
}
