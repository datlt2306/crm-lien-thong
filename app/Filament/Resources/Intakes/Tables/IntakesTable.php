<?php

namespace App\Filament\Resources\Intakes\Tables;

use App\Models\Intake;
use App\Models\Quota;
use App\Models\Student;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class IntakesTable {
    private static function getProgramLabel(?string $programCode): string {
        return match (strtolower((string) $programCode)) {
            'regular' => 'Chính quy',
            'part_time' => 'Vừa học vừa làm',
            'distance' => 'Đào tạo từ xa',
            default => $programCode ?: 'Chưa xác định',
        };
    }

    private static function getMajorBadgeColor(?string $majorName): string {
        $palette = ['primary', 'success', 'warning', 'danger', 'info', 'gray'];
        $normalized = trim(mb_strtolower((string) $majorName));

        if ($normalized === '') {
            return 'gray';
        }

        $index = crc32($normalized) % count($palette);

        return $palette[$index];
    }

    private static function getEditUrlForQuota(Quota $record): ?string {
        if (!$record?->intake_id) {
            return null;
        }

        $baseUrl = \App\Filament\Resources\Intakes\IntakeResource::getUrl('edit', ['record' => $record->intake_id]);
        $year = null;

        if (!empty($record->intake_start_date)) {
            $year = (int) Carbon::parse($record->intake_start_date)->format('Y');
        } elseif (!empty($record->intake?->start_date)) {
            $year = (int) Carbon::parse($record->intake->start_date)->format('Y');
        }

        $query = array_filter([
            'major_name' => $record->major_name,
            'program_name' => $record->program_name,
            'year' => $year,
        ], fn($value) => !is_null($value) && $value !== '');

        if (empty($query)) {
            return $baseUrl;
        }

        return $baseUrl . '?' . http_build_query($query);
    }

    public static function configure(Table $table): Table {
        $user = \Illuminate\Support\Facades\Auth::user();

        $showTrashed = session('intakes_show_trashed', false);

        $dedupedQuotaIdsQuery = Quota::query()
            ->selectRaw('MAX(id) as id')
            ->whereNotNull('intake_id');

        if ($showTrashed) {
            $dedupedQuotaIdsQuery->onlyTrashed();
        }

        $dedupedQuotaIds = $dedupedQuotaIdsQuery->groupBy(
            'intake_id',
            DB::raw('LOWER(TRIM(COALESCE(major_name, name)))'),
            DB::raw("UPPER(TRIM(COALESCE(program_name, '')))")
        );

        $query = Quota::query();
        if ($showTrashed) {
            $query->onlyTrashed();
        }

        $query->join('intakes as i', 'i.id', '=', 'quotas.intake_id')
            ->select('quotas.*', 'i.start_date as intake_start_date', 'i.end_date as intake_end_date', 'i.status as intake_status')
            ->with(['intake'])
            ->whereIn('quotas.id', $dedupedQuotaIds);

        return $table
            ->heading('Chỉ tiêu tuyển sinh')
            ->description('Quản lý danh sách các chỉ tiêu tuyển sinh.')
            ->headerActions([
                \Filament\Actions\Action::make('create')
                    ->label('Thêm chỉ tiêu')
                    ->url(fn() => \App\Filament\Resources\Intakes\IntakeResource::getUrl('create')),
            ])
            ->query($query)
            ->recordUrl(fn($r) => ($user?->can('intake_view') && $r?->intake_id) ? self::getEditUrlForQuota($r) : null)
            ->columns([
                TextColumn::make('intake.name')
                    ->label('Tên đợt tuyển sinh')
                    ->sortable(),
                BadgeColumn::make('major_name')
                    ->label('Ngành tuyển sinh')
                    ->formatStateUsing(fn(?string $state) => $state ?: 'Chưa xác định')
                    ->color(fn(?string $state) => self::getMajorBadgeColor($state))
                    ->sortable(),
                TextColumn::make('program_name')
                    ->label('Hệ tuyển sinh')
                    ->formatStateUsing(fn($state) => self::getProgramLabel($state))
                    ->sortable(),
                BadgeColumn::make('year')
                    ->label('Năm')
                    ->getStateUsing(function (Quota $record) {
                        $startRaw = $record->intake_start_date ?? $record->intake?->start_date;
                        return $startRaw ? Carbon::parse($startRaw)->format('Y') : '—';
                    })
                    ->color('primary')
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderBy('intake_start_date', $direction);
                    }),

                BadgeColumn::make('status')
                    ->label('Trạng thái')
                    ->getStateUsing(fn(Quota $record) => $record->intake_status ?: $record->intake?->status ?: null)
                    ->formatStateUsing(fn(?string $state) => Intake::getStatusOptions()[$state] ?? ($state ?: 'Chưa cấu hình'))
                    ->color(function (?string $state): string {
                        return match ($state) {
                            Intake::STATUS_ACTIVE => 'success',   // Đang tuyển sinh
                            Intake::STATUS_UPCOMING => 'warning', // Sắp mở
                            Intake::STATUS_CLOSED => 'gray',      // Đã đóng
                            Intake::STATUS_CANCELLED => 'danger', // Đã hủy
                            default => 'gray',
                        };
                    }),

                TextColumn::make('window_range')
                    ->label('Thời gian')
                    ->getStateUsing(function (Quota $record) {
                        $startRaw = $record->intake_start_date ?? $record->intake?->start_date;
                        $endRaw = $record->intake_end_date ?? $record->intake?->end_date;
                        $start = $startRaw ? Carbon::parse($startRaw)->format('d/m/Y') : null;
                        $end = $endRaw ? Carbon::parse($endRaw)->format('d/m/Y') : null;
                        if (!$start || !$end) {
                            return 'Chưa cấu hình';
                        }
                        return "{$start} - {$end}";
                    }),

                

                

                TextColumn::make('target_quota')
                    ->label('Chỉ tiêu')
                    ->formatStateUsing(fn($state) => number_format((int) $state))
                    ->tooltip('Tổng số lượng học viên tối đa có thể tiếp nhận cho chương trình này.'),
                    // ->sortable(),

                TextColumn::make('utilization')
                    ->label('Tỷ lệ')
                    ->state(fn(Quota $record) => $record)
                    ->getStateUsing(function (Quota $record) {
                        $target = max(1, (int) $record->target_quota);
                        $percent = round(((int) $record->current_quota / $target) * 100, 2);
                        return $percent . '%';
                    })
                    ->tooltip('Phần trăm hoàn thành chỉ tiêu (Chỉ tính những học viên đã xác nhận nộp lệ phí).')
                    ->color(
                        fn($state) =>
                        (float)str_replace('%', '', $state) > 90 ? 'danger' : ((float)str_replace('%', '', $state) > 70 ? 'warning' : 'success')
                    ),

                TextColumn::make('students_count')
                    ->label('Học viên')
                    ->state(fn(Quota $record) => Student::where('quota_id', $record->id)->count())
                    ->tooltip('Tổng số lượng học viên đã đăng ký vào chương trình này (Bao gồm tất cả trạng thái).'),
                    // ->sortable(false),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i:s'),
                    // ->sortable()
                    // ->toggleable(isToggledHiddenByDefault: true),
            ])
            // ->filters([
            //     \Filament\Tables\Filters\SelectFilter::make('status')
            //         ->label('Trạng thái')
            //         ->options(Intake::getStatusOptions())
            //         ->query(function ($query, array $data) {
            //             if (!isset($data['value']) || $data['value'] === '') {
            //                 return $query;
            //             }
            //             return $query->whereHas('intake', fn($q) => $q->where('status', $data['value']));
            //         }),

            // ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('edit')
                        ->label('Chỉnh sửa')
                        ->url(fn($record) => self::getEditUrlForQuota($record))
                        ->visible(fn() => \Illuminate\Support\Facades\Auth::user()?->can('intake_update')),
                    \Filament\Actions\Action::make('toggle_active')
                        ->label(fn($record) => ($record->intake_status ?: $record->intake?->status) === Intake::STATUS_ACTIVE ? 'Đóng tuyển sinh' : 'Mở tuyển sinh')
                        ->icon(fn($record) => ($record->intake_status ?: $record->intake?->status) === Intake::STATUS_ACTIVE ? 'heroicon-m-no-symbol' : 'heroicon-m-check-circle')
                        ->color(fn($record) => ($record->intake_status ?: $record->intake?->status) === Intake::STATUS_ACTIVE ? 'danger' : 'success')
                        ->action(function ($record) {
                            $intake = $record->intake;
                            if ($intake) {
                                $newStatus = $intake->status === Intake::STATUS_ACTIVE ? Intake::STATUS_CLOSED : Intake::STATUS_ACTIVE;
                                $intake->update(['status' => $newStatus]);
                            }
                        })
                        ->requiresConfirmation(),
                    \Filament\Actions\DeleteAction::make()
                        ->label('Xóa')
                        ->modalHeading('Xóa đợt tuyển sinh')
                        ->modalDescription('Bạn có chắc chắn muốn xóa đợt tuyển sinh này? Hồ sơ sẽ được chuyển vào Thùng rác.')
                        ->before(function (\Filament\Actions\DeleteAction $action, Quota $record) {
                            $hasStudents = $record->current_quota > 0 || Student::where('quota_id', $record->id)->exists();
                            if ($hasStudents) {
                                Notification::make()
                                    ->danger()
                                    ->title('Không thể xóa')
                                    ->body('Đợt tuyển sinh này đã có học viên đăng ký tuyển sinh. Không thể thực hiện xóa.')
                                    ->send();
                                $action->halt();
                            }
                        })
                        ->visible(fn() => \Illuminate\Support\Facades\Auth::user()?->can('intake_delete')),
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
                    ->label(fn() => 'Tất cả (' . Quota::query()->whereIn('id', Quota::query()
                        ->selectRaw('MAX(id) as id')
                        ->whereNotNull('intake_id')
                        ->groupBy(
                            'intake_id',
                            DB::raw('LOWER(TRIM(COALESCE(major_name, name)))'),
                            DB::raw("UPPER(TRIM(COALESCE(program_name, '')))")
                        )
                    )->count() . ')')
                    ->icon('heroicon-o-calendar')
                    ->color(fn() => !session('intakes_show_trashed', false) ? 'primary' : 'gray')
                    ->button()
                    ->size('sm')
                    ->action(function () {
                        session(['intakes_show_trashed' => false]);
                    }),
                \Filament\Actions\Action::make('show_trashed')
                    ->label(fn() => 'Thùng rác (' . Quota::query()->onlyTrashed()->whereIn('id', Quota::query()
                        ->onlyTrashed()
                        ->selectRaw('MAX(id) as id')
                        ->whereNotNull('intake_id')
                        ->groupBy(
                            'intake_id',
                            DB::raw('LOWER(TRIM(COALESCE(major_name, name)))'),
                            DB::raw("UPPER(TRIM(COALESCE(program_name, '')))")
                        )
                    )->count() . ')')
                    ->icon('heroicon-o-trash')
                    ->color(fn() => session('intakes_show_trashed', false) ? 'danger' : 'gray')
                    ->button()
                    ->size('sm')
                    ->action(function () {
                        session(['intakes_show_trashed' => true]);
                    }),
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\RestoreBulkAction::make()
                        ->label('Khôi phục')
                        ->visible(fn () => session('intakes_show_trashed', false)),
                    \Filament\Actions\ForceDeleteBulkAction::make()
                        ->label('Xóa vĩnh viễn hàng loạt')
                        ->modalHeading('Xóa vĩnh viễn đã chọn')
                        ->modalDescription('Hành động này sẽ xóa hoàn toàn dữ liệu đã chọn khỏi hệ thống. Bạn chắc chắn chứ?')
                        ->visible(fn () => session('intakes_show_trashed', false)),
                    \Filament\Actions\DeleteBulkAction::make()
                        ->label('Xóa hàng loạt')
                        ->modalHeading('Bỏ vào thùng rác')
                        ->modalDescription('Bạn có chắc chắn muốn bỏ các mục đã chọn vào Thùng rác? Bạn có thể khôi phục lại sau.')
                        ->visible(fn () => !session('intakes_show_trashed', false)),
                ])->label('Hành động hàng loạt'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
