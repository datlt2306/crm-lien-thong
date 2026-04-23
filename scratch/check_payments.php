<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use App\Models\Payment;

$students = Student::all();
echo "Total students: " . $students->count() . "\n";

foreach ($students as $student) {
    $payment = $student->payment;
    echo "Student: {$student->full_name} | Status: {$student->status} | Program: {$student->program_type}\n";
    if ($payment) {
        echo "  Payment: ID {$payment->id} | Status: {$payment->status} | Amount: {$payment->amount}\n";
    } else {
        echo "  Payment: NONE\n";
    }
}
