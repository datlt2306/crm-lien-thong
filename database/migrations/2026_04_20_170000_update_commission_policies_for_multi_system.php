<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // 1. Chuyển program_type sang JSON để hỗ trợ multi-select
        Schema::table('commission_policies', function (Blueprint $table) {
            $table->renameColumn('program_type', 'temp_program_type');
        });

        Schema::table('commission_policies', function (Blueprint $table) {
            $table->json('program_type')->nullable()->after('temp_program_type');
        });

        // Di chuyển dữ liệu cũ
        $policies = DB::table('commission_policies')->get();
        foreach ($policies as $policy) {
            $newTypes = $policy->temp_program_type ? [$policy->temp_program_type] : [];
            
            // Chuyển payout_rules sang cấu trúc mới: { "TYPE": [rules] }
            $oldRules = json_decode($policy->payout_rules, true) ?? [];
            $newRules = [];
            
            if ($policy->temp_program_type && !empty($oldRules)) {
                $newRules[$policy->temp_program_type] = $oldRules;
            } else {
                // Nếu không có program_type cũ, để rules vào một key chung hoặc mảng trống
                // Để đảm bảo không mất dữ liệu, ta giữ nguyên nếu không có type
                $newRules = $oldRules; 
            }

            DB::table('commission_policies')
                ->where('id', $policy->id)
                ->update([
                    'program_type' => json_encode($newTypes),
                    'payout_rules' => json_encode($newRules)
                ]);
        }

        Schema::table('commission_policies', function (Blueprint $table) {
            $table->dropColumn('temp_program_type');
        });
    }

    public function down(): void {
        Schema::table('commission_policies', function (Blueprint $table) {
            $table->string('temp_program_type')->nullable()->after('program_type');
        });

        $policies = DB::table('commission_policies')->get();
        foreach ($policies as $policy) {
            $types = json_decode($policy->program_type, true);
            $type = (!empty($types) && is_array($types)) ? $types[0] : null;

            $rules = json_decode($policy->payout_rules, true) ?? [];
            $oldRules = ($type && isset($rules[$type])) ? $rules[$type] : $rules;

            DB::table('commission_policies')
                ->where('id', $policy->id)
                ->update([
                    'temp_program_type' => $type,
                    'payout_rules' => json_encode($oldRules)
                ]);
        }

        Schema::table('commission_policies', function (Blueprint $table) {
            $table->dropColumn('program_type');
            $table->renameColumn('temp_program_type', 'program_type');
        });
    }
};
