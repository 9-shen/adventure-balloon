<?php

namespace App\Filament\Admin\Resources\TransportFinance;

use App\Models\TransportCompany;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action as TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TransporterBillingResource extends Resource
{
    protected static ?string $model = TransportCompany::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-truck';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Transport Finance';
    }

    public static function getNavigationLabel(): string
    {
        return 'Transporters & Dispatches';
    }

    public static function getModelLabel(): string
    {
        return 'Transporter';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Transporters & Dispatches';
    }

    protected static ?string $slug = 'transport-finance/transporters';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin', 'accountant']) ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                TransportCompany::query()
                    ->where('is_active', true)
                    ->withCount('dispatches')
                    ->withSum('dispatches', 'transport_cost')
                    ->withCount('transportBills')
                    ->withSum(['transportBills as total_billed' => fn (Builder $q) => $q->whereIn('status', ['draft', 'sent', 'paid', 'overdue'])], 'total_amount')
                    ->withSum(['transportBills as total_paid' => fn (Builder $q) => $q->where('status', 'paid')], 'total_amount')
            )
            ->columns([
                TextColumn::make('company_name')
                    ->label('Transport Company')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (TransportCompany $tc): string => $tc->contact_name ?? ''),

                TextColumn::make('dispatches_count')
                    ->label('Dispatches')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('dispatches_sum_transport_cost')
                    ->label('Total Cost')
                    ->money('MAD')
                    ->sortable()
                    ->default(0),

                TextColumn::make('total_billed')
                    ->label('Total Billed')
                    ->money('MAD')
                    ->sortable()
                    ->default(0),

                TextColumn::make('total_paid')
                    ->label('Total Paid')
                    ->money('MAD')
                    ->color('success')
                    ->sortable()
                    ->default(0),

                TextColumn::make('outstanding')
                    ->label('Outstanding')
                    ->getStateUsing(fn (TransportCompany $tc): float =>
                        (float) ($tc->total_billed ?? 0) - (float) ($tc->total_paid ?? 0)
                    )
                    ->money('MAD')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->weight('bold'),

                TextColumn::make('transport_bills_count')
                    ->label('Bills')
                    ->badge()
                    ->color('warning')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->default('1'),
            ])
            ->actions([
                TableAction::make('view_dispatches')
                    ->label('View Dispatches')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (TransportCompany $tc): string =>
                        TransporterBillingResource::getUrl('manage', ['record' => $tc->id])
                    ),
            ])
            ->defaultSort('company_name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Filament\Admin\Resources\TransportFinance\TransporterBillingResource\Pages\ListTransporters::route('/'),
            'manage' => \App\Filament\Admin\Resources\TransportFinance\TransporterBillingResource\Pages\ViewTransporterDispatches::route('/{record}/dispatches'),
        ];
    }
}
