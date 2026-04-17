<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'profile_code')) {
                $table->string('profile_code')->nullable()->unique()->after('id');
            }
        });

        DB::table('students')
            ->select(['id', 'created_at'])
            ->whereNull('profile_code')
            ->orderBy('id')
            ->chunkById(200, function ($students) {
                foreach ($students as $student) {
                    $year = $student->created_at
                        ? date('Y', strtotime((string) $student->created_at))
                        : date('Y');

                    DB::table('students')
                        ->where('id', $student->id)
                        ->update([
                            'profile_code' => sprintf('HS%s%06d', $year, $student->id),
                        ]);
                }
            });
    }

    public function down(): void {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'profile_code')) {
                $table->dropUnique(['profile_code']);
                $table->dropColumn('profile_code');
            }
        });
    }
};
