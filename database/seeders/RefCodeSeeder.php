<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Collaborator;
use App\Models\RefCode;

class RefCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dat = Collaborator::where('email', 'datletrong2306@gmail.com')->first();
        if ($dat) {
            RefCode::updateOrCreate(['name' => 'Đạt (Nguồn Long)'], [
                'code' => 'L8A2M', // Mã nhìn tự nhiên cho Long
                'collaborator_id' => $dat->id,
                'telegram_chat_id' => '-1002446700000', 
            ]);
            RefCode::updateOrCreate(['name' => 'Đạt (Nguồn Sơn)'], [
                'code' => 'S3B8X', // Mã nhìn tự nhiên cho Sơn
                'collaborator_id' => $dat->id,
                'telegram_chat_id' => '-1002446700001', 
            ]);
        }
    }
}
