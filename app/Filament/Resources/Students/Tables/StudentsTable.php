<?php

namespace App\Filament\Resources\Students\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                TextColumn::make('current_college')
                    ->label('Trường đang học')
                    ->searchable(),
                TextColumn::make('target_university')
                    ->label('Trường muốn học')
                    ->searchable(),
                TextColumn::make('major')
                    ->label('Ngành học')
                    ->searchable(),
                TextColumn::make('source')
                    ->label('Nguồn')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Tình trạng')
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
                    ->visible(fn(\App\Models\Student $record): bool => $record->status !== 'enrolled')
                    ->action(function (\App\Models\Student $record) {
                        $record->update(['status' => 'enrolled']);

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
