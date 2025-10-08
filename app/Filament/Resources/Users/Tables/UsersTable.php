<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class UsersTable {
    public static function configure(Table $table): Table {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('Ảnh')
                    ->circular()
                    ->size(40),
                TextColumn::make('name')
                    ->label('Họ và tên')
                    ->searchable(),
                TextColumn::make('contact')
                    ->label('Liên hệ')
                    ->state(fn($record) => $record)
                    ->formatStateUsing(function ($record) {
                        $phone = $record->phone ?? '';
                        $email = $record->email ?? '';
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
                            $q->orWhere('email', 'like', "%$search%")
                                ->orWhere('phone', 'like', "%$search%");
                        });
                    }),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('role')
                    ->label('Vai trò')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'super_admin' => 'Super Admin',
                            'organization_owner' => 'Chủ đơn vị',
                            'ctv' => 'Cộng tác viên',
                            default => $state
                        };
                    })
                    ->badge()
                    ->color(function ($state) {
                        return match ($state) {
                            'super_admin' => 'danger',
                            'organization_owner' => 'warning',
                            'ctv' => 'info',
                            default => 'gray'
                        };
                    })
                    ->searchable(),
                TextColumn::make('organization')
                    ->label('Tổ chức')
                    ->state(function ($record) {
                        // Lấy tổ chức từ quan hệ (owner -> ownedOrganization, ctv -> collaborator.organization)
                        $org = $record->getOrganization();
                        return $org?->name ?? '—';
                    })
                    ->sortable()
                    ->searchable(query: function ($query, $search) {
                        // Tìm theo tên tổ chức bằng cách join linh hoạt
                        return $query->where(function ($q) use ($search) {
                            // Tìm theo email CTV -> join sang collaborators -> organizations
                            $q->orWhereIn('email', \App\Models\Collaborator::whereHas('organization', function ($oq) use ($search) {
                                $oq->where('name', 'like', "%$search%");
                            })->pluck('email'))
                                // Tìm theo owner -> organizations.organization_owner_id
                                ->orWhereIn('id', \App\Models\Organization::where('name', 'like', "%$search%")->pluck('organization_owner_id'));
                        });
                    })
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->visible(fn() => \Illuminate\Support\Facades\Auth::user()?->role === 'super_admin'),
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
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Xem chi tiết'),
                EditAction::make()
                    ->label('Chỉnh sửa')
                    ->visible(fn($record) => Gate::allows('update', $record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->modalHeading('Xóa người dùng đã chọn')
                        ->modalDescription('Bạn có chắc chắn muốn xóa các người dùng đã chọn? Hành động này không thể hoàn tác.')
                        ->modalSubmitActionLabel('Xóa')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(fn() => Gate::allows('viewAny', \App\Models\User::class))
                        ->before(function ($records) {
                            // Kiểm tra quyền cho từng record
                            foreach ($records as $record) {
                                if (!Gate::allows('delete', $record)) {
                                    throw new \Exception('Bạn không có quyền xóa người dùng này.');
                                }
                            }
                        }),
                ]),
            ]);
    }
}
