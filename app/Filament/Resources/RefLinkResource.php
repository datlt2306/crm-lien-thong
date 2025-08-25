<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RefLinkResource\Pages;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;

class RefLinkResource extends Resource {
    protected static ?string $model = null;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Link giới thiệu';

    protected static ?string $modelLabel = 'Link giới thiệu';

    protected static ?string $pluralModelLabel = 'Link giới thiệu';

    protected static string|\UnitEnum|null $navigationGroup = 'Thanh toán & Hoa hồng';
    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool {
        $user = Auth::user();
        return $user && $user->role === 'ctv';
    }

    public static function getPages(): array {
        return [
            'index' => Pages\ViewMyRefLink::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string {
        $user = Auth::user();
        if ($user && $user->role === 'ctv') {
            $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();
            if ($collaborator) {
                return $collaborator->ref_id;
            }
        }
        return null;
    }

    public static function getNavigationBadgeTooltip(): ?string {
        return 'Mã giới thiệu của bạn';
    }
}
