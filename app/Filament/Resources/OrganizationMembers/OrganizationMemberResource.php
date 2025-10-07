<?php

namespace App\Filament\Resources\OrganizationMembers;

use App\Filament\Resources\OrganizationMemberResource\Pages;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class OrganizationMemberResource extends Resource {
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;
    protected static ?string $navigationLabel = 'Thành viên';
    protected static string|\UnitEnum|null $navigationGroup = 'Tổ chức';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema {
        return $schema
            ->components([
                // Form components sẽ được thêm sau
            ]);
    }

    public static function table(Table $table): Table {
        return $table
            ->columns([
                // Table columns sẽ được thêm sau
            ])
            ->filters([
                // Filters sẽ được thêm sau
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array {
        return [
            // Pages sẽ được tạo sau
        ];
    }
}
