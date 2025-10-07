<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Organization;
use App\Models\Collaborator;

class CleanupOrphanedRecordsSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        echo "=== DỌN DẸP DỮ LIỆU CŨ ===\n";

        // 1. Dọn dẹp Organizations có organization_owner_id không tồn tại
        $organizationsWithInvalidOwners = Organization::whereNotNull('organization_owner_id')
            ->whereNotExists(function ($query) {
                $query->select(\DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'organizations.organization_owner_id');
            })
            ->get();

        foreach ($organizationsWithInvalidOwners as $org) {
            $org->update(['organization_owner_id' => null]);
            echo "✓ Đã xóa organization_owner_id không hợp lệ cho: {$org->name}\n";
        }

        // 2. Dọn dẹp Collaborators có email không tồn tại trong Users
        $collaboratorsWithInvalidEmails = Collaborator::whereNotExists(function ($query) {
            $query->select(\DB::raw(1))
                ->from('users')
                ->whereColumn('users.email', 'collaborators.email');
        })->get();

        foreach ($collaboratorsWithInvalidEmails as $collab) {
            $collab->delete();
            echo "✓ Đã xóa Collaborator không hợp lệ: {$collab->full_name} ({$collab->email})\n";
        }

        // 3. Dọn dẹp Collaborators có upline_id không tồn tại
        $collaboratorsWithInvalidUplines = Collaborator::whereNotNull('upline_id')
            ->whereNotExists(function ($query) {
                $query->select(\DB::raw(1))
                    ->from('collaborators')
                    ->whereColumn('collaborators.id', 'collaborators.upline_id');
            })->get();

        foreach ($collaboratorsWithInvalidUplines as $collab) {
            $collab->update(['upline_id' => null]);
            echo "✓ Đã xóa upline_id không hợp lệ cho: {$collab->full_name}\n";
        }

        // 4. Dọn dẹp Collaborators có organization_id không tồn tại
        $collaboratorsWithInvalidOrgs = Collaborator::whereNotNull('organization_id')
            ->whereNotExists(function ($query) {
                $query->select(\DB::raw(1))
                    ->from('organizations')
                    ->whereColumn('organizations.id', 'collaborators.organization_id');
            })->get();

        foreach ($collaboratorsWithInvalidOrgs as $collab) {
            $collab->update(['organization_id' => null]);
            echo "✓ Đã xóa organization_id không hợp lệ cho: {$collab->full_name}\n";
        }

        echo "\n=== HOÀN THÀNH DỌN DẸP ===\n";
        echo "Organizations: " . Organization::count() . "\n";
        echo "Collaborators: " . Collaborator::count() . "\n";
        echo "Users: " . User::count() . "\n";
    }
}
