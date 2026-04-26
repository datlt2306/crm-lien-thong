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
                TextColumn::make('is_active')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn($state) => $state ? 'Hoạt động' : 'Ngừng hoạt động'),
                TextColumn::make('updated_at')->label('Cập nhật')->since(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->label('Chỉnh sửa'),
                    \Filament\Actions\Action::make('toggle_active')
                        ->label(fn($record) => $record->is_active ? 'Vô hiệu hóa' : 'Kích hoạt')
                        ->icon(fn($record) => $record->is_active ? 'heroicon-m-no-symbol' : 'heroicon-m-check-circle')
                        ->color(fn($record) => $record->is_active ? 'danger' : 'success')
                        ->action(fn($record) => $record->update(['is_active' => !$record->is_active]))
                        ->requiresConfirmation(),
                    \Filament\Actions\DeleteAction::make()
                        ->label('Xóa')
                        ->modalHeading('Xóa ngành học')
                        ->modalDescription('Bạn có chắc chắn muốn xóa ngành học này? Hồ sơ sẽ được chuyển vào Thùng rác.'),
                    \Filament\Actions\RestoreAction::make()
                        ->label('Khôi phục'),
                    \Filament\Actions\ForceDeleteAction::make()
                        ->label('Xóa vĩnh viễn'),
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
                    \Filament\Actions\DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->modalHeading('Xóa các ngành học đã chọn')
                        ->modalDescription('Hồ sơ sẽ được chuyển vào Thùng rác.'),
                    \Filament\Actions\RestoreBulkAction::make()
                        ->label('Khôi phục đã chọn'),
                    \Filament\Actions\ForceDeleteBulkAction::make()
                        ->label('Xóa vĩnh viễn đã chọn'),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}

