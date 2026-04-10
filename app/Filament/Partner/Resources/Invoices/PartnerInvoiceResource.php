<?php

namespace App\Filament\Partner\Resources\Invoices;

use App\Filament\Partner\Resources\Invoices\Pages\ListPartnerInvoices;
use App\Models\Invoice;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PartnerInvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationLabel(): string
    {
        return 'Invoices';
    }

    public static function getModelLabel(): string
    {
        return 'Invoice';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Invoices';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'My Bookings';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return parent::getEloquentQuery()
            ->where('partner_id', $user->partner_id)
            ->latest();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_ref')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('period_from')
                    ->label('Period')
                    ->formatStateUsing(fn ($record) =>
                        optional($record->period_from)?->format('d/m/Y')
                        . ' → '
                        . optional($record->period_to)?->format('d/m/Y')
                    ),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('MAD'),

                TextColumn::make('tax_amount')
                    ->label('Tax')
                    ->money('MAD'),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('MAD')
                    ->weight('bold'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'gray'    => 'draft',
                        'info'    => 'sent',
                        'success' => 'paid',
                        'danger'  => 'overdue',
                    ]),

                TextColumn::make('sent_at')
                    ->label('Sent')
                    ->date('d/m/Y')
                    ->placeholder('—'),

                TextColumn::make('paid_at')
                    ->label('Paid On')
                    ->date('d/m/Y')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft'   => 'Draft',
                        'sent'    => 'Sent',
                        'paid'    => 'Paid',
                        'overdue' => 'Overdue',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPartnerInvoices::route('/'),
        ];
    }
}
