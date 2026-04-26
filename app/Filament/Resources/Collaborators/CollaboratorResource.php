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
use Illuminate\Support\Facades\Gate;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use App\Models\User;

class CollaboratorResource extends Resource {
    protected static ?string $model = Collaborator::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Cộng tác viên';
    protected static ?string $navigationLabel = 'Cộng tác viên';
    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = null;

    public static function shouldRegisterNavigation(): bool {
        return Gate::allows('viewAny', Collaborator::class);
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
            return $query->whereNull('id');
        }

        // Cho phép xem nếu có quyền
        if ($user->can('collaborator_view_any')) {
            return $query;
        }

        // Mặc định: không thấy gì
        return $query->whereNull('id');
    }

}
