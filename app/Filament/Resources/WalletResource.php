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

    protected static string|\UnitEnum|null $navigationGroup = 'Cộng tác viên';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool {
        $user = \Illuminate\Support\Facades\Auth::user();

        if ($user->role === 'super_admin') {
            // Super admin luôn thấy menu này
            return true;
        }

        if ($user->role === 'ctv') {
            // CTV thấy menu ví tiền để xem số dư của mình
            return true;
        }

        // Chủ đơn vị không cần chức năng ví
        return false;
    }

    public static function form(Schema $schema): Schema {
        return $schema
            ->schema([
                Forms\Components\Select::make('collaborator_id')
                    ->label('CTV')
                    ->options(function () {
                        $user = \Illuminate\Support\Facades\Auth::user();
                        if ($user->role === 'super_admin') {
                            return \App\Models\Collaborator::pluck('full_name', 'id');
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
                    ->sortable()
                    ->visible(function (): bool {
                        $user = \Illuminate\Support\Facades\Auth::user();
                        if ($user->role === 'super_admin') return true;

                        if ($user->role === 'ctv') {
                            $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();
                            // CTV cấp 1 (không có upline) thấy cột chi để theo dõi chuyển cho CTV2
                            // CTV cấp 2 (có upline) không thấy cột chi
                            return $collaborator && $collaborator->upline_id === null;
                        }

                        return false;
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Cập nhật lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->visible(fn(): bool => \Illuminate\Support\Facades\Auth::user()->role === 'super_admin'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('collaborator.organization_id')
                    ->label('Tổ chức')
                    ->options(function () {
                        $user = \Illuminate\Support\Facades\Auth::user();
                        if ($user->role === 'super_admin') {
                            return \App\Models\Organization::pluck('name', 'id');
                        }
                        return [];
                    }),
            ])
            ->actions([
                ViewAction::make(),
                // Đã loại bỏ chức năng giao dịch
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $user = \Illuminate\Support\Facades\Auth::user();
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
