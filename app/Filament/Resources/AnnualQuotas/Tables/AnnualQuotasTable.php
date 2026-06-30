<?php

namespace App\Filament\Resources\AnnualQuotas\Tables;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use App\Models\Student;
use App\Models\AnnualQuota;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class AnnualQuotasTable {
    private static function getProgramLabel(?string $programCode): string {
        return match (strtolower((string) $programCode)) {
            'regular' => 'Chính quy',
            'part_time' => 'Vừa học vừa làm',
            'distance' => 'Đào tạo từ xa',
            default => $programCode ?: 'Chưa xác định',
        };
    }

    public static function configure(Table $table): Table {
        $user = \Illuminate\Support\Facades\Auth::user();
        $canEdit = $user && $user->can('annual_quota_update');
        $canDelete = $user && $user->can('annual_quota_delete');

        return $table
            ->heading('Chỉ tiêu năm')
            ->description('Quản lý danh sách các chỉ tiêu năm.')
            ->headerActions([
                \Filament\Actions\Action::make('create')
                    ->label('Thêm chỉ tiêu năm')
                    ->url(fn() => \App\Filament\Resources\AnnualQuotas\AnnualQuotaResource::getUrl('create'))
                    ->visible(fn() => \Illuminate\Support\Facades\Auth::user()?->can('annual_quota_create')),
            ])
            ->recordUrl(fn($r) => ($canEdit && $r) ? \App\Filament\Resources\AnnualQuotas\AnnualQuotaResource::getUrl('edit', ['record' => $r]) : null)
            ->columns([
                TextColumn::make('major_name')
                    ->label('Ngành')
                    ->sortable(),

                TextColumn::make('program_name')
                    ->label('Hệ đào tạo')
                    ->formatStateUsing(fn($state) => self::getProgramLabel($state))
                    ->sortable(),

                TextColumn::make('year')
                    ->label('Năm')
                    ->sortable(),

                TextColumn::make('target_quota')
                    ->label('Chỉ tiêu')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('current_quota')
                    ->label('Đã tuyển')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('available_slots')
                    ->label('Còn lại')
                    ->getStateUsing(fn($record) => max(0, ($record?->target_quota ?? 0) - ($record?->current_quota ?? 0)))
                    ->color(fn($state) => $state <= 0 ? 'danger' : ($state <= 10 ? 'warning' : 'success')),

                BadgeColumn::make('status')
                    ->label('Trạng thái')
                    ->formatStateUsing(fn($state) => \App\Models\AnnualQuota::getStatusOptions()[$state] ?? $state ?? 'N/A')
                    ->colors([
                        'success' => \App\Models\AnnualQuota::STATUS_ACTIVE,
                        'warning' => \App\Models\AnnualQuota::STATUS_INACTIVE,
                        'gray' => \App\Models\AnnualQuota::STATUS_FULL,
                    ]),


                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->date('d/m/Y')
                    ->sortable()
                    // ->toggleable(isToggledHiddenByDefault: true),
            ])
            // ->filters([
            //     \Filament\Tables\Filters\SelectFilter::make('major_name')
            //         ->label('Ngành')
            //         ->options(fn() => \Illuminate\Support\Facades\DB::table('annual_quotas')->whereNotNull('major_name')->distinct()->pluck('major_name', 'major_name')->toArray())
            //         ->searchable()
            //         ->preload(),
            //     \Filament\Tables\Filters\SelectFilter::make('program_name')
            //         ->label('Hệ đào tạo')
            //         ->options(function () {
            //             $values = \Illuminate\Support\Facades\DB::table('annual_quotas')
            //                 ->whereNotNull('program_name')
            //                 ->distinct()
            //                 ->pluck('program_name')
            //                 ->toArray();

            //             $options = [];
            //             foreach ($values as $value) {
            //                 $options[$value] = self::getProgramLabel($value);
            //             }

            //             return $options;
            //         })
            //         ->searchable()
            //         ->preload(),
            //     \Filament\Tables\Filters\SelectFilter::make('year')
            //         ->label('Năm')
            //         ->options(fn() => collect(range(now()->year - 2, now()->year + 2))->mapWithKeys(fn($y) => [$y => (string) $y])),
            //     \Filament\Tables\Filters\SelectFilter::make('status')
            //         ->label('Trạng thái')
            //         ->options(\App\Models\AnnualQuota::getStatusOptions()),
            // ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->visible(fn() => $canEdit),
                    \Filament\Actions\Action::make('toggle_active')
                        ->label(fn($record) => $record->status === AnnualQuota::STATUS_ACTIVE ? 'Vô hiệu hóa' : 'Kích hoạt')
                        ->icon(fn($record) => $record->status === AnnualQuota::STATUS_ACTIVE ? 'heroicon-m-no-symbol' : 'heroicon-m-check-circle')
                        ->color(fn($record) => $record->status === AnnualQuota::STATUS_ACTIVE ? 'danger' : 'success')
                        ->action(function ($record) {
                            $newStatus = $record->status === AnnualQuota::STATUS_ACTIVE ? AnnualQuota::STATUS_INACTIVE : AnnualQuota::STATUS_ACTIVE;
                            $record->update(['status' => $newStatus]);
                        })
                        ->requiresConfirmation(),
                    \Filament\Actions\DeleteAction::make()
                        ->label('Xóa')
                        ->modalHeading('Xóa chỉ tiêu năm')
                        ->modalDescription('Bạn có chắc chắn muốn xóa chỉ tiêu năm này? Hồ sơ sẽ được chuyển vào Thùng rác.')
                        ->visible(fn() => $canDelete),
                    \Filament\Actions\RestoreAction::make()
                        ->label('Khôi phục'),
                    \Filament\Actions\ForceDeleteAction::make()
                        ->label('Xóa vĩnh viễn'),
                ])->label('Hành động')->icon('heroicon-m-ellipsis-vertical')->color('gray')->button()->size('sm'),
            ])
            ->toolbarActions([
                \Filament\Actions\Action::make('show_active')
                    ->label(fn() => 'Tất cả (' . \App\Models\AnnualQuota::whereNull('deleted_at')->count() . ')')
                    ->icon('heroicon-o-presentation-chart-line')
                    ->color(fn() => !session('annual_quotas_show_trashed', false) ? 'primary' : 'gray')
                    ->button()
                    ->size('sm')
                    ->action(function () {
                        session(['annual_quotas_show_trashed' => false]);
                    }),
                \Filament\Actions\Action::make('show_trashed')
                    ->label(fn() => 'Thùng rác (' . \App\Models\AnnualQuota::onlyTrashed()->count() . ')')
                    ->icon('heroicon-o-trash')
                    ->color(fn() => session('annual_quotas_show_trashed', false) ? 'danger' : 'gray')
                    ->button()
                    ->size('sm')
                    ->visible(fn() => \App\Models\AnnualQuota::onlyTrashed()->count() > 0)
                    ->action(function () {
                        session(['annual_quotas_show_trashed' => true]);
                    }),
                BulkActionGroup::make(
                    session('annual_quotas_show_trashed', false)
                        ? [
                            \Filament\Actions\RestoreBulkAction::make()
                                ->label('Khôi phục đã chọn'),
                            \Filament\Actions\ForceDeleteBulkAction::make()
                                ->label('Xóa vĩnh viễn đã chọn')
                                ->modalHeading('Xóa vĩnh viễn chỉ tiêu năm đã chọn')
                                ->modalDescription('Hành động này sẽ xóa hoàn toàn các chỉ tiêu năm đã chọn khỏi hệ thống. Bạn chắc chắn chứ?'),
                        ]
                        : [
                            \Filament\Actions\DeleteBulkAction::make()
                                ->label('Bỏ vào thùng rác')
                                ->modalHeading('Bỏ chỉ tiêu năm đã chọn vào thùng rác')
                                ->modalDescription('Bạn có chắc chắn muốn bỏ các chỉ tiêu năm đã chọn vào Thùng rác? Bạn có thể khôi phục lại sau.')
                                ->visible(fn() => \Illuminate\Support\Facades\Auth::user()?->can('annual_quota_delete')),
                        ]
                )
                ->label('Hành động hàng loạt'),
            ])
            ->defaultSort('year', 'desc');
    }
}
