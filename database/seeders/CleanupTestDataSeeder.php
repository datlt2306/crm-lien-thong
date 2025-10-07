<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Organization;
use App\Models\Collaborator;

class CleanupTestDataSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        echo "=== DỌN DẸP TEST DATA ===\n";

        // 1. Xóa test users (có chứa "Test" trong tên)
        $testUsers = User::where('name', 'like', '%Test%')
            ->orWhere('email', 'like', '%test%')
            ->get();

        foreach ($testUsers as $user) {
            echo "Đang xóa test user: {$user->name} ({$user->email})\n";
            $user->delete(); // UserObserver sẽ tự động xóa mối quan hệ
        }

        // 2. Xóa test organizations
        $testOrgs = Organization::where('name', 'like', '%Test%')
            ->orWhere('code', 'like', '%TEST%')
            ->get();

        foreach ($testOrgs as $org) {
            echo "Đang xóa test organization: {$org->name}\n";
            $org->delete();
        }

        // 3. Xóa test collaborators
        $testCollabs = Collaborator::where('full_name', 'like', '%Test%')
            ->orWhere('email', 'like', '%test%')
            ->get();

        foreach ($testCollabs as $collab) {
            echo "Đang xóa test collaborator: {$collab->full_name}\n";
            $collab->delete();
        }

        echo "\n=== HOÀN THÀNH DỌN DẸP ===\n";
        echo "Users: " . User::count() . "\n";
        echo "Organizations: " . Organization::count() . "\n";
        echo "Collaborators: " . Collaborator::count() . "\n";
    }
}
