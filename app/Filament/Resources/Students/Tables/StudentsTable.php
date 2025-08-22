<?php

namespace App\Filament\Resources\Students\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\Student;

class StudentsTable {
    public static function configure(Table $table): Table {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Họ và tên')
                    ->searchable(),
                TextColumn::make('contact')
                    ->label('Liên hệ')
                    ->state(fn($record) => $record)
                    ->formatStateUsing(function ($record) {
                        $phone = $record->phone ?: '';
                        $email = $record->email ?: '';
                        $lines = [];
                        if ($phone) {
                            $lines[] = '📞 ' . e($phone);
                        }
                        if ($email) {
                            $lines[] = '✉️ ' . e($email);
                        }
                        return implode('<br>', $lines) ?: '—';
                    })
                    ->html()
                    ->searchable(query: function ($query, $search) {
                        return $query->where(function ($q) use ($search) {
                            $q->where('phone', 'like', "%$search%")
                                ->orWhere('email', 'like', "%$search%");
                        });
                    }),
                TextColumn::make('collaborator.full_name')
                    ->label('Người giới thiệu')
                    ->searchable()
                    ->description(fn($record) => $record->collaborator?->email)
                    ->badge()
                    ->color('info')
                    ->placeholder('Không có'),
                TextColumn::make('target_university')
                    ->label('Trường muốn học')
                    ->searchable(),
                TextColumn::make('major')
                    ->label('Ngành học')
                    ->searchable(),
                TextColumn::make('dob')
                    ->label('Ngày sinh')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('intake_month')
                    ->label('Đợt tuyển')
                    ->formatStateUsing(fn ($state) => $state ? "Tháng {$state}" : '—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('program_type')
                    ->label('Hệ liên thông')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'REGULAR' => 'Chính quy',
                        'PART_TIME' => 'Vừa học vừa làm',
                        default => '—'
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'REGULAR' => 'success',
                        'PART_TIME' => 'warning',
                        default => 'gray'
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('address')
                    ->label('Địa chỉ')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('source')
                    ->label('Nguồn')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Tình trạng')
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            Student::STATUS_NEW => 'gray',
                            Student::STATUS_CONTACTED => 'blue',
                            Student::STATUS_SUBMITTED => 'yellow',
                            Student::STATUS_APPROVED => 'orange',
                            Student::STATUS_ENROLLED => 'success',
                            Student::STATUS_REJECTED => 'danger',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(fn (string $state): string => Student::getStatusOptions()[$state] ?? $state)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Ngày cập nhật')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('mark_enrolled')
                    ->label('Đánh dấu nhập học')
                    ->icon('heroicon-o-academic-cap')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Đánh dấu nhập học')
                    ->modalDescription('Bạn có chắc chắn muốn đánh dấu sinh viên này đã nhập học? Hệ thống sẽ tự động cập nhật commission cho CTV cấp 2.')
                    ->modalSubmitActionLabel('Xác nhận')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(fn(Student $record): bool => $record->status !== Student::STATUS_ENROLLED)
                    ->action(function (Student $record) {
                        $record->update(['status' => Student::STATUS_ENROLLED]);

                        // Cập nhật commission khi student nhập học
                        $commissionService = new \App\Services\CommissionService();
                        $commissionService->updateCommissionsOnEnrollment($record);

                        \Filament\Notifications\Notification::make()
                            ->title('Đã đánh dấu nhập học')
                            ->body('Commission đã được cập nhật tự động.')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
