<?php

namespace App\Filament\Resources\StudentEvents;

use App\Filament\Resources\StudentEventResource\Pages;
use App\Models\Student;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class StudentEventResource extends Resource {
    protected static ?string $model = Student::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;
    protected static ?string $navigationLabel = 'Sự kiện / Notes';
    protected static string|\UnitEnum|null $navigationGroup = 'Tuyển sinh';
    protected static ?int $navigationSort = 3;

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
