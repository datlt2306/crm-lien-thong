<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletResource\Pages;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;

class WalletResource extends Resource {
    protected static ?string $model = Wallet::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationLabel = 'Ví tiền';

    protected static ?string $modelLabel = 'Ví tiền';

    protected static ?string $pluralModelLabel = 'Ví tiền';

    protected static string|\UnitEnum|null $navigationGroup = 'Quản lý hoa hồng';

    public static function shouldRegisterNavigation(): bool {
        $user = auth()->user();

        if ($user->role === 'super_admin') {
            // Super admin luôn thấy menu này
            return true;
        }

        if ($user->role === 'chủ đơn vị') {
            // Chủ đơn vị thấy menu ví tiền để quản lý ví của tổ chức
            return true;
        }

        if ($user->role === 'ctv') {
            // CTV thấy menu ví tiền để xem số dư của mình
            return true;
        }

        return false;
    }

    public static function form(Schema $schema): Schema {
        return $schema
            ->schema([
                Forms\Components\Select::make('collaborator_id')
                    ->label('CTV')
                    ->options(function () {
                        $user = auth()->user();
                        if ($user->role === 'super_admin') {
                            return \App\Models\Collaborator::pluck('full_name', 'id');
                        } else if ($user->role === 'chủ đơn vị') {
                            $org = \App\Models\Organization::where('owner_id', $user->id)->first();
                            if ($org) {
                                return \App\Models\Collaborator::where('organization_id', $org->id)
                                    ->pluck('full_name', 'id');
                            }
                        }
                        return [];
                    })
                    ->required()
                    ->searchable(),

                Forms\Components\TextInput::make('balance')
                    ->label('Số dư')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->disabled(),

                Forms\Components\TextInput::make('total_received')
                    ->label('Tổng nhận')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->disabled(),

                Forms\Components\TextInput::make('total_paid')
                    ->label('Tổng chi')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('collaborator.full_name')
                    ->label('CTV')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('collaborator.organization.name')
                    ->label('Tổ chức')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance')
                    ->label('Số dư')
                    ->money('VND')
                    ->sortable()
                    ->color(fn(float $state): string => $state > 0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('total_received')
                    ->label('Tổng nhận')
                    ->money('VND')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_paid')
                    ->label('Tổng chi')
                    ->money('VND')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Cập nhật lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('collaborator.organization_id')
                    ->label('Tổ chức')
                    ->options(function () {
                        $user = auth()->user();
                        if ($user->role === 'super_admin') {
                            return \App\Models\Organization::pluck('name', 'id');
                        } else if ($user->role === 'chủ đơn vị') {
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
                \Filament\Actions\Action::make('transactions')
                    ->label('Giao dịch')
                    ->icon('heroicon-o-arrow-path')
                    ->url(fn(Wallet $record): string => route('filament.admin.resources.wallet-transactions.index')),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if ($user->role === 'super_admin') {
                    // Super admin thấy tất cả
                    return;
                }

                if ($user->role === 'ctv') {
                    // CTV chỉ thấy ví của mình
                    $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();
                    if ($collaborator) {
                        $query->where('collaborator_id', $collaborator->id);
                    } else {
                        $query->whereNull('id'); // Không trả về gì nếu không tìm thấy collaborator
                    }
                } else if ($user->role === 'chủ đơn vị') {
                    // Chủ đơn vị chỉ thấy ví của tổ chức mình
                    $org = \App\Models\Organization::where('owner_id', $user->id)->first();
                    if ($org) {
                        $query->whereHas('collaborator', function ($q) use ($org) {
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
            'index' => Pages\ListWallets::route('/'),
            'view' => Pages\ViewWallet::route('/{record}'),
        ];
    }
}
