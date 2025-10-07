<?php

namespace App\Filament\Resources\StudentPipelines;

use App\Filament\Resources\StudentPipelineResource\Pages;
use App\Models\Student;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class StudentPipelineResource extends Resource {
    protected static ?string $model = Student::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;
    protected static ?string $navigationLabel = 'Học viên';
    protected static string|\UnitEnum|null $navigationGroup = 'Tuyển sinh';
    protected static ?int $navigationSort = 1;

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
