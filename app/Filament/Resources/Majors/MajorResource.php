<?php

namespace App\Filament\Resources\Majors;

use App\Models\Major;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ActionGroup;
use Illuminate\Support\Facades\Auth;

class MajorResource extends Resource {
    protected static ?string $model = Major::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquaresPlus;
    protected static ?string $navigationLabel = 'Ngành học';
    protected static string|\UnitEnum|null $navigationGroup = 'Tuyển sinh';
    protected static ?int $navigationSort = 7;

    public static function shouldRegisterNavigation(): bool {
        $user = Auth::user();
        if (!$user) return false;
        return in_array($user->role, ['super_admin', 'organization_owner']);
    }

    public static function form(Schema $schema): Schema {
        return $schema->components([
            // Không cần mã ngành theo yêu cầu mới
            \Filament\Forms\Components\TextInput::make('name')
                ->label('Tên ngành')
                ->required(),
            \Filament\Forms\Components\Toggle::make('is_active')
                ->label('Kích hoạt')
                ->default(true)
                ->formatStateUsing(function ($state) {
                    // Chuyển đổi từ string sang boolean cho UI
                    if (is_string($state)) {
                        return $state === '1' || $state === true;
                    }
                    return (bool) $state;
                })
                ->dehydrateStateUsing(function ($state) {
                    // Chuyển đổi từ boolean sang integer cho database
                    return $state ? 1 : 0;
                }),
            // Chỉ tiêu sẽ quản lý ở phần Đơn vị
        ]);
    }

    public static function table(Table $table): Table {
        return $table->columns([
            \Filament\Tables\Columns\TextColumn::make('name')->label('Tên ngành')->sortable()->searchable(),
            \Filament\Tables\Columns\BadgeColumn::make('is_active')
                ->label('Trạng thái')
                ->formatStateUsing(fn($state) => $state ? 'Kích hoạt' : 'Vô hiệu')
                ->colors(['success' => fn($state) => $state === true, 'danger' => fn($state) => $state === false]),
        ])->recordActions([
            ActionGroup::make([
                EditAction::make()
                    ->label('Chỉnh sửa'),
                DeleteAction::make()
                    ->label('Xóa'),
            ])
                ->label('Hành động')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->button()
                ->size('sm')
                ->tooltip('Các hành động khả dụng')
        ]);
    }

    public static function getPages(): array {
        return [
            'index' => Pages\ListMajors::route('/'),
            'create' => Pages\CreateMajor::route('/create'),
            'edit' => Pages\EditMajor::route('/{record}/edit'),
        ];
    }
}
