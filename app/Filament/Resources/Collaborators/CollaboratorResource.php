<?php

namespace App\Filament\Resources\Collaborators;

use App\Filament\Resources\Collaborators\Pages\CreateCollaborator;
use App\Filament\Resources\Collaborators\Pages\EditCollaborator;
use App\Filament\Resources\Collaborators\Pages\ListCollaborators;
use App\Filament\Resources\Collaborators\Pages\ViewCollaborator;
use App\Filament\Resources\Collaborators\Schemas\CollaboratorForm;
use App\Filament\Resources\Collaborators\Schemas\CollaboratorInfolist;
use App\Filament\Resources\Collaborators\Tables\CollaboratorsTable;
use App\Models\Collaborator;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use App\Models\User;

class CollaboratorResource extends Resource {
    protected static ?string $model = Collaborator::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Cộng tác viên';
    protected static ?string $navigationLabel = 'Cộng tác viên';
    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function shouldRegisterNavigation(): bool {
        $user = \Illuminate\Support\Facades\Auth::user();

        if ($user->role === 'super_admin') {
            // Super admin luôn thấy menu này
            return true;
        }

        if ($user->role === 'organization_owner') {
            // Chủ đơn vị luôn thấy menu CTV để xem CTV cấp 1
            return true;
        }

        if ($user->role === 'ctv') {
            // CTV không được phép xem danh sách cộng tác viên
            return false;
        }

        return false;
    }

    public static function form(Schema $schema): Schema {
        return CollaboratorForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema {
        return CollaboratorInfolist::configure($schema);
    }

    public static function table(Table $table): Table {
        return CollaboratorsTable::configure($table);
    }

    public static function getRelations(): array {
        return [
            //
        ];
    }

    public static function getPages(): array {
        return [
            'index' => ListCollaborators::route('/'),
            'create' => CreateCollaborator::route('/create'),
            'edit' => EditCollaborator::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (!$user) {
            return $query;
        }

        // Super admin thấy tất cả
        if ($user->role === 'super_admin') {
            return $query;
        }

        // Chủ đơn vị: thấy CTV cấp 1 trong tổ chức của mình (upline_id = null)
        if ($user->role === 'organization_owner') {
            $org = \App\Models\Organization::where('organization_owner_id', $user->id)->first();
            if ($org) {
                return $query->where('organization_id', $org->id)
                    ->whereNull('upline_id');
            }
            // Không có tổ chức -> không trả về gì
            return $query->whereNull('id');
        }

        // CTV: thấy CTV cấp 2 trực tiếp (downlines trực tiếp)
        if ($user->role === 'ctv') {
            $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();
            if ($collaborator) {
                return $query->where('upline_id', $collaborator->id);
            }
            return $query->whereNull('id');
        }

        // Mặc định: không thấy gì
        return $query->whereNull('id');
    }

    public static function getNavigationBadge(): ?string {
        try {
            $user = Auth::user();
            if (!$user) return null;

            if ($user->role === 'super_admin') {
                // Super admin thấy tổng số CTV
                return (string) Collaborator::count();
            }

            if ($user->role === 'organization_owner') {
                // Chủ đơn vị thấy số CTV cấp 1 trong tổ chức (upline_id = null)
                $org = \App\Models\Organization::where('organization_owner_id', $user->id)->first();
                if ($org) {
                    return (string) Collaborator::where('organization_id', $org->id)
                        ->whereNull('upline_id')
                        ->count();
                }
            }

            if ($user->role === 'ctv') {
                // CTV không thấy badge vì không thấy menu
                return null;
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getNavigationBadgeTooltip(): ?string {
        $user = Auth::user();
        if (!$user) return null;

        if ($user->role === 'super_admin') {
            return 'Tổng số cộng tác viên';
        }

        if ($user->role === 'organization_owner') {
            return 'Số cộng tác viên trong tổ chức';
        }

        if ($user->role === 'ctv') {
            return null; // CTV không thấy menu nên không cần tooltip
        }

        return null;
    }
}
