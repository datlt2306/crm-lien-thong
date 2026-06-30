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
            ->heading('Chính sách hoa hồng')
            ->description('Thiết lập chính sách tính hoa hồng, định mức chi trả cho cộng tác viên.')
            ->headerActions([
                \Filament\Actions\Action::make('create')
                    ->label('Thêm chính sách mới')
                    ->url(fn() => \App\Filament\Resources\CommissionPolicies\CommissionPolicyResource::getUrl('create')),
            ])
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
                    ->color(fn($state) => 'info') // Since it can be multiple, just use info color
                    ->formatStateUsing(fn($state) => is_array($state) 
                        ? collect($state)->map(fn($s) => match (strtolower((string)$s)) {
                            'regular' => 'Chính quy',
                            'part_time' => 'Vừa học vừa làm',
                            'distance' => 'Đào tạo từ xa',
                            default => $s,
                        })->join(', ')
                        : match (strtolower((string)$state)) {
                            'regular' => 'Chính quy',
                            'part_time' => 'Vừa học vừa làm',
                            'distance' => 'Đào tạo từ xa',
                            default => 'Tất cả',
                        }),
                TextColumn::make('payout_rules')
                    ->label('Gói chia tiền')
                    ->html()
                    ->getStateUsing(function ($record) {
                        $payoutRules = $record->payout_rules;
                        $html = '';
                        
                        if (!empty($payoutRules) && is_array($payoutRules)) {
                            // Check if it's the new nested structure or old flat structure
                            $isNested = !isset($payoutRules[0]); 

                            if ($isNested) {
                                foreach ($payoutRules as $type => $rules) {
                                    $typeLabel = match (strtolower((string)$type)) {
                                        'regular' => '🎓 Chính quy',
                                        'part_time' => '🕒 Vừa học vừa làm',
                                        'distance' => '🌐 Đào tạo từ xa',
                                        'default' => '⚙️ Mặc định',
                                        default => $type
                                    };
                                    
                                    $html .= "<div class='mb-2 last:mb-0'><div class='font-bold text-xs uppercase text-gray-500'>{$typeLabel}</div>";
                                    $html .= self::renderRulesHtml($rules);
                                    $html .= "</div>";
                                }
                            } else {
                                $html = self::renderRulesHtml($payoutRules);
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
                    ->label('Hệ đào tạo')
                    ->options([
                        'regular' => 'Chính quy',
                        'part_time' => 'Vừa học vừa làm',
                        'distance' => 'Đào tạo từ xa',
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
                    \Filament\Actions\Action::make('toggle_active')
                        ->label(fn($record) => $record->active ? 'Vô hiệu hóa' : 'Kích hoạt')
                        ->icon(fn($record) => $record->active ? 'heroicon-m-no-symbol' : 'heroicon-m-check-circle')
                        ->color(fn($record) => $record->active ? 'danger' : 'success')
                        ->action(fn($record) => $record->update(['active' => !$record->active]))
                        ->requiresConfirmation(),
                    \Filament\Actions\DeleteAction::make()
                        ->label('Xóa')
                        ->modalHeading('Xóa chính sách hoa hồng')
                        ->modalDescription('Bạn có chắc chắn muốn xóa chính sách này? Hồ sơ sẽ được chuyển vào Thùng rác.'),
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
                    ->tooltip('Các hành động khả dụng')
            ])
            ->toolbarActions([
                \Filament\Actions\Action::make('show_active')
                    ->label(fn() => 'Tất cả (' . \App\Models\CommissionPolicy::whereNull('deleted_at')->count() . ')')
                    ->icon('heroicon-o-document-currency-dollar')
                    ->color(fn() => !session('commission_policies_show_trashed', false) ? 'primary' : 'gray')
                    ->button()
                    ->size('sm')
                    ->action(function () {
                        session(['commission_policies_show_trashed' => false]);
                    }),
                \Filament\Actions\Action::make('show_trashed')
                    ->label(fn() => 'Thùng rác (' . \App\Models\CommissionPolicy::onlyTrashed()->count() . ')')
                    ->icon('heroicon-o-trash')
                    ->color(fn() => session('commission_policies_show_trashed', false) ? 'danger' : 'gray')
                    ->button()
                    ->size('sm')
                    ->visible(fn() => \App\Models\CommissionPolicy::onlyTrashed()->count() > 0)
                    ->action(function () {
                        session(['commission_policies_show_trashed' => true]);
                    }),
                BulkActionGroup::make(
                    session('commission_policies_show_trashed', false)
                        ? [
                            \Filament\Actions\RestoreBulkAction::make()
                                ->label('Khôi phục đã chọn'),
                            \Filament\Actions\ForceDeleteBulkAction::make()
                                ->label('Xóa vĩnh viễn đã chọn')
                                ->modalHeading('Xóa vĩnh viễn chính sách hoa hồng đã chọn')
                                ->modalDescription('Hành động này sẽ xóa hoàn toàn các chính sách hoa hồng đã chọn khỏi hệ thống. Bạn chắc chắn chứ?'),
                        ]
                        : [
                            \Filament\Actions\DeleteBulkAction::make()
                                ->label('Bỏ vào thùng rác')
                                ->modalHeading('Bỏ chính sách hoa hồng đã chọn vào thùng rác')
                                ->modalDescription('Bạn có chắc chắn muốn bỏ các chính sách hoa hồng đã chọn vào Thùng rác? Bạn có thể khôi phục lại sau.')
                                ->visible(fn() => \Illuminate\Support\Facades\Auth::user()?->can('commission_policy_delete')),
                        ]
                )
                ->label('Hành động hàng loạt'),
            ])
            ->defaultSort('id', 'desc');
    }

    private static function renderRulesHtml(array $rules): string {
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
        
        if (empty($lines)) return '';
        
        return '<div class="text-sm space-y-1 mt-1">' . implode('', $lines) . '</div>';
    }
}
