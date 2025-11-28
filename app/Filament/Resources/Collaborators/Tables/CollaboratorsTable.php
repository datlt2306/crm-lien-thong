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
                TextColumn::make('organization.name')
                    ->label('Tổ chức')
                    ->searchable(),
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
                \Filament\Tables\Filters\SelectFilter::make('organization_id')
                    ->label('Tổ chức')
                    ->relationship('organization', 'name'),
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
                                        'role' => 'ctv',
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
                        ->label('Xóa cộng tác viên')
                        ->modalHeading('Xóa cộng tác viên')
                        ->modalDescription('Bạn có chắc chắn muốn xóa cộng tác viên này? Hành động này sẽ xóa cả tài khoản người dùng tương ứng và không thể hoàn tác.')
                        ->modalSubmitActionLabel('Xóa')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(fn($record) => Gate::allows('delete', $record))
                        ->before(function (Collaborator $record) {
                            // Xóa user tương ứng nếu có
                            if ($record->email) {
                                $user = User::where('email', $record->email)->first();
                                if ($user) {
                                    $user->delete();
                                }
                            }
                        }),
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
                                                'role' => 'ctv',
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
                        ->modalDescription('Bạn có chắc chắn muốn xóa các cộng tác viên đã chọn? Hành động này sẽ xóa cả tài khoản người dùng tương ứng và không thể hoàn tác.')
                        ->modalSubmitActionLabel('Xóa')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(fn() => Gate::allows('viewAny', Collaborator::class))
                        ->before(function ($records) {
                            // Kiểm tra quyền cho từng record
                            foreach ($records as $record) {
                                if (!Gate::allows('delete', $record)) {
                                    throw new \Exception('Bạn không có quyền xóa cộng tác viên này.');
                                }
                            }

                            // Xóa user tương ứng cho mỗi collaborator
                            foreach ($records as $record) {
                                if ($record->email) {
                                    $user = User::where('email', $record->email)->first();
                                    if ($user) {
                                        $user->delete();
                                    }
                                }
                            }
                        }),
                ]),
            ]);
    }
}
