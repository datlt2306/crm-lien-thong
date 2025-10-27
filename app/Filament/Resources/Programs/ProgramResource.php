<?php

namespace App\Filament\Resources\Programs;

use App\Models\Program;
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

class ProgramResource extends Resource {
    protected static ?string $model = Program::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;
    protected static ?string $navigationLabel = 'Hệ liên thông';
    protected static string|\UnitEnum|null $navigationGroup = 'Tuyển sinh';
    protected static ?int $navigationSort = 6;

    public static function shouldRegisterNavigation(): bool {
        $user = Auth::user();
        if (!$user) return false;
        return in_array($user->role, ['super_admin', 'organization_owner']);
    }

    public static function form(Schema $schema): Schema {
        return $schema->components([
            \Filament\Forms\Components\TextInput::make('name')
                ->label('Tên hệ')
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
        ]);
    }

    public static function table(Table $table): Table {
        return $table->columns([
            \Filament\Tables\Columns\TextColumn::make('name')
                ->label('Tên hệ')
                ->sortable()
                ->searchable(),
            \Filament\Tables\Columns\BadgeColumn::make('is_active')
                ->label('Trạng thái')
                ->formatStateUsing(fn($state) => $state ? 'Kích hoạt' : 'Vô hiệu')
                ->colors([
                    'success' => fn($state) => $state === true,
                    'danger' => fn($state) => $state === false,
                ]),
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
            'index' => Pages\ListPrograms::route('/'),
            'create' => Pages\CreateProgram::route('/create'),
            'edit' => Pages\EditProgram::route('/{record}/edit'),
        ];
    }
}
