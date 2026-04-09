<?php

namespace App\Filament\Admin\Resources\Invoicing\PartnerInvoiceResource\Pages;

use App\Filament\Admin\Resources\Invoicing\PartnerInvoiceResource;
use App\Filament\Admin\Resources\Invoicing\InvoiceResource;
use App\Models\Booking;
use App\Models\Partner;
use App\Services\InvoiceService;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Builder;

class ViewPartnerBookings extends ManageRelatedRecords
{
    protected static string $resource    = PartnerInvoiceResource::class;
    protected static string $relationship = 'bookings';
    protected static ?string $title      = 'Partner Bookings';

    public static function getNavigationLabel(): string
    {
        return 'Bookings';
    }

    // Track the IDs the accountant has bulk-selected for invoicing
    public array $selectedForInvoice = [];

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return $this->record->company_name . ' — Bookings';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_invoice')
                ->label(fn () => 'Create Invoice' . (count($this->selectedForInvoice) > 0 ? ' (' . count($this->selectedForInvoice) . ' bookings)' : ''))
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->disabled(fn () => empty($this->selectedForInvoice))
                ->form([
                    TextInput::make('booking_count')
                        ->label('Bookings Selected')
                        ->disabled()
                        ->dehydrated(false)
                        ->default(fn () => count($this->selectedForInvoice)),

                    TextInput::make('tax_rate')
                        ->label('Tax Rate (%)')
                        ->numeric()
                        ->default(0)
                        ->suffix('%')
                        ->helperText('Use 0 for tax-free invoices'),

                    Textarea::make('notes')
                        ->label('Invoice Notes (optional)')
                        ->placeholder('Will appear on the PDF invoice...')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    if (empty($this->selectedForInvoice)) {
                        Notification::make()->title('No bookings selected')->warning()->send();
                        return;
                    }

                    /** @var Partner $partner */
                    $partner = $this->record;
                    $invoice = app(InvoiceService::class)->generate($partner, $this->selectedForInvoice, [
                        'tax_rate' => (float) $data['tax_rate'],
                        'notes'    => $data['notes'] ?? null,
                    ]);

                    $this->selectedForInvoice = [];

                    Notification::make()
                        ->title('✅ Invoice ' . $invoice->invoice_ref . ' created!')
                        ->body($invoice->items->count() . ' booking(s) — MAD ' . number_format((float) $invoice->total_amount, 2))
                        ->success()
                        ->send();

                    $this->redirect(InvoiceResource::getUrl('view', ['record' => $invoice->id]));
                })
                ->slideOver()
                ->modalWidth('md'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('booking_ref')
            ->columns([
                TextColumn::make('booking_ref')
                    ->label('Ref')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color(fn (Booking $b) => $b->isInvoiced() ? 'gray' : 'primary'),

                TextColumn::make('flight_date')
                    ->label('Flight Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->limit(22),

                TextColumn::make('pax_display')
                    ->label('PAX')
                    ->getStateUsing(fn (Booking $b) =>
                        $b->adult_pax . 'A' . ($b->child_pax > 0 ? ' + ' . $b->child_pax . 'C' : '')
                    ),

                TextColumn::make('final_amount')
                    ->label('Total')
                    ->money('MAD')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('MAD')
                    ->color('success'),

                TextColumn::make('balance_due')
                    ->label('Balance')
                    ->money('MAD')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->weight('bold'),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'due' => 'danger', 'partial' => 'warning',
                        'on_site' => 'info', 'paid' => 'success', default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('booking_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success', 'cancelled' => 'danger',
                        'completed' => 'info', default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('invoiced_label')
                    ->label('Invoiced')
                    ->getStateUsing(fn (Booking $b) => $b->isInvoiced() ? '✅ Yes' : '—')
                    ->badge()
                    ->color(fn (Booking $b) => $b->isInvoiced() ? 'success' : 'gray'),
            ])
            ->filters([
                // ─── Date Range ─────────────────────────────────────────────
                Filter::make('date_range')
                    ->label('Flight Date Range')
                    ->form([
                        DatePicker::make('from')
                            ->label('From Date')
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('until')
                            ->label('Until Date')
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'],  fn ($q, $v) => $q->whereDate('flight_date', '>=', $v))
                            ->when($data['until'], fn ($q, $v) => $q->whereDate('flight_date', '<=', $v));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'])  $indicators[] = Tables\Filters\Indicator::make('From: ' . $data['from']);
                        if ($data['until']) $indicators[] = Tables\Filters\Indicator::make('Until: ' . $data['until']);
                        return $indicators;
                    }),

                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'due' => 'Due', 'partial' => 'Partial',
                        'paid' => 'Paid', 'on_site' => 'On Site',
                    ]),

                SelectFilter::make('booking_status')
                    ->label('Booking Status')
                    ->options([
                        'pending' => 'Pending', 'confirmed' => 'Confirmed',
                        'completed' => 'Completed', 'cancelled' => 'Cancelled',
                    ]),

                Filter::make('not_invoiced')
                    ->label('Not Yet Invoiced')
                    ->toggle()
                    ->query(fn (Builder $q) => $q->whereNull('invoiced_at')),
            ])
            ->bulkActions([
                BulkAction::make('add_to_invoice')
                    ->label('Add to Invoice')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->action(function ($records): void {
                        $eligible = $records->filter(fn (Booking $b) => !$b->isInvoiced());

                        if ($eligible->isEmpty()) {
                            Notification::make()
                                ->title('All selected bookings are already invoiced')
                                ->warning()->send();
                            return;
                        }

                        $ids = $eligible->pluck('id')->toArray();
                        $this->selectedForInvoice = array_unique(
                            array_merge($this->selectedForInvoice, $ids)
                        );

                        $total = (float) Booking::whereIn('id', $this->selectedForInvoice)->sum('final_amount');

                        Notification::make()
                            ->title(count($ids) . ' booking(s) added to invoice basket')
                            ->body('Total basket: ' . count($this->selectedForInvoice) . ' bookings — MAD ' . number_format((float) Booking::whereIn('id', $this->selectedForInvoice)->sum('final_amount'), 2))
                            ->success()->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('remove_from_invoice')
                    ->label('Remove from Basket')
                    ->icon('heroicon-o-minus-circle')
                    ->color('danger')
                    ->action(function ($records): void {
                        $ids = $records->pluck('id')->toArray();
                        $this->selectedForInvoice = array_values(
                            array_diff($this->selectedForInvoice, $ids)
                        );

                        Notification::make()
                            ->title(count($ids) . ' booking(s) removed from basket')
                            ->warning()->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('flight_date', 'desc')
            ->striped();
    }
}
