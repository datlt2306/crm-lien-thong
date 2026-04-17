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

    public static function configure(Table $table): Table {
        $user = \Illuminate\Support\Facades\Auth::user();
        $canEdit = $user && in_array($user->role, ['super_admin', 'organization_owner']);

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
            ->with(['intake.organization'])
            ->whereIn('quotas.id', $dedupedQuotaIds);

        if ($user?->role === 'organization_owner') {
            $org = \App\Models\Organization::where('organization_owner_id', $user->id)->first();
            $query->where('organization_id', $org?->id ?? 0);
        } elseif ($user?->role === 'ctv') {
            $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();
            $query->where('organization_id', $collaborator?->organization_id ?? 0);
        }

        return $table
            ->query($query)
            ->recordUrl(fn($r) => ($canEdit && $r?->intake_id) ? \App\Filament\Resources\Intakes\IntakeResource::getUrl('edit', ['record' => $r->intake_id]) : null)
            ->columns([
                TextColumn::make('intake.name')
                    ->label('Tên đợt tuyển sinh')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Trạng thái')
                    ->getStateUsing(function (Quota $record) {
                        $intakeStatus = $record->intake_status ?: $record->intake?->status;
                        return Intake::getStatusOptions()[$intakeStatus] ?? ($intakeStatus ?: 'Chưa cấu hình');
                    })
                    ->colors([
                        'warning' => Intake::STATUS_UPCOMING,
                        'success' => Intake::STATUS_ACTIVE,
                        'danger' => Intake::STATUS_CLOSED,
                        'gray' => Intake::STATUS_CANCELLED,
                    ]),

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

                TextColumn::make('major_name')
                    ->label('Ngành tuyển sinh')
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
                    ->sortable(),

                TextColumn::make('utilization')
                    ->label('Tỷ lệ')
                    ->state(fn(Quota $record) => $record)
                    ->getStateUsing(function (Quota $record) {
                        $target = max(1, (int) $record->target_quota);
                        $percent = round(((int) $record->current_quota / $target) * 100, 2);
                        return $percent . '%';
                    })
                    ->color(
                        fn($state) =>
                        (float)str_replace('%', '', $state) > 90 ? 'danger' : ((float)str_replace('%', '', $state) > 70 ? 'warning' : 'success')
                    ),

                TextColumn::make('students_count')
                    ->label('Học viên')
                    ->state(fn(Quota $record) => Student::where('quota_id', $record->id)->count())
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
                    Action::make('edit_intake')
                        ->label('Chỉnh sửa')
                        ->icon('heroicon-o-pencil-square')
                        ->url(fn(Quota $record) => \App\Filament\Resources\Intakes\IntakeResource::getUrl('edit', ['record' => $record->intake_id]))
                        ->visible(fn() => \Illuminate\Support\Facades\Auth::user() &&
                            in_array(\Illuminate\Support\Facades\Auth::user()->role, ['super_admin', 'organization_owner'])),
                ])
                    ->label('Hành động')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->button()
                    ->size('sm')
                    ->tooltip('Các hành động khả dụng'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
