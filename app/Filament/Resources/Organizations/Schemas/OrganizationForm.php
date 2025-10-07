<?php

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OrganizationForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Tên đơn vị')
                    ->required()
                    ->live(onBlur: true)
                    ->unique('organizations', 'name', ignoreRecord: true)
                    ->validationAttribute('Tên đơn vị'),
                Section::make('Chủ đơn vị')
                    ->columns(1)
                    ->visible(fn($context) => Auth::user()?->role === 'super_admin')
                    ->schema([
                        \Filament\Forms\Components\Select::make('organization_owner_id')
                            ->label('Chọn tài khoản có sẵn')
                            ->relationship('organization_owner', 'name', fn($query) => $query->whereIn('role', ['super_admin', 'organization_owner']))
                            ->searchable()
                            ->preload()
                            ->placeholder('Chọn tài khoản có sẵn...')
                            ->required(fn($context) => $context === 'create'),
                        // Ẩn các trường tạo tài khoản mới khi tạo mới đơn vị; chỉ hiển thị ở chế độ sửa
                        \Filament\Forms\Components\TextInput::make('organization_owner_email')
                            ->label('Email chủ đơn vị (nếu tạo mới)')
                            ->email()
                            ->helperText('Chỉ điền nếu muốn tạo tài khoản mới (chỉ trong chỉnh sửa)')
                            ->visible(fn($get, $context) => $context === 'edit' && !$get('organization_owner_id')),
                        \Filament\Forms\Components\TextInput::make('organization_owner_password')
                            ->label('Mật khẩu (nếu tạo mới)')
                            ->password()
                            ->default('123456')
                            ->helperText('Mặc định: 123456')
                            ->minLength(6)
                            ->confirmed()
                            ->visible(fn($get, $context) => $context === 'edit' && !$get('organization_owner_id')),
                        \Filament\Forms\Components\TextInput::make('organization_owner_password_confirmation')
                            ->label('Xác nhận mật khẩu')
                            ->password()
                            ->default('123456')
                            ->same('organization_owner_password')
                            ->visible(fn($get, $context) => $context === 'edit' && !$get('organization_owner_id')),
                    ]),
                Section::make('Đào tạo')
                    ->description('Chọn ngành, chỉ tiêu và hệ đào tạo')
                    ->columns(1)
                    ->visible(
                        fn($context) =>
                        // Chỉ organization_owner được phép cấu hình phần đào tạo
                        $context === 'edit' && Auth::user()?->role === 'organization_owner'
                    )
                    ->schema([
                        \Filament\Forms\Components\Repeater::make('training_rows')
                            ->label('Ngành / Chỉ tiêu / Hệ đào tạo / Đợt tuyển')
                            ->addActionLabel('＋ Thêm')
                            ->afterStateHydrated(function ($state, callable $set, ?\App\Models\Organization $record = null) {
                                // Chỉ load dữ liệu nếu chưa có state và có record (edit mode)
                                if (!empty($state) || !$record) {
                                    return;
                                }

                                $rows = $record->majors->map(function ($m) {
                                    // Lấy programs cho major này từ bảng pivot mới
                                    $programIds = \Illuminate\Support\Facades\DB::table('major_organization_program')
                                        ->join('major_organization', 'major_organization_program.major_organization_id', '=', 'major_organization.id')
                                        ->where('major_organization.organization_id', $m->pivot->organization_id)
                                        ->where('major_organization.major_id', $m->id)
                                        ->pluck('major_organization_program.program_id')
                                        ->toArray();

                                    return [
                                        'major_id' => $m->id,
                                        'quota' => $m->pivot->quota,
                                        'program_ids' => $programIds,
                                        'intake_months' => $m->pivot->intake_months ? json_decode($m->pivot->intake_months, true) : [],
                                    ];
                                })->toArray();

                                if (!empty($rows)) {
                                    $set('training_rows', $rows);
                                }
                            })
                            ->schema([
                                \Filament\Forms\Components\Select::make('major_id')
                                    ->label('Ngành')
                                    ->options(function ($get) {
                                        /** @var callable $get */
                                        $rows = (array) $get('../../training_rows');
                                        $current = $get('major_id');
                                        $used = collect($rows)->pluck('major_id')->filter()->unique()->values()->all();
                                        if (!empty($current)) {
                                            $used = array_values(array_diff($used, [$current]));
                                        }
                                        $query = \App\Models\Major::where('is_active', true)->orderBy('name');
                                        if (!empty($used)) {
                                            $query->whereNotIn('id', $used);
                                        }
                                        return $query->pluck('name', 'id')->toArray();
                                    })
                                    ->required()
                                    ->searchable(),
                                \Filament\Forms\Components\Select::make('program_ids')
                                    ->label('Hệ đào tạo')
                                    ->multiple()
                                    ->options(fn() => \App\Models\Program::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray())
                                    ->preload()
                                    ->searchable()
                                    ->required(),
                                \Filament\Forms\Components\TextInput::make('quota')
                                    ->label('Chỉ tiêu')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),
                                \Filament\Forms\Components\Select::make('intake_months')
                                    ->label('Đợt tuyển (tháng)')
                                    ->multiple()
                                    ->options([
                                        1 => '1',
                                        2 => '2',
                                        3 => '3',
                                        4 => '4',
                                        5 => '5',
                                        6 => '6',
                                        7 => '7',
                                        8 => '8',
                                        9 => '9',
                                        10 => '10',
                                        11 => '11',
                                        12 => '12'
                                    ])
                                    ->helperText('VD: trường tuyển chính tháng 6,9,12 thì chọn 6,9,12')
                                    ->required(),

                            ])
                            ->columns(4)
                            ->default([])
                        // Đồng bộ sẽ thực hiện trong afterSave/afterCreate của Page
                        ,
                    ]),


                \Filament\Forms\Components\Toggle::make('status')
                    ->label('Kích hoạt')
                    ->onColor('success')
                    ->offColor('danger')
                    ->inline(false)
                    ->helperText('Bật để kích hoạt, tắt để vô hiệu hoá đơn vị')
                    ->formatStateUsing(function ($state) {
                        // Filament đã tự động chuyển đổi string thành boolean
                        // Nếu state là string, so sánh với 'active'
                        // Nếu state là boolean, trả về trực tiếp
                        if (is_string($state)) {
                            return $state === 'active';
                        }
                        return (bool) $state;
                    })
                    ->dehydrateStateUsing(function ($state) {
                        return $state ? 'active' : 'inactive';
                    }),
            ])
            ->columns(1);
    }
}
