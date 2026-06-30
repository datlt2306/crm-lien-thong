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
            ->heading('Ngành đào tạo')
            ->description('Quản lý danh sách các ngành học đào tạo.')
            ->headerActions([
                \Filament\Actions\Action::make('create')
                    ->label('Thêm mới')
                    ->url(fn() => \App\Filament\Resources\Majors\MajorResource::getUrl('create')),
            ])
            ->columns([
                TextColumn::make('name')->label('Tên ngành')->sortable(),
                TextColumn::make('code')->label('Mã ngành'),
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
                        ->modalDescription('Bạn có chắc chắn muốn xóa ngành học này?')
                        ->before(function (\Filament\Actions\DeleteAction $action, Major $record) {
                            $code = $record->code;
                            $name = $record->name;
                            $hasRelations = Student::whereIn('major_name', [$code, $name])->exists()
                                || Quota::whereIn('major_name', [$code, $name])->exists()
                                || AnnualQuota::whereIn('major_name', [$code, $name])->exists();

                            if ($hasRelations) {
                                Notification::make()
                                    ->danger()
                                    ->title('Không thể xóa ngành học')
                                    ->body('Ngành học này đang có học viên đăng ký hoặc nằm trong bảng chỉ tiêu năm/chỉ tiêu đợt. Không thể thực hiện xóa.')
                                    ->send();
                                $action->halt();
                            }
                        }),
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
                    ->label(fn() => 'Tất cả (' . \App\Models\Major::whereNull('deleted_at')->count() . ')')
                    ->icon('heroicon-o-academic-cap')
                    ->color(fn() => !session('majors_show_trashed', false) ? 'primary' : 'gray')
                    ->button()
                    ->size('sm')
                    ->action(function () {
                        session(['majors_show_trashed' => false]);
                    }),
                \Filament\Actions\Action::make('show_trashed')
                    ->label(fn() => 'Thùng rác (' . \App\Models\Major::onlyTrashed()->count() . ')')
                    ->icon('heroicon-o-trash')
                    ->color(fn() => session('majors_show_trashed', false) ? 'danger' : 'gray')
                    ->button()
                    ->size('sm')
                    
                    ->action(function () {
                        session(['majors_show_trashed' => true]);
                    }),
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\RestoreBulkAction::make()
                        ->label('Khôi phục')
                        ->visible(fn () => session('majors_show_trashed', false)),
                    \Filament\Actions\ForceDeleteBulkAction::make()
                        ->label('Xóa vĩnh viễn hàng loạt')
                        ->modalHeading('Xóa vĩnh viễn đã chọn')
                        ->modalDescription('Hành động này sẽ xóa hoàn toàn dữ liệu đã chọn khỏi hệ thống. Bạn chắc chắn chứ?')
                        ->visible(fn () => session('majors_show_trashed', false)),
                    \Filament\Actions\DeleteBulkAction::make()
                        ->label('Xóa hàng loạt')
                        ->modalHeading('Bỏ vào thùng rác')
                        ->modalDescription('Bạn có chắc chắn muốn bỏ các mục đã chọn vào Thùng rác? Bạn có thể khôi phục lại sau.')
                        ->before(function (\Filament\Actions\DeleteBulkAction $action, \Illuminate\Database\Eloquent\Collection $records) {
                            $hasActive = $records->contains(function ($record) {
                                $code = $record->code;
                                $name = $record->name;
                                return Student::whereIn('major_name', [$code, $name])->exists()
                                    || Quota::whereIn('major_name', [$code, $name])->exists()
                                    || AnnualQuota::whereIn('major_name', [$code, $name])->exists();
                            });

                            if ($hasActive) {
                                Notification::make()
                                    ->danger()
                                    ->title('Không thể xóa hàng loạt')
                                    ->body('Một hoặc nhiều ngành học được chọn đang có học viên hoặc nằm trong chỉ tiêu tuyển sinh/năm. Không thể thực hiện xóa.')
                                    ->send();
                                $action->halt();
                            }
                        })
                        ->visible(fn () => !session('majors_show_trashed', false)),
                ])->label('Hành động hàng loạt'),
            ])
            ->defaultSort('id', 'desc');
    }
}

