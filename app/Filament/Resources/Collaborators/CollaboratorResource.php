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

class CollaboratorResource extends Resource {
    protected static ?string $model = Collaborator::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Quản lý dữ liệu';
    protected static ?string $navigationLabel = 'Cộng tác viên';
    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function shouldRegisterNavigation(): bool {
        $user = \Illuminate\Support\Facades\Auth::user();

        if ($user->role === 'super_admin') {
            // Super admin luôn thấy menu này
            return true;
        }

        if ($user->role === 'ctv') {
            // Kiểm tra xem user có phải là CTV và có tuyến dưới không
            $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();

            if (!$collaborator) {
                return false;
            }

            // Chỉ hiển thị nếu có tuyến dưới (downlines)
            return $collaborator->downlines()->exists();
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
        if ($user && $user->role === 'chủ đơn vị') {
            // Tìm collaborator của user hiện tại
            $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();
            if ($collaborator) {
                // CTV chỉ thấy CTV con của mình (downlines)
                $query->where('upline_id', $collaborator->id);
            } else {
                // Nếu không tìm thấy collaborator, không trả về gì
                $query->whereNull('id');
            }
        }
        return $query;
    }

    public static function getNavigationBadge(): ?string {
        try {
            $user = Auth::user();
            if (!$user) return null;

            if ($user->role === 'super_admin') {
                // Super admin thấy tổng số CTV
                return (string) Collaborator::count();
            }

            if ($user->role === 'chủ đơn vị') {
                // Chủ đơn vị thấy số CTV của tổ chức mình
                $org = \App\Models\Organization::where('owner_id', $user->id)->first();
                if ($org) {
                    return (string) Collaborator::where('organization_id', $org->id)->count();
                }
            }

            if ($user->role === 'ctv') {
                // CTV thấy số tuyến dưới của mình
                $collaborator = Collaborator::where('email', $user->email)->first();
                if ($collaborator) {
                    return (string) $collaborator->downlines()->count();
                }
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

        if ($user->role === 'chủ đơn vị') {
            return 'Số cộng tác viên trong tổ chức';
        }

        if ($user->role === 'ctv') {
            return 'Số tuyến dưới';
        }

        return null;
    }
}
