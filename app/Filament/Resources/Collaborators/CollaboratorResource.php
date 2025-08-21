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
    protected static ?string $navigationLabel = 'CTV của tôi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        
        if ($user->role === 'super_admin') {
            // Super admin luôn thấy menu này
            return true;
        }
        
        if ($user->role !== 'user') {
            // Chỉ user mới cần kiểm tra
            return false;
        }
        
        // Kiểm tra xem user có phải là CTV và có tuyến dưới không
        $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();
        
        if (!$collaborator) {
            return false;
        }
        
        // Chỉ hiển thị nếu có tuyến dưới (downlines)
        return $collaborator->downlines()->exists();
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
        if ($user && $user->role === 'user') {
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
}
