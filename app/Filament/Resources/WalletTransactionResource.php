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

class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Giao dịch ví tiền';

    protected static ?string $modelLabel = 'Giao dịch ví tiền';

    protected static ?string $pluralModelLabel = 'Giao dịch ví tiền';

    protected static string|\UnitEnum|null $navigationGroup = 'Quản lý hoa hồng';
    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        // Chỉ Admin và Kế toán mới xem được lịch sử giao dịch ví tiền
        return in_array($user->role, ['super_admin', 'accountant']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('wallet_id')
                    ->label('Ví')
                    ->options(function () {
                        return \App\Models\Wallet::with('collaborator')
                            ->get()
                            ->pluck('collaborator.full_name', 'id');
                    })
                    ->required()
                    ->searchable()
                    ->disabled(),

                Forms\Components\Select::make('type')
                    ->label('Loại giao dịch')
                    ->options([
                        'deposit' => 'Nạp tiền',
                        'withdrawal' => 'Rút tiền',
                        'transfer_out' => 'Chuyển tiền đi',
                        'transfer_in' => 'Nhận tiền',
                    ])
                    ->required()
                    ->disabled(),

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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('wallet.collaborator.full_name')
                    ->label('CTV')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Loại giao dịch')
                    ->badge()
                    ->colors([
                        'success' => 'deposit',
                        'danger' => 'withdrawal',
                        'warning' => 'transfer_out',
                        'info' => 'transfer_in',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
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
                    ->color(fn (string $state, WalletTransaction $record): string => 
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

                Tables\Filters\SelectFilter::make('wallet_id')
                    ->label('Ví')
                    ->options(fn() => \App\Models\Wallet::with('collaborator')->get()->pluck('collaborator.full_name', 'id')),
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
                if (in_array($user->role, ['super_admin', 'accountant'])) {
                    return;
                }
                
                $query->whereNull('id');
            });
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWalletTransactions::route('/'),
            'view' => Pages\ViewWalletTransaction::route('/{record}'),
        ];
    }
}
