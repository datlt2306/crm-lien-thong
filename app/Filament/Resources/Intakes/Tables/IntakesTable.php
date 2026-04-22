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
        return match (strtoupper((string) $programCode)) {
            'REGULAR' => 'Chính quy',
            'PART_TIME' => 'Vừa học vừa làm',
            'DISTANCE' => 'Đào tạo từ xa',
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

        $dedupedQuotaIds = Quota::query()
            ->selectRaw('MAX(id) as id')
            ->whereNotNull('intake_id')
            ->groupBy(
                'intake_id',
                DB::raw('LOWER(TRIM(COALESCE(major_name, name)))'),
                DB::raw("UPPER(TRIM(COALESCE(program_name, '')))")
            );

        $query = Quota::query()
            ->join('intakes as i', 'i.id', '=', 'quotas.intake_id')
            ->select('quotas.*', 'i.start_date as intake_start_date', 'i.end_date as intake_end_date', 'i.status as intake_status')
            ->with(['intake'])
            ->whereIn('quotas.id', $dedupedQuotaIds);

        return $table
            ->query($query)
            ->recordUrl(fn($r) => ($user?->can('intake_view') && $r?->intake_id) ? self::getEditUrlForQuota($r) : null)
            ->columns([
                TextColumn::make('intake.name')
                    ->label('Tên đợt tuyển sinh')
                    ->searchable()
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

                BadgeColumn::make('major_name')
                    ->label('Ngành tuyển sinh')
                    ->formatStateUsing(fn(?string $state) => $state ?: 'Chưa xác định')
                    ->color(fn(?string $state) => self::getMajorBadgeColor($state))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('program_name')
                    ->label('Hệ tuyển sinh')
                    ->formatStateUsing(fn($state) => self::getProgramLabel($state))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('target_quota')
                    ->label('Chỉ tiêu')
                    ->formatStateUsing(fn($state) => number_format((int) $state))
                    ->tooltip('Tổng số lượng học viên tối đa có thể tiếp nhận cho chương trình này.')
                    ->sortable(),

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
                    ->tooltip('Tổng số lượng học viên đã đăng ký vào chương trình này (Bao gồm tất cả trạng thái).')
                    ->sortable(false),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(Intake::getStatusOptions())
                    ->query(function ($query, array $data) {
                        if (!isset($data['value']) || $data['value'] === '') {
                            return $query;
                        }
                        return $query->whereHas('intake', fn($q) => $q->where('status', $data['value']));
                    }),

            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('edit')
                        ->label('Chỉnh sửa')
                        ->url(fn($record) => self::getEditUrlForQuota($record))
                        ->visible(fn() => \Illuminate\Support\Facades\Auth::user()?->can('intake_update')),
                    DeleteAction::make()
                        ->label('Xóa')
                        ->modalHeading('Xóa chỉ tiêu tuyển sinh')
                        ->modalDescription('Nếu chỉ tiêu này đã có học viên đăng ký, hệ thống sẽ tự động chuyển sang trạng thái Tạm dừng thay vì xóa vĩnh viễn.')
                        ->modalSubmitActionLabel('Xóa/Tạm dừng')
                        ->visible(fn() => \Illuminate\Support\Facades\Auth::user()?->can('intake_delete'))
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
                    ->tooltip('Các hành động khả dụng'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->modalHeading('Xóa các chỉ tiêu đã chọn')
                        ->modalDescription('Các chỉ tiêu đã có học viên sẽ được tự động chuyển sang trạng thái Tạm dừng.')
                        ->modalSubmitActionLabel('Bắt đầu xử lý')
                        ->visible(fn() => \Illuminate\Support\Facades\Auth::user()?->can('quota_delete'))
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
