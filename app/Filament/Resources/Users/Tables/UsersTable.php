<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class UsersTable {
    public static function configure(Table $table): Table {
        return $table
            ->heading('Danh sách người dùng')
            ->description('Quản lý thông tin tài khoản người dùng và phân quyền truy cập.')
            ->headerActions([
                \Filament\Actions\Action::make('create')
                    ->label('Thêm người dùng mới')
                    ->url(fn() => \App\Filament\Resources\Users\UserResource::getUrl('create')),
            ])
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
                    ->formatStateUsing(fn($state) => match ($state) {
                        'super_admin' => 'Super Admin',
                        'collaborator' => 'Cộng tác viên',
                        default => $state
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'super_admin' => 'danger',
                        'collaborator' => 'info',
                        default => 'gray'
                    })
                    ->searchable(),
                TextColumn::make('is_active')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn($state) => $state ? 'Hoạt động' : 'Bị khóa'),
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
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Xem chi tiết'),
                    EditAction::make()
                        ->label('Chỉnh sửa')
                        ->visible(fn($record) => Gate::allows('update', $record)),
                    Action::make('toggle_active')
                        ->label(fn($record) => $record->is_active ? 'Vô hiệu hóa' : 'Kích hoạt')
                        ->icon(fn($record) => $record->is_active ? 'heroicon-m-no-symbol' : 'heroicon-m-check-circle')
                        ->color(fn($record) => $record->is_active ? 'danger' : 'success')
                        ->action(function ($record) {
                            $newStatus = !$record->is_active;
                            $record->update(['is_active' => $newStatus]);
                            
                            // Đồng bộ trạng thái với CTV nếu có
                            $collaborator = $record->collaborator;
                            if ($collaborator) {
                                $collaborator->update(['is_active' => $newStatus]);
                            }
                        })
                        ->requiresConfirmation(),
                    DeleteAction::make()
                        ->label('Xóa')
                        ->modalHeading('Xóa người dùng')
                        ->modalDescription('Bạn có chắc chắn muốn vô hiệu hóa tài khoản này? Tài khoản sẽ được lưu lại và có thể khôi phục sau.')
                        ->visible(fn($record) => Gate::allows('delete', $record)),
                ])
                    ->label('Hành động')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->button()
                    ->size('sm')
                    ->tooltip('Các hành động khả dụng')
            ])
            ->toolbarActions([
                Action::make('show_active')
                    ->label(fn() => 'Tất cả (' . \App\Models\User::whereNull('deleted_at')->count() . ')')
                    ->icon('heroicon-o-users')
                    ->color(fn() => !session('users_show_trashed', false) ? 'primary' : 'gray')
                    ->button()
                    ->size('sm')
                    ->action(function () {
                        session(['users_show_trashed' => false]);
                    }),
                Action::make('show_trashed')
                    ->label(fn() => 'Vô hiệu hóa (' . \App\Models\User::onlyTrashed()->count() . ')')
                    ->icon('heroicon-o-no-symbol')
                    ->color(fn() => session('users_show_trashed', false) ? 'danger' : 'gray')
                    ->button()
                    ->size('sm')
                    
                    ->action(function () {
                        session(['users_show_trashed' => true]);
                    }),
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\RestoreBulkAction::make()
                        ->label('Khôi phục')
                        ->visible(fn () => session('users_show_trashed', false)),
                    \Filament\Actions\ForceDeleteBulkAction::make()
                        ->label('Xóa vĩnh viễn hàng loạt')
                        ->modalHeading('Xóa vĩnh viễn đã chọn')
                        ->modalDescription('Hành động này sẽ xóa hoàn toàn dữ liệu đã chọn khỏi hệ thống. Bạn chắc chắn chứ?')
                        ->visible(fn () => session('users_show_trashed', false)),
                    \Filament\Actions\DeleteBulkAction::make()
                        ->label('Vô hiệu hóa hàng loạt')
                        ->modalHeading('Vô hiệu hóa tài khoản đã chọn')
                        ->modalDescription('Bạn có chắc chắn muốn vô hiệu hóa các tài khoản đã chọn? Bạn có thể khôi phục lại sau.')
                        ->visible(fn () => !session('users_show_trashed', false)),
                ])->label('Hành động hàng loạt'),
            ])
            ->defaultSort('id', 'desc');
    }
}
