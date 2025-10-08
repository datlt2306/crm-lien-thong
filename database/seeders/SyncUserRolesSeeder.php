<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Organization;
use App\Models\Collaborator;

class SyncUserRolesSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        echo "=== ĐỒNG BỘ DỮ LIỆU USER ROLES ===\n";

        // 1. Gán organization_owner cho organizations
        $organizations = Organization::whereNull('organization_owner_id')->get();
        $organizationOwners = User::where('role', 'organization_owner')->get();

        foreach ($organizations as $org) {
            if ($organizationOwners->count() > 0) {
                $owner = $organizationOwners->first();
                $org->update(['organization_owner_id' => $owner->id]);
                echo "✓ Đã gán organization_owner cho: {$org->name}\n";
            }
        }

        // 2. Tạo Collaborator cho users có role 'ctv' (KHÔNG tạo cho organization_owner)
        $ctvUsers = User::where('role', 'ctv')->get();
        $organization = Organization::first(); // Lấy organization đầu tiên

        foreach ($ctvUsers as $user) {
            // Kiểm tra xem đã có Collaborator chưa
            $existingCollaborator = Collaborator::where('email', $user->email)->first();

            if (!$existingCollaborator) {
                // Xử lý phone NULL
                $phone = $user->phone;
                if (empty($phone)) {
                    $phone = '0000000000'; // Default phone
                    if (!empty($user->email)) {
                        $phone = '000' . substr(preg_replace('/[^0-9]/', '', $user->email), 0, 7);
                    }
                }

                $collaborator = Collaborator::create([
                    'full_name' => $user->name,
                    'email' => $user->email,
                    'phone' => $phone,
                    'organization_id' => $organization?->id,
                    'upline_id' => null, // CTV cấp 1
                    'status' => 'active'
                ]);
                echo "✓ Đã tạo Collaborator: {$collaborator->full_name}\n";
            } else {
                echo "✓ Collaborator đã tồn tại: {$existingCollaborator->full_name}\n";
            }
        }

        // 3. Dọn dẹp: Xóa Collaborator records của organization_owners (không nên có)
        foreach ($organizationOwners as $owner) {
            $existingCollaborator = Collaborator::where('email', $owner->email)->first();

            if ($existingCollaborator) {
                $existingCollaborator->delete();
                echo "✓ Đã xóa Collaborator không hợp lệ của organization_owner: {$owner->name}\n";
            }
        }

        echo "\n=== HOÀN THÀNH ===\n";
        echo "Organizations: " . Organization::count() . "\n";
        echo "Collaborators: " . Collaborator::count() . "\n";
        echo "Users with organization_owner role: " . User::where('role', 'organization_owner')->count() . "\n";
        echo "Users with ctv role: " . User::where('role', 'ctv')->count() . "\n";
    }
}
