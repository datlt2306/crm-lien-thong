<?php

namespace App\Filament\Resources\AnnualQuotas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class AnnualQuotaForm {
    private static function normalizeProgramValue(?string $value): ?string {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            return null;
        }

        return match (mb_strtolower($normalized)) {
            'regular', 'chính quy', 'he chinh quy', 'hệ chính quy' => 'regular',
            'part_time', 'part-time', 'vừa học vừa làm', 'he vua hoc vua lam', 'hệ vừa học vừa làm', 'bán thời gian' => 'part_time',
            'distance', 'đào tạo từ xa', 'he dao tao tu xa', 'hệ đào tạo từ xa' => 'distance',
            default => strtoupper($normalized),
        };
    }

    private static function getProgramLabel(?string $value): string {
        return match (self::normalizeProgramValue($value)) {
            'regular' => 'Chính quy',
            'part_time' => 'Vừa học vừa làm',
            'distance' => 'Đào tạo từ xa',
            default => (string) $value,
        };
    }

    public static function configure(Schema $schema): Schema {
        $year = (int) now()->format('Y');
        return $schema
            ->schema([
                Section::make('Chỉ tiêu năm (ngành + hệ)')
                    ->description('Một năm có target (vd 100 CNTT chính quy); chia linh hoạt cho các đợt. Đợt 1 đủ → hết; chưa đủ → chuyển đợt sau.')
                    ->schema([

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
                            ->options(function () {
                                $base = [
                                    'regular' => 'Chính quy',
                                    'part_time' => 'Vừa học vừa làm',
                                    'distance' => 'Đào tạo từ xa',
                                ];

                                if (!SchemaFacade::hasTable('programs')) {
                                    return $base;
                                }

                                $fromPrograms = \App\Models\Program::query()
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($program) {
                                        $key = self::normalizeProgramValue($program->code ?: $program->name);
                                        $label = self::getProgramLabel($program->name ?: $program->code);

                                        return $key ? [$key => $label] : [];
                                    })
                                    ->toArray();

                                return array_replace($base, $fromPrograms);
                            })
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
