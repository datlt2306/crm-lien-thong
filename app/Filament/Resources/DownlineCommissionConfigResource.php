<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DownlineCommissionConfigResource\Pages;
use App\Models\DownlineCommissionConfig;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;

class DownlineCommissionConfigResource extends Resource {
    protected static ?string $model = DownlineCommissionConfig::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Cấu hình hoa hồng tuyến dưới';

    protected static ?string $modelLabel = 'Cấu hình hoa hồng tuyến dưới';

    protected static ?string $pluralModelLabel = 'Cấu hình hoa hồng tuyến dưới';

    protected static string|\UnitEnum|null $navigationGroup = 'Quản lý hoa hồng';

    public static function shouldRegisterNavigation(): bool {
        $user = auth()->user();

        if ($user->role === 'super_admin') {
            // Super admin luôn thấy menu này
            return true;
        }

        if ($user->role === 'ctv') {
            // Kiểm tra xem user có phải là CTV cấp 1 không và có tuyến dưới không
            $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();

            if (!$collaborator) {
                return false;
            }

            // Chỉ hiển thị nếu là CTV cấp 1 (không có upline) và có tuyến dưới
            return $collaborator->isLevel1() && $collaborator->downlines()->exists();
        }

        return false;
    }

    public static function form(Schema $schema): Schema {
        return $schema
            ->schema([
                Forms\Components\Select::make('upline_collaborator_id')
                    ->label('CTV cấp 1')
                    ->options(function () {
                        $user = auth()->user();
                        if ($user->role === 'super_admin') {
                            return \App\Models\Collaborator::whereNull('upline_id')
                                ->pluck('full_name', 'id');
                        } else {
                            $org = \App\Models\Organization::where('owner_id', $user->id)->first();
                            if ($org) {
                                return \App\Models\Collaborator::where('organization_id', $org->id)
                                    ->whereNull('upline_id')
                                    ->pluck('full_name', 'id');
                            }
                        }
                        return [];
                    })
                    ->required()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $upline = \App\Models\Collaborator::find($state);
                            if ($upline) {
                                $downlineOptions = \App\Models\Collaborator::where('upline_id', $state)
                                    ->pluck('full_name', 'id');
                                $set('downline_collaborator_id', null);
                            }
                        }
                    }),

                Forms\Components\Select::make('downline_collaborator_id')
                    ->label('CTV cấp 2')
                    ->options(function (callable $get) {
                        $uplineId = $get('upline_collaborator_id');
                        if ($uplineId) {
                            return \App\Models\Collaborator::where('upline_id', $uplineId)
                                ->pluck('full_name', 'id');
                        }
                        return [];
                    })
                    ->required()
                    ->searchable()
                    ->disabled(fn(callable $get) => !$get('upline_collaborator_id')),

                \Filament\Schemas\Components\Section::make('Cấu hình số tiền')
                    ->schema([
                        Forms\Components\TextInput::make('cq_amount')
                            ->label('Số tiền hệ Chính quy (VNĐ)')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->minValue(0)
                            ->step(1000),

                        Forms\Components\TextInput::make('vhvlv_amount')
                            ->label('Số tiền hệ VHVLV (VNĐ)')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->minValue(0)
                            ->step(1000),
                    ]),

                \Filament\Schemas\Components\Section::make('Hình thức thanh toán')
                    ->schema([
                        Forms\Components\Radio::make('payment_type')
                            ->label('Hình thức chi')
                            ->options([
                                'immediate' => 'Trả ngay',
                                'on_enrollment' => 'Trả khi nhập học',
                            ])
                            ->default('immediate')
                            ->required(),
                    ]),

                Forms\Components\Toggle::make('is_active')
                    ->label('Kích hoạt')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uplineCollaborator.full_name')
                    ->label('CTV cấp 1')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('downlineCollaborator.full_name')
                    ->label('CTV cấp 2')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('cq_amount')
                    ->label('Hệ CQ')
                    ->money('VND')
                    ->sortable(),

                Tables\Columns\TextColumn::make('vhvlv_amount')
                    ->label('Hệ VHVLV')
                    ->money('VND')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('payment_type')
                    ->label('Hình thức')
                    ->colors([
                        'success' => 'immediate',
                        'warning' => 'on_enrollment',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'immediate' => 'Trả ngay',
                        'on_enrollment' => 'Trả khi nhập học',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Trạng thái')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Cập nhật lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('upline_collaborator_id')
                    ->label('CTV cấp 1')
                    ->options(function () {
                        $user = auth()->user();
                        if ($user->role === 'super_admin') {
                            return \App\Models\Collaborator::whereNull('upline_id')
                                ->pluck('full_name', 'id');
                        } else if ($user->role === 'chủ đơn vị') {
                            $org = \App\Models\Organization::where('owner_id', $user->id)->first();
                            if ($org) {
                                return \App\Models\Collaborator::where('organization_id', $org->id)
                                    ->whereNull('upline_id')
                                    ->pluck('full_name', 'id');
                            }
                        }
                        return [];
                    }),

                Tables\Filters\SelectFilter::make('payment_type')
                    ->label('Hình thức thanh toán')
                    ->options([
                        'immediate' => 'Trả ngay',
                        'on_enrollment' => 'Trả khi nhập học',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Trạng thái'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if ($user->role === 'super_admin') {
                    return;
                }
                if ($user->role === 'chủ đơn vị') {
                    $org = \App\Models\Organization::where('owner_id', $user->id)->first();
                    if ($org) {
                        $query->whereHas('uplineCollaborator', function ($q) use ($org) {
                            $q->where('organization_id', $org->id);
                        });
                    }
                }
            });
    }

    public static function getRelations(): array {
        return [
            //
        ];
    }

    public static function getPages(): array {
        return [
            'index' => Pages\ListDownlineCommissionConfigs::route('/'),
            'create' => Pages\CreateDownlineCommissionConfig::route('/create'),
            'edit' => Pages\EditDownlineCommissionConfig::route('/{record}/edit'),
        ];
    }
}
