<?php

namespace App\Filament\Resources\Collaborators\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
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
                TextColumn::make('upline.full_name')
                    ->label('CTV cấp trên')
                    ->searchable()
                    ->visible(fn() => Auth::user()?->role === 'super_admin'),
                TextColumn::make('downlines_count')
                    ->label('Số CTV con')
                    ->counts('downlines')
                    ->badge()
                    ->color('info')
                    ->visible(function ($record) {
                        return $record && $record->downlines()->count() > 0;
                    }),
                // TextColumn::make('organization.name')
                //     ->label('Tổ chức')
                //     ->searchable(),
                TextColumn::make('ref_id')
                    ->label('Mã giới thiệu')
                    ->badge()
                    ->color('warning')
                    ->copyable(fn($record) => 'https://lienthongdaihoc.com/ref/' . $record->ref_id)
                    ->copyMessage('Đã copy link giới thiệu!')
                    ->copyMessageDuration(2000),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn($state) => $state === 'active' ? 'success' : 'danger')
                    ->formatStateUsing(fn($state) => $state === 'active' ? 'Kích hoạt' : 'Vô hiệu'),
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
                        'inactive' => 'Vô hiệu',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Xem chi tiết'),
                EditAction::make()
                    ->label('Chỉnh sửa'),
                DeleteAction::make()
                    ->label('Xóa cộng tác viên')
                    ->modalHeading('Xóa cộng tác viên')
                    ->modalDescription('Bạn có chắc chắn muốn xóa cộng tác viên này? Hành động này sẽ xóa cả tài khoản người dùng tương ứng và không thể hoàn tác.')
                    ->modalSubmitActionLabel('Xóa')
                    ->modalCancelActionLabel('Hủy')
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
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->modalHeading('Xóa cộng tác viên đã chọn')
                        ->modalDescription('Bạn có chắc chắn muốn xóa các cộng tác viên đã chọn? Hành động này sẽ xóa cả tài khoản người dùng tương ứng và không thể hoàn tác.')
                        ->modalSubmitActionLabel('Xóa')
                        ->modalCancelActionLabel('Hủy')
                        ->before(function ($records) {
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
