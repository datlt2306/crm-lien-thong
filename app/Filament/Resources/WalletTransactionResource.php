<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletTransactionResource\Pages;
use App\Models\WalletTransaction;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Illuminate\Database\Eloquent\Builder;

class WalletTransactionResource extends Resource {
    protected static ?string $model = WalletTransaction::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Giao dịch ví tiền';

    protected static ?string $modelLabel = 'Giao dịch ví tiền';

    protected static ?string $pluralModelLabel = 'Giao dịch ví tiền';

    protected static string|\UnitEnum|null $navigationGroup = 'Quản lý hoa hồng';

    public static function shouldRegisterNavigation(): bool {
        $user = auth()->user();

        if ($user->role === 'super_admin') {
            // Super admin luôn thấy menu này
            return true;
        }

        if ($user->role === 'chủ đơn vị') {
            // Chủ đơn vị thấy menu giao dịch để quản lý giao dịch của tổ chức
            return true;
        }

        if ($user->role === 'ctv') {
            // CTV thấy menu giao dịch để xem lịch sử của mình
            return true;
        }

        return false;
    }

    public static function form(Schema $schema): Schema {
        return $schema
            ->schema([
                Forms\Components\Select::make('wallet_id')
                    ->label('Ví')
                    ->options(function () {
                        $user = auth()->user();
                        if ($user->role === 'super_admin') {
                            return \App\Models\Wallet::with('collaborator')
                                ->get()
                                ->pluck('collaborator.full_name', 'id');
                        } else {
                            $org = \App\Models\Organization::where('owner_id', $user->id)->first();
                            if ($org) {
                                return \App\Models\Wallet::whereHas('collaborator', function ($q) use ($org) {
                                    $q->where('organization_id', $org->id);
                                })->with('collaborator')
                                    ->get()
                                    ->pluck('collaborator.full_name', 'id');
                            }
                        }
                        return [];
                    })
                    ->required()
                    ->searchable(),

                Forms\Components\Select::make('type')
                    ->label('Loại giao dịch')
                    ->options([
                        'deposit' => 'Nạp tiền',
                        'withdrawal' => 'Rút tiền',
                        'transfer_out' => 'Chuyển tiền đi',
                        'transfer_in' => 'Nhận tiền',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('amount')
                    ->label('Số tiền')
                    ->numeric()
                    ->required()
                    ->disabled(),

                Forms\Components\TextInput::make('balance_before')
                    ->label('Số dư trước')
                    ->numeric()
                    ->required()
                    ->disabled(),

                Forms\Components\TextInput::make('balance_after')
                    ->label('Số dư sau')
                    ->numeric()
                    ->required()
                    ->disabled(),

                Forms\Components\TextInput::make('description')
                    ->label('Mô tả')
                    ->required()
                    ->disabled(),

                Forms\Components\Select::make('related_wallet_id')
                    ->label('Ví liên quan')
                    ->options(function () {
                        $user = auth()->user();
                        if ($user->role === 'super_admin') {
                            return \App\Models\Wallet::with('collaborator')
                                ->get()
                                ->pluck('collaborator.full_name', 'id');
                        } else {
                            $org = \App\Models\Organization::where('owner_id', $user->id)->first();
                            if ($org) {
                                return \App\Models\Wallet::whereHas('collaborator', function ($q) use ($org) {
                                    $q->where('organization_id', $org->id);
                                })->with('collaborator')
                                    ->get()
                                    ->pluck('collaborator.full_name', 'id');
                            }
                        }
                        return [];
                    })
                    ->searchable()
                    ->disabled(),

                Forms\Components\Select::make('commission_item_id')
                    ->label('Commission liên quan')
                    ->options(function () {
                        return \App\Models\CommissionItem::with('recipient')
                            ->get()
                            ->pluck('recipient.full_name', 'id');
                    })
                    ->searchable()
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('wallet.collaborator.full_name')
                    ->label('CTV')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('wallet.collaborator.organization.name')
                    ->label('Tổ chức')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Loại giao dịch')
                    ->colors([
                        'success' => 'deposit',
                        'danger' => 'withdrawal',
                        'warning' => 'transfer_out',
                        'info' => 'transfer_in',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'deposit' => 'Nạp tiền',
                        'withdrawal' => 'Rút tiền',
                        'transfer_out' => 'Chuyển tiền đi',
                        'transfer_in' => 'Nhận tiền',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Số tiền')
                    ->money('VND')
                    ->sortable()
                    ->color(
                        fn(string $state, WalletTransaction $record): string =>
                        in_array($record->type, ['deposit', 'transfer_in']) ? 'success' : 'danger'
                    ),

                Tables\Columns\TextColumn::make('balance_before')
                    ->label('Số dư trước')
                    ->money('VND')
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Số dư sau')
                    ->money('VND')
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Mô tả')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('relatedWallet.collaborator.full_name')
                    ->label('Ví liên quan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Loại giao dịch')
                    ->options([
                        'deposit' => 'Nạp tiền',
                        'withdrawal' => 'Rút tiền',
                        'transfer_out' => 'Chuyển tiền đi',
                        'transfer_in' => 'Nhận tiền',
                    ]),

                Tables\Filters\SelectFilter::make('wallet.collaborator.organization_id')
                    ->label('Tổ chức')
                    ->options(function () {
                        $user = auth()->user();
                        if ($user->role === 'super_admin') {
                            return \App\Models\Organization::pluck('name', 'id');
                        } else {
                            $org = \App\Models\Organization::where('owner_id', $user->id)->first();
                            if ($org) {
                                return [$org->id => $org->name];
                            }
                        }
                        return [];
                    }),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    //
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if ($user->role === 'super_admin') {
                    // Super admin thấy tất cả
                    return;
                }

                if ($user->role === 'ctv') {
                    // CTV chỉ thấy giao dịch của mình
                    $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();
                    if ($collaborator) {
                        $query->whereHas('wallet', function ($q) use ($collaborator) {
                            $q->where('collaborator_id', $collaborator->id);
                        });
                    } else {
                        $query->whereNull('id'); // Không trả về gì nếu không tìm thấy collaborator
                    }
                } else if ($user->role === 'chủ đơn vị') {
                    // Chủ đơn vị chỉ thấy giao dịch của tổ chức mình
                    $org = \App\Models\Organization::where('owner_id', $user->id)->first();
                    if ($org) {
                        $query->whereHas('wallet.collaborator', function ($q) use ($org) {
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

    public static function getNavigationUrl(): string {
        $user = auth()->user();

        if ($user->role === 'super_admin') {
            return static::getUrl('index');
        }

        if ($user->role === 'ctv') {
            // CTV sẽ được chuyển đến trang xem giao dịch của mình
            return static::getUrl('index');
        }

        if ($user->role === 'chủ đơn vị') {
            // Chủ đơn vị vẫn xem danh sách
            return static::getUrl('index');
        }

        return static::getUrl('index');
    }

    public static function getPages(): array {
        return [
            'index' => Pages\ListWalletTransactions::route('/'),
            'view' => Pages\ViewWalletTransaction::route('/{record}'),
        ];
    }
}
