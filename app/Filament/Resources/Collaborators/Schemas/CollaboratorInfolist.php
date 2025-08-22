<?php

namespace App\Filament\Resources\Collaborators\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CollaboratorInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('full_name')
                    ->label('Họ và tên'),
                TextEntry::make('phone')
                    ->label('Số điện thoại'),
                TextEntry::make('email')
                    ->label('Địa chỉ email'),
                TextEntry::make('organization_id')
                    ->numeric(),
                TextEntry::make('ref_id'),
                TextEntry::make('upline_id')
                    ->numeric(),
                TextEntry::make('status'),
                TextEntry::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i:s'),
                TextEntry::make('updated_at')
                    ->label('Ngày cập nhật')
                    ->dateTime('d/m/Y H:i:s'),
            ]);
    }
}
