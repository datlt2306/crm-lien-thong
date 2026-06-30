<?php

namespace App\Filament\Resources\Programs\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use App\Models\Student;
use App\Models\Program;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProgramsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading('Hệ đào tạo')
            ->description('Quản lý danh sách các hệ đào tạo.')
            ->headerActions([
                \Filament\Actions\Action::make('create')
                    ->label('Thêm hệ đào tạo')
                    ->url(fn() => \App\Filament\Resources\Programs\ProgramResource::getUrl('create')),
            ])
            ->columns([
                TextColumn::make('name')->label('Hệ đào tạo')->sortable(),

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
                        ->modalHeading('Xóa hệ đào tạo')
                        ->modalDescription('Bạn có chắc chắn muốn xóa hệ đào tạo này? Hồ sơ sẽ được chuyển vào Thùng rác.'),
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
            ->toolbarActions([
                \Filament\Actions\Action::make('show_active')
                    ->label(fn() => 'Tất cả (' . \App\Models\Program::whereNull('deleted_at')->count() . ')')
                    ->icon('heroicon-o-academic-cap')
                    ->color(fn() => !session('programs_show_trashed', false) ? 'primary' : 'gray')
                    ->button()
                    ->size('sm')
                    ->action(function () {
                        session(['programs_show_trashed' => false]);
                    }),
                \Filament\Actions\Action::make('show_trashed')
                    ->label(fn() => 'Thùng rác (' . \App\Models\Program::onlyTrashed()->count() . ')')
                    ->icon('heroicon-o-trash')
                    ->color(fn() => session('programs_show_trashed', false) ? 'danger' : 'gray')
                    ->button()
                    ->size('sm')
                    ->visible(fn() => \App\Models\Program::onlyTrashed()->count() > 0)
                    ->action(function () {
                        session(['programs_show_trashed' => true]);
                    }),
                BulkActionGroup::make(
                    session('programs_show_trashed', false)
                        ? [
                            \Filament\Actions\RestoreBulkAction::make()
                                ->label('Khôi phục đã chọn'),
                            \Filament\Actions\ForceDeleteBulkAction::make()
                                ->label('Xóa vĩnh viễn đã chọn')
                                ->modalHeading('Xóa vĩnh viễn hệ đào tạo đã chọn')
                                ->modalDescription('Hành động này sẽ xóa hoàn toàn các hệ đào tạo đã chọn khỏi hệ thống. Bạn chắc chắn chứ?'),
                        ]
                        : [
                            \Filament\Actions\DeleteBulkAction::make()
                                ->label('Bỏ vào thùng rác')
                                ->modalHeading('Bỏ hệ đào tạo đã chọn vào thùng rác')
                                ->modalDescription('Bạn có chắc chắn muốn bỏ các hệ đào tạo đã chọn vào Thùng rác? Bạn có thể khôi phục lại sau.'),
                        ]
                )
                ->label('Hành động hàng loạt'),
            ])
            ->defaultSort('id', 'desc');
    }
}

