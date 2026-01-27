<?php

namespace App\Filament\Resources\AnnualQuotas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AnnualQuotaForm {
    public static function configure(Schema $schema): Schema {
        $year = (int) now()->format('Y');
        return $schema
            ->schema([
                Section::make('Chỉ tiêu năm (ngành + hệ)')
                    ->description('Một năm có target (vd 100 CNTT chính quy); chia linh hoạt cho các đợt. Đợt 1 đủ → hết; chưa đủ → chuyển đợt sau.')
                    ->schema([
                        Select::make('organization_id')
                            ->label('Tổ chức')
                            ->relationship('organization', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('major_id')
                            ->label('Ngành học')
                            ->relationship('major', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn($record) => ($record->code ? $record->code . ' - ' : '') . $record->name),

                        Select::make('program_id')
                            ->label('Hệ đào tạo')
                            ->relationship('program', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        TextInput::make('year')
                            ->label('Năm tuyển sinh')
                            ->required()
                            ->numeric()
                            ->minValue($year - 2)
                            ->maxValue($year + 2)
                            ->default($year)
                            ->suffix(''),

                        TextInput::make('target_quota')
                            ->label('Chỉ tiêu cả năm')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->suffix('học viên'),

                        TextInput::make('current_quota')
                            ->label('Đã tuyển (cộng dồn)')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Tự cập nhật khi payment được verify'),

                        Select::make('status')
                            ->label('Trạng thái')
                            ->options(\App\Models\AnnualQuota::getStatusOptions())
                            ->default(\App\Models\AnnualQuota::STATUS_ACTIVE),

                        Select::make('intakes')
                            ->label('📅 Áp dụng cho đợt tuyển')
                            ->relationship('intakes', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Chọn các đợt tuyển sinh sẽ áp dụng chỉ tiêu này. Để trống = áp dụng tất cả đợt trong năm.')
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('Ghi chú')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
