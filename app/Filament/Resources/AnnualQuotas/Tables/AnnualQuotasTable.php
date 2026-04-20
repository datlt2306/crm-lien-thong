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
        return match (strtoupper((string) $programCode)) {
            'REGULAR' => 'Chính quy',
            'PART_TIME' => 'Vừa học vừa làm',
            'DISTANCE' => 'Đào tạo từ xa',
            default => $programCode ?: 'Chưa xác định',
        };
    }

    public static function configure(Table $table): Table {
        $user = \Illuminate\Support\Facades\Auth::user();
        $canEdit = $user && in_array($user->role, ['super_admin', ]);

        return $table
            ->recordUrl(fn($r) => ($canEdit && $r) ? \App\Filament\Resources\AnnualQuotas\AnnualQuotaResource::getUrl('edit', ['record' => $r]) : null)
            ->columns([
                TextColumn::make('major_name')
                    ->label('Ngành')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('program_name')
                    ->label('Hệ đào tạo')
                    ->formatStateUsing(fn($state) => self::getProgramLabel($state))
                    ->searchable()
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
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('major_name')
                    ->label('Ngành')
                    ->options(fn() => \Illuminate\Support\Facades\DB::table('annual_quotas')->whereNotNull('major_name')->distinct()->pluck('major_name', 'major_name')->toArray())
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('program_name')
                    ->label('Hệ đào tạo')
                    ->options(function () {
                        $values = \Illuminate\Support\Facades\DB::table('annual_quotas')
                            ->whereNotNull('program_name')
                            ->distinct()
                            ->pluck('program_name')
                            ->toArray();

                        $options = [];
                        foreach ($values as $value) {
                            $options[$value] = self::getProgramLabel($value);
                        }

                        return $options;
                    })
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('year')
                    ->label('Năm')
                    ->options(fn() => collect(range(now()->year - 2, now()->year + 2))->mapWithKeys(fn($y) => [$y => (string) $y])),
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(\App\Models\AnnualQuota::getStatusOptions()),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->visible(fn() => $canEdit),
                    DeleteAction::make()
                        ->label('Xóa')
                        ->modalHeading('Xóa chỉ tiêu năm')
                        ->modalDescription('Nếu chỉ tiêu này đã có hồ sơ học viên, hệ thống sẽ tự động chuyển sang trạng thái Tạm dừng thay vì xóa vĩnh viễn.')
                        ->modalSubmitActionLabel('Xóa/Tạm dừng')
                        ->visible(fn() => $canEdit)
                        ->action(function ($record) {
                            // AnnualQuota có thể liên kết qua major_name/program_name/year hoặc qua quan hệ nếu có
                            // Ở đây ta check theo major_name và program_name trong Student
                            $hasStudents = Student::where('major', $record->major_name)
                                ->where('program_type', $record->program_name)
                                ->exists();

                            if ($hasStudents) {
                                $record->update(['status' => AnnualQuota::STATUS_INACTIVE]);
                                Notification::make()
                                    ->title('Đã chuyển sang Tạm dừng')
                                    ->body("Chỉ tiêu năm này đã có học viên đăng ký nên không thể xóa. Trạng thái đã được cập nhật thành Tạm dừng.")
                                    ->warning()
                                    ->send();
                            } else {
                                $record->delete();
                                Notification::make()
                                    ->title('Đã xóa vĩnh viễn')
                                    ->success() ->send();
                            }
                        }),
                ])->label('Hành động')->icon('heroicon-m-ellipsis-vertical')->color('gray')->button()->size('sm'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->modalHeading('Xóa các chỉ tiêu năm đã chọn')
                        ->modalDescription('Các chỉ tiêu đã có học viên sẽ được tự động chuyển sang trạng thái Tạm dừng.')
                        ->modalSubmitActionLabel('Bắt đầu xử lý')
                        ->visible(fn() => $canEdit)
                        ->action(function ($records) {
                            $deleted = 0;
                            $deactivated = 0;

                            foreach ($records as $record) {
                                $hasStudents = Student::where('major', $record->major_name)
                                    ->where('program_type', $record->program_name)
                                    ->exists();

                                if ($hasStudents) {
                                    $record->update(['status' => AnnualQuota::STATUS_INACTIVE]);
                                    $deactivated++;
                                } else {
                                    $record->delete();
                                    $deleted++;
                                }
                            }

                            Notification::make()
                                ->title('Xử lý hoàn tất')
                                ->body("Đã xóa $deleted chỉ tiêu năm và chuyển Tạm dừng $deactivated chỉ tiêu có học viên.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('year', 'desc');
    }
}
