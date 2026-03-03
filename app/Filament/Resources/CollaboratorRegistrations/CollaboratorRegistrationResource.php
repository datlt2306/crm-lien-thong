<?php

namespace App\Filament\Resources\CollaboratorRegistrations;

use App\Filament\Resources\CollaboratorRegistrations\Pages\CreateCollaboratorRegistration;
use App\Filament\Resources\CollaboratorRegistrations\Pages\EditCollaboratorRegistration;
use App\Filament\Resources\CollaboratorRegistrations\Pages\ListCollaboratorRegistrations;
use App\Filament\Resources\CollaboratorRegistrations\Schemas\CollaboratorRegistrationForm;
use App\Filament\Resources\CollaboratorRegistrations\Tables\CollaboratorRegistrationsTable;
use App\Models\CollaboratorRegistration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CollaboratorRegistrationResource extends Resource {
    protected static ?string $model = CollaboratorRegistration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static ?string $navigationLabel = 'Mời/Đăng ký CTV';
    protected static string|\UnitEnum|null $navigationGroup = 'Cộng tác viên';
    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Đăng ký Cộng tác viên';

    protected static ?string $pluralModelLabel = 'Đăng ký Cộng tác viên';

    public static function shouldRegisterNavigation(): bool {
        // Ẩn hoàn toàn vì tính năng đăng ký CTV public đã bị loại bỏ
        return false;
    }

    public static function form(Schema $schema): Schema {
        return CollaboratorRegistrationForm::configure($schema);
    }

    public static function table(Table $table): Table {
        return CollaboratorRegistrationsTable::configure($table);
    }

    public static function getRelations(): array {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder {
        $query = parent::getEloquentQuery();
        $user = \Illuminate\Support\Facades\Auth::user();

        if (!$user) {
            return $query->whereNull('id');
        }

        // Super admin thấy tất cả
        if ($user->role === 'super_admin') {
            return $query;
        }

        // Organization owner thấy đăng ký của tổ chức mình
        if ($user->role === 'organization_owner') {
            $org = \App\Models\Organization::where('organization_owner_id', $user->id)->first();
            if ($org) {
                return $query->where('organization_id', $org->id);
            }
            return $query->whereNull('id');
        }

        // CTV và các role khác không thấy gì
        return $query->whereNull('id');
    }

    public static function getPages(): array {
        return [
            'index' => ListCollaboratorRegistrations::route('/'),
            'edit' => EditCollaboratorRegistration::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) return null;

        $count = 0;
        if ($user->role === 'super_admin') {
            $count = CollaboratorRegistration::where('status', 'pending')->count();
        } elseif ($user->role === 'organization_owner') {
            $org = \App\Models\Organization::where('organization_owner_id', $user->id)->first();
            if ($org) {
                $count = CollaboratorRegistration::where('status', 'pending')
                    ->where('organization_id', $org->id)
                    ->count();
            }
        }

        return $count > 0 ? (string)$count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null {
        return 'warning';
    }
}
