<?php

namespace App\Filament\Resources\Collaborators\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CollaboratorInfolist {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->components([
                TextEntry::make('full_name')
                    ->label('Họ và tên'),
                TextEntry::make('phone')
                    ->label('Số điện thoại'),
                TextEntry::make('email')
                    ->label('Địa chỉ email'),
                TextEntry::make('identity_card')
                    ->label('Số CCCD'),
                TextEntry::make('tax_code')
                    ->label('Mã số thuế'),
                TextEntry::make('bank_name')
                    ->label('Ngân hàng'),
                TextEntry::make('bank_account')
                    ->label('Tài khoản ngân hàng'),
                TextEntry::make('ref_id')
                    ->label('Mã giới thiệu'),
                TextEntry::make('status')
                    ->label('Trạng thái')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'active' => 'Kích hoạt',
                        'pending' => 'Chờ duyệt',
                        'inactive' => 'Vô hiệu',
                        default => $state,
                    }),
                TextEntry::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i:s'),
                TextEntry::make('updated_at')
                    ->label('Ngày cập nhật')
                    ->dateTime('d/m/Y H:i:s'),
            ]);
    }
}
