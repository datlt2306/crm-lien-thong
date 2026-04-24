<?php

namespace App\Filament\Resources\Majors\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use App\Models\Student;
use App\Models\Major;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MajorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Tên ngành')->searchable()->sortable(),
                TextColumn::make('code')->label('Mã ngành')->searchable(),
                IconColumn::make('is_active')->label('Hoạt động')->boolean(),
                TextColumn::make('updated_at')->label('Cập nhật')->since(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->label('Chỉnh sửa'),
                    DeleteAction::make()
                        ->label('Xóa')
                        ->modalHeading('Xóa ngành học')
                        ->modalDescription('Nếu ngành này đã có học viên đăng ký, hệ thống sẽ tự động chuyển sang trạng thái Ngừng hoạt động thay vì xóa vĩnh viễn.')
                        ->modalSubmitActionLabel('Xóa/Vô hiệu hóa')
                        ->action(function ($record) {
                            $hasStudents = Student::where('major', $record->name)->exists();

                            if ($hasStudents) {
                                $record->update(['is_active' => false]);
                                Notification::make()
                                    ->title('Đã chuyển sang Ngừng hoạt động')
                                    ->body("Ngành {$record->name} đã có học viên đăng ký nên không thể xóa. Trạng thái đã được cập nhật.")
                                    ->warning()
                                    ->send();
                            } else {
                                $record->delete();
                                Notification::make()
                                    ->title('Đã xóa vĩnh viễn')
                                    ->success() ->send();
                            }
                        }),
                ])
                    ->label('Hành động')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->button()
                    ->size('sm')
                    ->tooltip('Các hành động khả dụng'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->modalHeading('Xóa các ngành đã chọn')
                        ->modalDescription('Các ngành đã có học viên sẽ được tự động chuyển sang trạng thái Ngừng hoạt động.')
                        ->modalSubmitActionLabel('Bắt đầu xử lý')
                        ->action(function ($records) {
                            $deleted = 0;
                            $deactivated = 0;

                            foreach ($records as $record) {
                                $hasStudents = Student::where('major', $record->name)->exists();

                                if ($hasStudents) {
                                    $record->update(['is_active' => false]);
                                    $deactivated++;
                                } else {
                                    $record->delete();
                                    $deleted++;
                                }
                            }

                            Notification::make()
                                ->title('Xử lý hoàn tất')
                                ->body("Đã xóa $deleted ngành và chuyển Ngừng hoạt động $deactivated ngành có học viên.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}

