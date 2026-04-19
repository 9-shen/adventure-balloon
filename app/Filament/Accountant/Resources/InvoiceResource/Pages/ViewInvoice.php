<?php

namespace App\Filament\Accountant\Resources\InvoiceResource\Pages;

use App\Filament\Accountant\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    public function getTitle(): string
    {
        return 'Invoice ' . $this->record->invoice_ref;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function (): \Symfony\Component\HttpFoundation\StreamedResponse {
                    $service  = app(InvoiceService::class);
                    $pdf      = $service->generatePdf($this->record);
                    $filename = $this->record->invoice_ref . '.pdf';

                    return response()->streamDownload(
                        fn() => print($pdf),
                        $filename,
                        ['Content-Type' => 'application/pdf']
                    );
                }),

            Action::make('mark_sent')
                ->label('Mark as Sent')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn() => $this->record->isDraft())
                ->requiresConfirmation()
                ->action(function (): void {
                    app(InvoiceService::class)->markSent($this->record);
                    $this->refreshFormData(['status', 'sent_at']);
                    Notification::make()->title('Invoice marked as sent')->success()->send();
                }),

            Action::make('mark_paid')
                ->label('Mark as Paid')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn() => !$this->record->isPaid())
                ->form([
                    TextInput::make('payment_reference')
                        ->label('Payment Reference')
                        ->placeholder('e.g. WIRE-2026-00123')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    app(InvoiceService::class)->markPaid($this->record, $data['payment_reference']);
                    $this->refreshFormData(['status', 'paid_at', 'payment_reference']);
                    Notification::make()->title('Invoice marked as paid ✅')->success()->send();
                })
                ->slideOver(),
        ];
    }

    public function infolist(Schema $infolist): Schema
    {
        return $infolist->components([

            // ─── Invoice Header ──────────────────────────────────────────────
            Section::make('Invoice Details')
                ->columns(4)
                ->components([
                    TextEntry::make('invoice_ref')
                        ->label('Invoice #')
                        ->badge()
                        ->color('primary')
                        ->copyable(),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn(string $state): string => match ($state) {
                            'draft'   => 'gray',
                            'sent'    => 'info',
                            'paid'    => 'success',
                            'overdue' => 'danger',
                            default   => 'gray',
                        })
                        ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                    TextEntry::make('created_at')
                        ->label('Invoice Date')
                        ->date('d/m/Y'),

                    TextEntry::make('period_range')
                        ->label('Period')
                        ->getStateUsing(
                            fn($record) =>
                            $record->period_from->format('d/m/Y') . ' → ' . $record->period_to->format('d/m/Y')
                        ),
                ]),

            // ─── Partner Info ─────────────────────────────────────────────────
            Section::make('Partner / Bill To')
                ->columns(3)
                ->components([
                    TextEntry::make('partner.company_name')
                        ->label('Company')
                        ->weight('bold'),

                    TextEntry::make('partner.email')
                        ->label('Email')
                        ->copyable()
                        ->placeholder('—'),

                    TextEntry::make('partner.phone')
                        ->label('Phone')
                        ->copyable()
                        ->placeholder('—'),

                    TextEntry::make('partner.address')
                        ->label('Address')
                        ->placeholder('—'),

                    TextEntry::make('partner.tax_number')
                        ->label('Tax Number')
                        ->placeholder('—'),

                    TextEntry::make('partner.payment_terms_days')
                        ->label('Payment Terms')
                        ->formatStateUsing(fn($state) => $state ? "{$state} days" : '30 days'),
                ]),

            // ─── Financial Summary ─────────────────────────────────────────────
            Section::make('Financial Summary')
                ->columns(4)
                ->components([
                    TextEntry::make('subtotal')
                        ->label('Subtotal')
                        ->money('MAD'),

                    TextEntry::make('tax_summary')
                        ->label('Tax')
                        ->getStateUsing(
                            fn($record) =>
                            $record->tax_rate > 0
                                ? 'MAD ' . number_format($record->tax_amount, 2) . ' (' . $record->tax_rate . '%)'
                                : '—'
                        ),

                    TextEntry::make('total_amount')
                        ->label('Total Due')
                        ->money('MAD')
                        ->weight('bold')
                        ->color('danger'),

                    TextEntry::make('paid_at')
                        ->label('Paid On')
                        ->date('d/m/Y')
                        ->placeholder('Unpaid')
                        ->color(fn($state) => $state ? 'success' : 'gray'),

                    TextEntry::make('payment_reference')
                        ->label('Payment Reference')
                        ->placeholder('—')
                        ->copyable(),

                    TextEntry::make('sent_at')
                        ->label('Sent At')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('Not sent yet'),

                    TextEntry::make('createdBy.name')
                        ->label('Created By')
                        ->placeholder('—'),

                    TextEntry::make('notes')
                        ->label('Notes')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ])->columnSpanFull(),

            // ─── Line Items ────────────────────────────────────────────────────
            Section::make('Booking Lines')
                ->components([
                    RepeatableEntry::make('items')
                        ->label('')
                        ->columns(7)
                        ->contained(false)
                        ->schema([
                            TextEntry::make('flight_date')
                                ->label('Date')
                                ->date('d/m/Y'),

                            TextEntry::make('booking.booking_ref')
                                ->label('Booking Ref')
                                ->badge()
                                ->color('primary'),

                            TextEntry::make('description')
                                ->label('Description'),

                            TextEntry::make('adult_pax')
                                ->label('Adults')
                                ->badge()
                                ->color('info'),

                            TextEntry::make('child_pax')
                                ->label('Children')
                                ->badge()
                                ->color('warning'),

                            TextEntry::make('unit_price')
                                ->label('Unit Price')
                                ->money('MAD'),

                            TextEntry::make('line_total')
                                ->label('Amount')
                                ->money('MAD')
                                ->weight('bold'),
                        ]),
                ])->columnSpanFull(),
        ]);
    }
}
