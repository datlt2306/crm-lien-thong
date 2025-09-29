<?php

namespace App\Filament\Theme;

use Filament\View\PanelsRenderHook;
use Filament\View\Theme;

class AdminTheme extends Theme {
    public function getName(): string {
        return 'admin';
    }

    public function getRenderHook(): PanelsRenderHook {
        return PanelsRenderHook::HEAD_END;
    }

    public function getView(): string {
        return 'filament.theme.styles';
    }
}
