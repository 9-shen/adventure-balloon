<?php

namespace App\Filament\Admin\Resources\Invoicing;

use App\Models\Partner;
use App\Models\Booking;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action as TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PartnerInvoiceResource extends Resource
{
    protected static ?string $model = Partner::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-building-office-2';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Invoicing';
    }

    public static function getNavigationLabel(): string
    {
        return 'Partners & Bookings';
    }

    public static function getModelLabel(): string
    {
        return 'Partner';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Partners & Bookings';
    }

    protected static ?string $slug = 'invoicing/partners';
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
                Partner::query()
                    ->withCount(['bookings as total_bookings' => fn (Builder $q) => $q->where('type', 'partner')])
                    ->withSum(['bookings as total_billed' => fn (Builder $q) => $q->where('type', 'partner')], 'final_amount')
                    ->withSum(['bookings as total_paid' => fn (Builder $q) => $q->where('type', 'partner')], 'amount_paid')
                    ->withSum(['bookings as total_outstanding' => fn (Builder $q) => $q->where('type', 'partner')], 'balance_due')
                    ->withCount(['invoices as invoices_count'])
            )
            ->columns([
                TextColumn::make('company_name')
                    ->label('Partner')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Partner $p): string => $p->email ?? ''),

                TextColumn::make('total_bookings')
                    ->label('Bookings')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('total_billed')
                    ->label('Total Billed')
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('total_paid')
                    ->label('Total Paid')
                    ->money('MAD')
                    ->color('success')
                    ->sortable(),

                TextColumn::make('total_outstanding')
                    ->label('Outstanding')
                    ->money('MAD')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('invoices_count')
                    ->label('Invoices')
                    ->badge()
                    ->color('warning')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'suspended' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'approved'  => 'Approved',
                        'suspended' => 'Suspended',
                        'pending'   => 'Pending',
                    ]),
            ])
            ->actions([
                TableAction::make('view_bookings')
                    ->label('View Bookings')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (Partner $partner): string =>
                        \App\Filament\Admin\Resources\Invoicing\PartnerInvoiceResource::getUrl('manage', ['record' => $partner->id])
                    ),
            ])
            ->defaultSort('company_name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Filament\Admin\Resources\Invoicing\PartnerInvoiceResource\Pages\ListPartnerInvoices::route('/'),
            'manage' => \App\Filament\Admin\Resources\Invoicing\PartnerInvoiceResource\Pages\ViewPartnerBookings::route('/{record}/bookings'),
        ];
    }
}
