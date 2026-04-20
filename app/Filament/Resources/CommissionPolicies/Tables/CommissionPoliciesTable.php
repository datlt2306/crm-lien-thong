<?php

namespace App\Filament\Resources\CommissionPolicies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class CommissionPoliciesTable {
    public static function configure(Table $table): Table {
        return $table
            ->columns([
                TextColumn::make('collaborator.full_name')
                    ->label('Đối tượng CTV')
                    ->searchable()
                    ->default('Tất cả CTV')
                    ->toggleable(),
                TextColumn::make('target_program_id')
                    ->label('Ngành/Chương trình')
                    ->searchable()
                    ->default('Tất cả'),
                TextColumn::make('program_type')
                    ->label('Hệ đào tạo')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'REGULAR' => 'success',
                        'PART_TIME' => 'warning',
                        'DISTANCE' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'REGULAR' => 'Chính quy',
                        'PART_TIME' => 'VHVLV',
                        'DISTANCE' => 'Từ xa',
                        default => 'Tất cả',
                    }),
                TextColumn::make('payout_rules')
                    ->label('Gói chia tiền')
                    ->html()
                    ->getStateUsing(function ($record) {
                        $rules = $record->payout_rules;
                        $html = '';
                        
                        // 1. Hiển thị cấu hình Gói chia tiền mới (JSON)
                        if (!empty($rules) && is_array($rules)) {
                            $lines = [];
                            foreach ($rules as $rule) {
                                if (empty($rule['amount_vnd'])) continue;
                                
                                $recipient = ($rule['recipient_type'] ?? '') === 'direct_ctv' 
                                    ? '<span class="text-primary-600 font-bold">• CTV trực tiếp</span>' 
                                    : '<span class="text-info-600 font-bold">• CTV chỉ định</span>';
                                
                                $amount = number_format($rule['amount_vnd']) . 'đ';
                                
                                $trigger = ($rule['payout_trigger'] ?? '') === 'payment_verified' 
                                    ? '<span class="bg-success-100 text-success-700 px-1 rounded text-xs">Mùng 5</span>' 
                                    : '<span class="bg-warning-100 text-warning-700 px-1 rounded text-xs">Nhập học</span>';
                                
                                $lines[] = "<div>{$recipient}: <strong>{$amount}</strong> {$trigger}</div>";
                            }
                            if (!empty($lines)) {
                                $html = '<div class="text-sm space-y-1">' . implode('', $lines) . '</div>';
                            }
                        }

                        // 2. Fallback cho dữ liệu cũ (Legacy) nếu HTML vẫn trống
                        if (empty($html)) {
                            if ($record->amount_vnd > 0) {
                                $amount = number_format($record->amount_vnd) . 'đ';
                                $triggerLabel = $record->trigger === 'ON_ENROLLMENT' ? 'Nhập học' : 'Mùng 5';
                                $html = "<div class='text-sm'>📍 CTV trực tiếp: <strong>{$amount}</strong> ({$triggerLabel})</div>";
                            } elseif ($record->percent > 0) {
                                $percent = rtrim(rtrim((string)$record->percent, '0'), '.') . '%';
                                $html = "<div class='text-sm'>📍 CTV trực tiếp: <strong>{$percent}</strong></div>";
                            } else {
                                $html = '<span class="text-gray-400 italic">Chưa thiết lập</span>';
                            }
                        }

                        return $html;
                    }),
                TextColumn::make('role')
                    ->label('Vai trò')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn($state) => $state === 'PRIMARY' ? 'CTV Chính' : 'CTV Phụ'),
                TextColumn::make('priority')
                    ->label('Độ ưu tiên')
                    ->sortable(),
                TextColumn::make('active')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn($state) => $state ? 'Kích hoạt' : 'Vô hiệu'),

            ])
            ->filters([
                SelectFilter::make('program_type')
                    ->label('Chương trình')
                    ->options([
                        'REGULAR' => 'Chính quy',
                        'PART_TIME' => 'Bán thời gian',
                    ]),
                SelectFilter::make('role')
                    ->label('Vai trò')
                    ->options([
                        'PRIMARY' => 'CTV chính',
                        'SUB' => 'CTV phụ',
                    ]),
                SelectFilter::make('type')
                    ->label('Loại hoa hồng')
                    ->options([
                        'FIXED' => 'Cố định',
                        'PERCENT' => 'Phần trăm',
                        'PASS_THROUGH' => 'Chuyển tiếp',
                    ]),
                TernaryFilter::make('active')
                    ->label('Trạng thái')
                    ->placeholder('Tất cả')
                    ->trueLabel('Kích hoạt')
                    ->falseLabel('Vô hiệu'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Chỉnh sửa'),
                ])
                    ->label('Hành động')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->button()
                    ->size('sm')
                    ->tooltip('Các hành động khả dụng')
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->modalHeading('Xóa chính sách hoa hồng đã chọn')
                        ->modalDescription('Bạn có chắc chắn muốn xóa các chính sách hoa hồng đã chọn? Hành động này không thể hoàn tác.')
                        ->modalSubmitActionLabel('Xóa')
                        ->modalCancelActionLabel('Hủy'),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
