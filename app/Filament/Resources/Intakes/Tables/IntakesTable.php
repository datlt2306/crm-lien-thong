<?php

namespace App\Filament\Resources\Intakes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;

class IntakesTable {
    public static function configure(Table $table): Table {
        $user = \Illuminate\Support\Facades\Auth::user();
        $canEdit = $user && in_array($user->role, ['super_admin', 'organization_owner']);

        return $table
            ->recordUrl(fn($r) => ($canEdit && $r) ? \App\Filament\Resources\Intakes\IntakeResource::getUrl('edit', ['record' => $r]) : null)
            ->columns([
                TextColumn::make('name')
                    ->label('Tên đợt tuyển sinh')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('organization.name')
                    ->label('Tổ chức')
                    ->searchable()
                    ->sortable()
                    ->visible(fn() => \Illuminate\Support\Facades\Auth::user() &&
                        !in_array(\Illuminate\Support\Facades\Auth::user()->role, ['ctv'])),


                BadgeColumn::make('status')
                    ->label('Trạng thái')
                    ->formatStateUsing(function ($state) {
                        return \App\Models\Intake::getStatusOptions()[$state] ?? $state;
                    })
                    ->colors([
                        'warning' => \App\Models\Intake::STATUS_UPCOMING,
                        'success' => \App\Models\Intake::STATUS_ACTIVE,
                        'danger' => \App\Models\Intake::STATUS_CLOSED,
                        'gray' => \App\Models\Intake::STATUS_CANCELLED,
                    ]),

                TextColumn::make('start_date')
                    ->label('Bắt đầu')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('Kết thúc')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('majors_recruiting')
                    ->label('Ngành tuyển sinh')
                    ->getStateUsing(function ($record) {
                        // Ưu tiên: Lấy từ relationship (Quotas đã tạo cho đợt tuyển này)
                        $linkedQuotas = $record->quotas()
                            ->where('status', \App\Models\Quota::STATUS_ACTIVE)
                            ->get();

                        if ($linkedQuotas->isNotEmpty()) {
                            $majors = $linkedQuotas->pluck('major_name')->unique()->filter()->toArray();
                            return !empty($majors) ? implode(', ', $majors) : 'Chưa cấu hình';
                        }

                        // Fallback: Lấy tất cả annual_quotas của năm đó (chưa liên kết cụ thể)
                        $year = $record->start_date?->format('Y') ?? now()->format('Y');
                        $majors = \Illuminate\Support\Facades\DB::table('annual_quotas')
                            ->where('organization_id', $record->organization_id)
                            ->where('year', $year)
                            ->where('status', \App\Models\AnnualQuota::STATUS_ACTIVE)
                            ->select('major_name')
                            ->distinct()
                            ->pluck('major_name')
                            ->filter()
                            ->toArray();
                        
                        if (empty($majors)) {
                            return 'Chưa cấu hình';
                        }
                        return implode(', ', $majors) . ' *';
                    })
                    ->wrap()
                    ->tooltip(function ($record) {
                        $linkedQuotas = $record->quotas()
                            ->where('status', \App\Models\Quota::STATUS_ACTIVE)
                            ->get();

                        if ($linkedQuotas->isNotEmpty()) {
                            $lines = ['✅ Đã chỉ định đợt tuyển cụ thể:'];
                            foreach ($linkedQuotas as $q) {
                                $remaining = $q->target_quota - $q->current_quota;
                                $lines[] = "• {$q->major_name} ({$q->program_name}): {$q->current_quota}/{$q->target_quota} (còn {$remaining})";
                            }
                            return implode("\n", $lines);
                        }

                        // Fallback
                        $year = $record->start_date?->format('Y') ?? now()->format('Y');
                        $details = \Illuminate\Support\Facades\DB::table('annual_quotas')
                            ->where('organization_id', $record->organization_id)
                            ->where('year', $year)
                            ->where('status', \App\Models\AnnualQuota::STATUS_ACTIVE)
                            ->select('major_name', 'program_name', 'target_quota', 'current_quota')
                            ->get();
                        
                        if ($details->isEmpty()) {
                            return 'Chưa có chỉ tiêu năm nào được cấu hình';
                        }
                        
                        $lines = ["⚠️ Chưa chỉ định đợt cụ thể (hiện tất cả năm {$year}):"];
                        foreach ($details as $d) {
                            $remaining = $d->target_quota - $d->current_quota;
                            $lines[] = "• {$d->major_name} ({$d->program_name}): {$d->current_quota}/{$d->target_quota} (còn {$remaining})";
                        }
                        return implode("\n", $lines);
                    }),

                TextColumn::make('total_target_quota')
                    ->label('Tổng chỉ tiêu')
                    ->getStateUsing(function ($record) {
                        return number_format($record->total_target_quota);
                    })
                    ->description(fn($record) => 'Đã tuyển: ' . number_format($record->total_current_quota)),

                TextColumn::make('quota_utilization')
                    ->label('Tỷ lệ')
                    ->getStateUsing(function ($record) {
                        return $record->quota_utilization . '%';
                    })
                    ->color(
                        fn($state) =>
                        (float)str_replace('%', '', $state) > 90 ? 'danger' : ((float)str_replace('%', '', $state) > 70 ? 'warning' : 'success')
                    ),

                TextColumn::make('students_count')
                    ->label('Học viên')
                    ->counts('students')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(\App\Models\Intake::getStatusOptions()),

                \Filament\Tables\Filters\SelectFilter::make('organization_id')
                    ->label('Tổ chức')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn() => \Illuminate\Support\Facades\Auth::user() &&
                        !in_array(\Illuminate\Support\Facades\Auth::user()->role, ['ctv'])),


            ])
            ->recordActions([
                EditAction::make()
                    ->label('Chỉnh sửa')
                    ->visible(fn() => \Illuminate\Support\Facades\Auth::user() &&
                        in_array(\Illuminate\Support\Facades\Auth::user()->role, ['super_admin', 'organization_owner'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->modalHeading('Xóa đợt tuyển sinh đã chọn')
                        ->modalDescription('Bạn có chắc chắn muốn xóa các đợt tuyển sinh đã chọn? Hành động này không thể hoàn tác.')
                        ->modalSubmitActionLabel('Xóa')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(fn() => \Illuminate\Support\Facades\Auth::user() &&
                            in_array(\Illuminate\Support\Facades\Auth::user()->role, ['super_admin', 'organization_owner'])),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
