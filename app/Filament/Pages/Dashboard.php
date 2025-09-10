<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard {




    public function getTitle(): string {
        return 'Dashboard';
    }

    protected function getHeaderWidgets(): array {
        return [];
    }

    protected function getFooterWidgets(): array {
        return [];
    }
}
