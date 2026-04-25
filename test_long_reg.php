<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use App\Models\Collaborator;
use App\Models\RefCode;
use App\Notifications\StudentRegisteredNotification;

// 1. Lấy thông tin Long (Ref: L8A2M)
$longRef = RefCode::where('code', 'L8A2M')->first();
$master = Collaborator::find($longRef->collaborator_id);

// 2. Tạo sinh viên giả lập
$student = new Student();
$student->full_name = "Sinh Viên Test Long Ref";
$student->phone = "09" . rand(10000000, 99999999);
$student->dob = "2000-01-01";
$student->address = "123 Đường Test, Hà Nội";
$student->major = "Quản trị kinh doanh";
$student->program_type = "REGULAR";
$student->source_ref = "L8A2M"; // Mã của Long
$student->collaborator_id = $master->id;
$student->status = "NEW";
$student->profile_code = "HS2026TESTLONG" . rand(100, 999);
$student->save();

// 3. Gửi thông báo (Giả lập Observer)
$master->notify(new StudentRegisteredNotification($student));

echo "✅ Đã tạo sinh viên và gửi thông báo Telegram cho nguồn của Long (Ref: L8A2M).\n";
echo "🆔 Mã hồ sơ: " . $student->profile_code . "\n";
echo "🤝 Người giới thiệu: " . $longRef->name . "\n";
