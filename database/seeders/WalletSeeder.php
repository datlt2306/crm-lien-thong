<?php

namespace Database\Seeders;

use App\Models\Collaborator;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        // Tạo wallet cho tất cả collaborators
        $collaborators = Collaborator::all();

        foreach ($collaborators as $collaborator) {
            Wallet::firstOrCreate(
                ['collaborator_id' => $collaborator->id],
                [
                    'balance' => 0,
                    'total_received' => 0,
                    'total_paid' => 0,
                ]
            );
        }
    }
}
