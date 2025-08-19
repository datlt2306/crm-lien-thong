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

    protected static string|\UnitEnum|null $navigationGroup = null;
    protected static ?string $navigationLabel = 'Partners';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

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
        if ($user && $user->role === 'user') {
            $org = \App\Models\Organization::where('owner_id', $user->id)->first();
            if ($org) {
                $query->where('organization_id', $org->id);
            } else {
                $query->whereNull('id'); // Không có tổ chức nào, không trả về gì
            }
        }
        return $query;
    }
}
