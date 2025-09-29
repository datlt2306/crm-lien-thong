<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\NotificationPreference;

class NotificationPreferenceSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        // Tạo notification preferences cho tất cả users hiện có
        $users = User::all();

        foreach ($users as $user) {
            // Tạo preferences với giá trị mặc định nếu chưa có
            $user->notificationPreferences()->firstOrCreate([]);
        }

        $this->command->info('Created notification preferences for ' . $users->count() . ' users.');
    }
}
