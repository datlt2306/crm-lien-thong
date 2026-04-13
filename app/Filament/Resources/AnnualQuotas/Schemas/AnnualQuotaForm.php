<?php

namespace App\Filament\Resources\AnnualQuotas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as SchemaFacade;

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

                        Select::make('major_name')
                            ->label('Ngành học')
                            ->options(fn() => SchemaFacade::hasTable('majors')
                                ? \App\Models\Major::query()
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'name')
                                : [])
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('program_name')
                            ->label('Hệ đào tạo')
                            ->options(fn() => SchemaFacade::hasTable('programs')
                                ? \App\Models\Program::query()
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'name')
                                : [])
                            ->searchable()
                            ->preload()
                            ->required(),

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


                        Textarea::make('notes')
                            ->label('Ghi chú')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
