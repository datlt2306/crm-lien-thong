<?php

namespace App\Filament\Resources\Quotas\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use App\Models\Student;
use App\Models\Quota;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use App\Filament\Resources\Quotas\QuotaResource;

class QuotasTable {
    public static function configure(Table $table): Table {
        $user = \Illuminate\Support\Facades\Auth::user();
        $canEdit = $user && in_array($user->role, ['super_admin', ]);

        return $table
            ->recordUrl(fn($record) => $canEdit ? QuotaResource::getUrl('edit', ['record' => $record]) : null)
            ->columns([
                TextColumn::make('intake.name')
                    ->label('Đợt tuyển sinh')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => $record->intake ?
                        'Từ ' . $record->intake->start_date?->format('d/m/Y') .
                        ' đến ' . $record->intake->end_date?->format('d/m/Y') : null)
                    ->badge()
                    ->color('info'),

                TextColumn::make('name')
                    ->label('Tên chương trình')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => trim($record->major_name . ($record->program_name ? ' - ' . $record->program_name : ''))),

                TextColumn::make('major_name')
                    ->label('Ngành đào tạo')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('program_name')
                    ->label('Hệ đào tạo')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('intake.start_date')
                    ->label('Năm')
                    ->formatStateUsing(function ($record) {
                        return $record->intake?->start_date?->format('Y');
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('intake.start_date')
                    ->label('Tháng')
                    ->formatStateUsing(function ($record) {
                        return $record->intake?->start_date?->format('m');
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('target_quota')
                    ->label('Chỉ tiêu')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('current_quota')
                    ->label('Đã đạt')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('available_slots')
                    ->label('Còn trống')
                    ->getStateUsing(function ($record) {
                        return $record->available_slots;
                    })
                    ->color(fn($state) => $state <= 0 ? 'danger' : ($state <= 10 ? 'warning' : 'success')),

                BadgeColumn::make('status')
                    ->label('Trạng Thái')
                    ->formatStateUsing(function ($state, $record) {
                        $end = $record?->intake?->end_date;
                        if ($end && $end->format('Y-m-d') < now()->toDateString()) {
                            return 'Hết hạn tuyển sinh';
                        }
                        return \App\Models\Quota::getStatusOptions()[$state] ?? $state;
                    })
                    ->color(function ($state, $record) {
                        $end = $record?->intake?->end_date;
                        if ($end && $end->format('Y-m-d') < now()->toDateString()) {
                            return 'gray';
                        }
                        return match ($state) {
                            \App\Models\Quota::STATUS_ACTIVE => 'success',
                            \App\Models\Quota::STATUS_INACTIVE => 'warning',
                            \App\Models\Quota::STATUS_FULL => 'danger',
                            default => 'gray',
                        };
                    }),


                TextColumn::make('tuition_fee')
                    ->label('Học phí')
                    ->money('VND')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(\App\Models\Quota::getStatusOptions()),

                \Filament\Tables\Filters\SelectFilter::make('intake_id')
                    ->label('Đợt tuyển sinh')
                    ->relationship('intake', 'name')
                    ->searchable()
                    ->preload(),



            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Xem chi tiết')
                        ->visible(fn() => \Illuminate\Support\Facades\Auth::user() &&
                            !in_array(\Illuminate\Support\Facades\Auth::user()->role, ['ctv'])),
                    EditAction::make()
                        ->label('Chỉnh sửa')
                        ->visible(fn() => \Illuminate\Support\Facades\Auth::user() &&
                            in_array(\Illuminate\Support\Facades\Auth::user()->role, ['super_admin', ])),
                    DeleteAction::make()
                        ->label('Xóa')
                        ->modalHeading('Xóa chỉ tiêu tuyển sinh')
                        ->modalDescription('Nếu chỉ tiêu này đã có học viên đăng ký, hệ thống sẽ tự động chuyển sang trạng thái Tạm dừng thay vì xóa vĩnh viễn.')
                        ->modalSubmitActionLabel('Xóa/Tạm dừng')
                        ->visible(fn() => \Illuminate\Support\Facades\Auth::user() &&
                            in_array(\Illuminate\Support\Facades\Auth::user()->role, ['super_admin', ]))
                        ->action(function ($record) {
                            $hasStudents = Student::where('quota_id', $record->id)->exists();

                            if ($hasStudents) {
                                $record->update(['status' => Quota::STATUS_INACTIVE]);
                                Notification::make()
                                    ->title('Đã chuyển sang Tạm dừng')
                                    ->body("Chỉ tiêu này đã có học viên đăng ký nên không thể xóa. Trạng thái đã được cập nhật thành Tạm dừng.")
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
                    ->tooltip('Các hành động khả dụng')
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->modalHeading('Xóa các chỉ tiêu đã chọn')
                        ->modalDescription('Các chỉ tiêu đã có học viên sẽ được tự động chuyển sang trạng thái Tạm dừng.')
                        ->modalSubmitActionLabel('Bắt đầu xử lý')
                        ->visible(fn() => \Illuminate\Support\Facades\Auth::user() &&
                            in_array(\Illuminate\Support\Facades\Auth::user()->role, ['super_admin']))
                        ->action(function ($records) {
                            $deleted = 0;
                            $deactivated = 0;

                            foreach ($records as $record) {
                                $hasStudents = Student::where('quota_id', $record->id)->exists();

                                if ($hasStudents) {
                                    $record->update(['status' => Quota::STATUS_INACTIVE]);
                                    $deactivated++;
                                } else {
                                    $record->delete();
                                    $deleted++;
                                }
                            }

                            Notification::make()
                                ->title('Xử lý hoàn tất')
                                ->body("Đã xóa $deleted chỉ tiêu và chuyển Tạm dừng $deactivated chỉ tiêu có học viên.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
