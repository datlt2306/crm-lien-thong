<?php

namespace App\Filament\Resources\Collaborators\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use App\Models\Student;
use App\Models\Payment;
use App\Models\Commission;
use App\Models\CommissionItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\Collaborator;
use App\Models\User;

class CollaboratorsTable {
    public static function configure(Table $table): Table {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Họ và tên')
                    ->searchable()
                    ->sortable(),
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
                    })
                    ->sortable(),
                // Đã loại bỏ cột CTV cấp trên và số CTV con - hệ thống chỉ còn 1 cấp
                TextColumn::make('identity_card')
                    ->label('CCCD')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('bank_info')
                    ->label('Ngân hàng / STK')
                    ->state(fn($record) => $record)
                    ->formatStateUsing(fn($record) => $record->bank_name && $record->bank_account
                        ? e($record->bank_name) . ' - ' . e($record->bank_account)
                        : ($record->bank_name ? e($record->bank_name) : ($record->bank_account ? '•••' . substr($record->bank_account, -4) : '—')))
                    ->searchable(query: function ($query, $search) {
                        return $query->where(function ($q) use ($search) {
                            $q->where('bank_name', 'like', "%{$search}%")
                                ->orWhere('bank_account', 'like', "%{$search}%");
                        });
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'inactive' => 'danger',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'active' => 'Kích hoạt',
                        'pending' => 'Chờ duyệt',
                        'inactive' => 'Vô hiệu',
                        default => $state
                    }),
                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Ngày cập nhật')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'active' => 'Kích hoạt',
                        'pending' => 'Chờ duyệt',
                        'inactive' => 'Vô hiệu',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Xem chi tiết'),

                    // Action duyệt CTV
                    Action::make('approve')
                        ->label('Duyệt')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(
                            fn($record) =>
                            $record->status === 'pending' && Gate::allows('approve', $record)
                        )
                        ->requiresConfirmation()
                        ->modalHeading('Duyệt cộng tác viên')
                        ->modalDescription('Bạn có chắc chắn muốn duyệt cộng tác viên này? Họ sẽ trở thành CTV chính thức.')
                        ->modalSubmitActionLabel('Duyệt')
                        ->modalCancelActionLabel('Hủy')
                        ->action(function (Collaborator $record) {
                            $record->update(['status' => 'active']);

                            // Tạo user account nếu có email
                            if ($record->email) {
                                $user = \App\Models\User::where('email', $record->email)->first();
                                if (!$user) {
                                    \App\Models\User::create([
                                        'name' => $record->full_name,
                                        'email' => $record->email,
                                        'password' => \Illuminate\Support\Facades\Hash::make('123456'),
                                        'role' => 'collaborator',
                                        'collaborator_id' => $record->id,
                                    ]);
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Đã duyệt cộng tác viên thành công!')
                                ->success()
                                ->send();
                        }),

                    // Action từ chối CTV
                    Action::make('reject')
                        ->label('Từ chối')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(
                            fn($record) =>
                            $record->status === 'pending' && Gate::allows('reject', $record)
                        )
                        ->requiresConfirmation()
                        ->modalHeading('Từ chối cộng tác viên')
                        ->modalDescription('Bạn có chắc chắn muốn từ chối đăng ký này?')
                        ->modalSubmitActionLabel('Từ chối')
                        ->modalCancelActionLabel('Hủy')
                        ->action(function (Collaborator $record) {
                            $record->update(['status' => 'inactive']);

                            \Filament\Notifications\Notification::make()
                                ->title('Đã từ chối đăng ký cộng tác viên!')
                                ->warning()
                                ->send();
                        }),

                    EditAction::make()
                        ->label('Chỉnh sửa')
                        ->visible(fn($record) => Gate::allows('update', $record)),
                    DeleteAction::make()
                        ->label('Xóa')
                        ->modalHeading('Xóa cộng tác viên')
                        ->modalDescription('Bạn có chắc chắn muốn xóa cộng tác viên này? Nếu họ đã có dữ liệu học viên hoặc tài chính, hệ thống sẽ tự động chuyển sang trạng thái Ngừng hoạt động.')
                        ->modalSubmitActionLabel('Xóa/Vô hiệu hóa')
                        ->visible(fn($record) => Gate::allows('delete', $record))
                        ->action(function ($record) {
                            // Kiểm tra dữ liệu liên quan
                            $hasStudents = Student::where('collaborator_id', $record->id)->exists();
                            $hasPayments = Payment::where('primary_collaborator_id', $record->id)->exists();
                            $hasCommissions = CommissionItem::where('recipient_collaborator_id', $record->id)->exists();

                            if ($hasStudents || $hasPayments || $hasCommissions) {
                                // Nếu có dữ liệu liên quan -> Chỉ vô hiệu hóa CTV và User
                                $record->update(['is_active' => false]);
                                if ($record->email) {
                                    User::where('email', $record->email)->update(['is_active' => false]);
                                }
                                
                                Notification::make()
                                    ->title('Đã chuyển sang Ngừng hoạt động')
                                    ->body("CTV {$record->full_name} đã có dữ liệu (học viên/tài chính) nên không thể xóa vĩnh viễn. Hệ thống đã tự động khóa CTV và tài khoản User liên kết.")
                                    ->warning()
                                    ->send();
                            } else {
                                // Nếu không có dữ liệu -> Xóa cứng CTV và User
                                if ($record->email) {
                                    User::where('email', $record->email)->delete();
                                }
                                $record->delete();
                                
                                Notification::make()
                                    ->title('Đã xóa vĩnh viễn')
                                    ->body("Đã xóa CTV {$record->full_name} và tài khoản User liên kết.")
                                    ->success()
                                    ->send();
                            }
                        }),
                    Action::make('toggle_active')
                        ->label(fn($record) => $record->is_active ? 'Vô hiệu hóa' : 'Kích hoạt')
                        ->icon(fn($record) => $record->is_active ? 'heroicon-m-no-symbol' : 'heroicon-m-check-circle')
                        ->color(fn($record) => $record->is_active ? 'danger' : 'success')
                        ->action(function ($record) {
                            $record->update(['is_active' => !$record->is_active]);
                            
                            // Nếu disable CTV thì cũng disable user account tương ứng
                            if ($record->email) {
                                $user = User::where('email', $record->email)->first();
                                if ($user) {
                                    $user->update(['is_active' => $record->is_active]);
                                }
                            }
                        })
                        ->requiresConfirmation(),
                    Action::make('reassign_students')
                        ->label('Điều chuyển học viên')
                        ->icon('heroicon-o-user-group')
                        ->color('info')
                        ->visible(fn($record) => !$record->is_active && Auth::user()->can('collaborator_update'))
                        ->form([
                            \Filament\Forms\Components\Select::make('new_collaborator_id')
                                ->label('Chọn CTV tiếp nhận')
                                ->options(fn($record) => Collaborator::where('is_active', true)->where('id', '!=', $record->id)->pluck('full_name', 'id'))
                                ->required()
                                ->searchable(),
                        ])
                        ->action(function (Collaborator $record, array $data) {
                            $newCollabId = $data['new_collaborator_id'];
                            $newCollabName = Collaborator::find($newCollabId)?->full_name;
                            
                            // Đếm số lượng để thông báo
                            $studentCount = Student::where('collaborator_id', $record->id)->count();
                            
                            // Thực hiện chuyển nhượng học viên
                            Student::where('collaborator_id', $record->id)
                                ->update(['collaborator_id' => $newCollabId]);
                                
                            // Thực hiện chuyển nhượng trong bảng thanh toán (để tính hoa hồng sau này cho đúng người)
                            Payment::where('primary_collaborator_id', $record->id)
                                ->update(['primary_collaborator_id' => $newCollabId]);

                            Notification::make()
                                ->title('Điều chuyển thành công')
                                ->body("Đã chuyển giao $studentCount học viên từ {$record->full_name} sang {$newCollabName}.")
                                ->success()
                                ->send();
                        })
                        ->modalHeading('Điều chuyển học viên & Quản lý')
                        ->modalDescription('Tất cả học viên và các bản ghi thanh toán liên quan sẽ được chuyển sang cho CTV mới quản lý. Các hoa hồng đã phát sinh trước đó sẽ không bị ảnh hưởng (giữ nguyên lịch sử).')
                        ->modalSubmitActionLabel('Bắt đầu điều chuyển'),
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
                    // Bulk duyệt CTV
                    BulkAction::make('approve')
                        ->label('Duyệt đã chọn')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Duyệt cộng tác viên đã chọn')
                        ->modalDescription('Bạn có chắc chắn muốn duyệt các cộng tác viên đã chọn?')
                        ->modalSubmitActionLabel('Duyệt')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(fn() => Gate::allows('viewAny', Collaborator::class))
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                // Kiểm tra quyền cho từng record
                                if (Gate::allows('approve', $record) && $record->status === 'pending') {
                                    $record->update(['status' => 'active']);

                                    // Tạo user account nếu có email
                                    if ($record->email) {
                                        $user = \App\Models\User::where('email', $record->email)->first();
                                        if (!$user) {
                                            \App\Models\User::create([
                                                'name' => $record->full_name,
                                                'email' => $record->email,
                                                'password' => \Illuminate\Support\Facades\Hash::make('123456'),
                                                'role' => 'collaborator',
                                                'collaborator_id' => $record->id,
                                            ]);
                                        }
                                    }
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Đã duyệt các cộng tác viên thành công!')
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->modalHeading('Xóa cộng tác viên đã chọn')
                        ->modalDescription('Các cộng tác viên đã có dữ liệu học viên hoặc tài chính sẽ được tự động chuyển sang trạng thái Ngừng hoạt động thay vì xóa vĩnh viễn.')
                        ->modalSubmitActionLabel('Bắt đầu xử lý')
                        ->visible(fn() => Gate::allows('viewAny', Collaborator::class))
                        ->action(function ($records) {
                            $deletedCount = 0;
                            $deactivatedCount = 0;

                            foreach ($records as $record) {
                                // Kiểm tra dữ liệu liên quan
                                $hasStudents = Student::where('collaborator_id', $record->id)->exists();
                                $hasPayments = Payment::where('primary_collaborator_id', $record->id)->exists();
                                $hasCommissions = CommissionItem::where('recipient_collaborator_id', $record->id)->exists();

                                if ($hasStudents || $hasPayments || $hasCommissions) {
                                    // Vô hiệu hóa
                                    $record->update(['is_active' => false]);
                                    if ($record->email) {
                                        User::where('email', $record->email)->update(['is_active' => false]);
                                    }
                                    $deactivatedCount++;
                                } else {
                                    // Xóa cứng
                                    if ($record->email) {
                                        User::where('email', $record->email)->delete();
                                    }
                                    $record->delete();
                                    $deletedCount++;
                                }
                            }

                            Notification::make()
                                ->title('Xử lý hoàn tất')
                                ->body("Đã xóa vĩnh viễn $deletedCount CTV và chuyển Ngừng hoạt động $deactivatedCount CTV có dữ liệu liên quan.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
