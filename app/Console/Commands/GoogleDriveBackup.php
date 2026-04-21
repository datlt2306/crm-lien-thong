<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

class GoogleDriveBackup extends Command
{
    protected $signature = 'app:backup-google-drive';
    protected $description = 'Backup database to Google Drive with YYYY/MM structure';

    public function handle()
    {
        $year = date('Y');
        $month = date('m');
        $name = "{$year}/{$month}";

        $this->info("Starting database backup for: {$name}");

        $this->info("Creating/Checking directory: {$name}");
        \Illuminate\Support\Facades\Storage::disk('google_backup')->makeDirectory($name);

        // Cấu trúc thư mục: google_backup_root/YYYY/MM
        Config::set('backup.backup.name', $name);

        // Chạy lệnh backup của Spatie
        $status = Artisan::call('backup:run', [
            '--only-db' => true,
            '--disable-notifications' => false,
        ]);

        $output = Artisan::output();
        $this->info($output);

        if ($status === 0) {
            $this->info("Backup successfully uploaded to Google Drive.");
        } else {
            $this->error("Backup failed. Check logs for details.");
        }
    }
}
