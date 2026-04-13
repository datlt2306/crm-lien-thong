<?php

namespace App\Filament\Resources\AnnualQuotas\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class AnnualQuotasTable {
    public static function configure(Table $table): Table {
        $user = \Illuminate\Support\Facades\Auth::user();
        $canEdit = $user && in_array($user->role, ['super_admin', 'organization_owner']);

        return $table
            ->recordUrl(fn($r) => ($canEdit && $r) ? \App\Filament\Resources\AnnualQuotas\AnnualQuotaResource::getUrl('edit', ['record' => $r]) : null)
            ->columns([
                TextColumn::make('organization.name')
                    ->label('Tổ chức')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('major_name')
                    ->label('Ngành')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('program_name')
                    ->label('Hệ đào tạo')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('year')
                    ->label('Năm')
                    ->sortable(),

                TextColumn::make('target_quota')
                    ->label('Chỉ tiêu')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('current_quota')
                    ->label('Đã tuyển')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('available_slots')
                    ->label('Còn lại')
                    ->getStateUsing(fn($record) => max(0, ($record?->target_quota ?? 0) - ($record?->current_quota ?? 0)))
                    ->color(fn($state) => $state <= 0 ? 'danger' : ($state <= 10 ? 'warning' : 'success')),

                BadgeColumn::make('status')
                    ->label('Trạng thái')
                    ->formatStateUsing(fn($state) => \App\Models\AnnualQuota::getStatusOptions()[$state] ?? $state ?? 'N/A')
                    ->colors([
                        'success' => \App\Models\AnnualQuota::STATUS_ACTIVE,
                        'warning' => \App\Models\AnnualQuota::STATUS_INACTIVE,
                        'gray' => \App\Models\AnnualQuota::STATUS_FULL,
                    ]),


                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('organization_id')
                    ->label('Tổ chức')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('major_name')
                    ->label('Ngành')
                    ->options(fn() => \Illuminate\Support\Facades\DB::table('annual_quotas')->whereNotNull('major_name')->distinct()->pluck('major_name', 'major_name')->toArray())
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('program_name')
                    ->label('Hệ đào tạo')
                    ->options(fn() => \Illuminate\Support\Facades\DB::table('annual_quotas')->whereNotNull('program_name')->distinct()->pluck('program_name', 'program_name')->toArray())
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('year')
                    ->label('Năm')
                    ->options(fn() => collect(range(now()->year - 2, now()->year + 2))->mapWithKeys(fn($y) => [$y => (string) $y])),
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(\App\Models\AnnualQuota::getStatusOptions()),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->visible(fn() => $canEdit),
                ])->label('Hành động')->icon('heroicon-m-ellipsis-vertical')->color('gray')->button()->size('sm'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalHeading('Xóa chỉ tiêu năm đã chọn')
                        ->visible(fn() => $canEdit),
                ]),
            ])
            ->defaultSort('year', 'desc');
    }
}
