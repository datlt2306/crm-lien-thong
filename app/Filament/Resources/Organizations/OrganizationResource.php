<?php

namespace App\Filament\Resources\Organizations;

use App\Filament\Resources\Organizations\Pages\CreateOrganization;
use App\Filament\Resources\Organizations\Pages\EditOrganization;
use App\Filament\Resources\Organizations\Pages\ListOrganizations;
use App\Filament\Resources\Organizations\Pages\ViewOrganization;
use App\Filament\Resources\Organizations\Schemas\OrganizationForm;
use App\Filament\Resources\Organizations\Schemas\OrganizationInfolist;
use App\Filament\Resources\Organizations\Tables\OrganizationsTable;
use App\Models\Organization;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class OrganizationResource extends Resource {
    protected static ?string $model = Organization::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Quản lý dữ liệu';

    protected static ?string $navigationLabel = 'Đơn vị';
    protected static ?int $navigationSort = 4;

    public static function getNavigationUrl(): string {
        $user = Auth::user();

        // Chủ đơn vị sẽ đi thẳng đến trang edit đơn vị của mình
        if ($user?->role === 'chủ đơn vị') {
            // Tìm đơn vị của chủ đơn vị
            $organization = \App\Models\Organization::where('owner_id', $user->id)->first();
            if ($organization) {
                return static::getUrl('my-organization', ['record' => $organization->id]);
            }
        }

        // Super admin đi đến danh sách
        return static::getUrl('index');
    }

    public static function shouldRegisterNavigation(): bool {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Cả super admin và chủ đơn vị đều thấy menu "Đơn vị"
        return in_array($user->role, ['super_admin', 'chủ đơn vị']);
    }

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema {
        return OrganizationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema {
        return OrganizationInfolist::configure($schema);
    }

    public static function table(Table $table): Table {
        return OrganizationsTable::configure($table);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder {
        $query = parent::getEloquentQuery()->with('owner');

        // Nếu là chủ đơn vị, chỉ hiển thị đơn vị của họ
        if (\Illuminate\Support\Facades\Auth::user()?->role === 'chủ đơn vị') {
            $query->where('owner_id', \Illuminate\Support\Facades\Auth::id());
        }

        return $query;
    }

    public static function getRelations(): array {
        return [
            //
        ];
    }

    public static function getPages(): array {
        return [
            'index' => ListOrganizations::route('/'),
            'create' => CreateOrganization::route('/create'),
            'edit' => EditOrganization::route('/{record}/edit'),
            'my-organization' => \App\Filament\Resources\Organizations\Pages\EditMyOrganization::route('/my-organization/{record}'),
        ];
    }
}
