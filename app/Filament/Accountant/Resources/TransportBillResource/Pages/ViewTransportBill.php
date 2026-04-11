<?php

namespace App\Filament\Accountant\Resources\TransportBillResource\Pages;

use App\Filament\Accountant\Resources\TransportBillResource;
use App\Models\TransportBill;
use App\Services\TransportBillService;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class ViewTransportBill extends ViewRecord
{
    protected static string $resource = TransportBillResource::class;

    public function getTitle(): string
    {
        return 'Transport Bill ' . $this->record->bill_ref;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->action(function (): \Symfony\Component\HttpFoundation\StreamedResponse {
                    $pdf = app(TransportBillService::class)->generatePdf($this->record);
                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        $this->record->bill_ref . '.pdf',
                        ['Content-Type' => 'application/pdf']
                    );
                }),

            Action::make('mark_sent')
                ->label('Mark Sent')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->visible(fn () => $this->record->isDraft())
                ->requiresConfirmation()
                ->action(function () {
                    app(TransportBillService::class)->markSent($this->record);
                    $this->refreshFormData(['status', 'sent_at']);
                    Notification::make()->title('Bill marked as sent')->success()->send();
                }),

            Action::make('mark_paid')
                ->label('Mark Paid')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn () => !$this->record->isPaid())
                ->form([
                    TextInput::make('payment_reference')
                        ->label('Payment Reference')
                        ->placeholder('Bank transfer ref, cheque #, etc.'),
                ])
                ->action(function (array $data) {
                    app(TransportBillService::class)->markPaid($this->record, $data['payment_reference'] ?? null);
                    $this->refreshFormData(['status', 'paid_at', 'payment_reference', 'amount_paid', 'balance_due']);
                    Notification::make()->title('✅ Bill marked as paid')->success()->send();
                })
                ->slideOver(),
        ];
    }

    public function infolist(Schema $infolist): Schema
    {
        return $infolist->components([

            // ─── Bill Header ──────────────────────────────────────────────
            Section::make('Bill Details')
                ->columns(4)
                ->components([
                    TextEntry::make('bill_ref')
                        ->label('Bill #')
                        ->badge()
                        ->color('primary')
                        ->copyable(),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'draft'   => 'gray',
                            'sent'    => 'info',
                            'paid'    => 'success',
                            'overdue' => 'danger',
                            default   => 'gray',
                        })
                        ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                    TextEntry::make('created_at')
                        ->label('Bill Date')
                        ->date('d/m/Y'),

                    TextEntry::make('period_range')
                        ->label('Period')
                        ->getStateUsing(fn ($record) =>
                            ($record->period_from?->format('d/m/Y') ?? '—') . ' → ' . ($record->period_to?->format('d/m/Y') ?? '—')
                        ),
                ]),

            // ─── Transport Company ────────────────────────────────────────
            Section::make('Transport Company')
                ->columns(3)
                ->components([
                    TextEntry::make('transportCompany.company_name')
                        ->label('Company')
                        ->weight('bold'),

                    TextEntry::make('transportCompany.contact_name')
                        ->label('Contact')
                        ->placeholder('—'),

                    TextEntry::make('transportCompany.email')
                        ->label('Email')
                        ->copyable()
                        ->placeholder('—'),

                    TextEntry::make('transportCompany.phone')
                        ->label('Phone')
                        ->copyable()
                        ->placeholder('—'),

                    TextEntry::make('transportCompany.bank_name')
                        ->label('Bank')
                        ->placeholder('—'),

                    TextEntry::make('transportCompany.bank_iban')
                        ->label('IBAN')
                        ->placeholder('—'),
                ]),

            // ─── Financial Summary ────────────────────────────────────────
            Section::make('Financial Summary')
                ->columns(4)
                ->components([
                    TextEntry::make('subtotal')
                        ->label('Subtotal')
                        ->money('MAD'),

                    TextEntry::make('tax_summary')
                        ->label('Tax')
                        ->getStateUsing(fn ($record) =>
                            $record->tax_rate > 0
                                ? 'MAD ' . number_format((float) $record->tax_amount, 2) . ' (' . $record->tax_rate . '%)'
                                : '—'
                        ),

                    TextEntry::make('total_amount')
                        ->label('Total To Pay')
                        ->money('MAD')
                        ->weight('bold')
                        ->color('danger'),

                    TextEntry::make('balance_due')
                        ->label('Balance Due')
                        ->money('MAD')
                        ->weight('bold')
                        ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'success'),

                    TextEntry::make('paid_at')
                        ->label('Paid On')
                        ->date('d/m/Y')
                        ->placeholder('Unpaid')
                        ->color(fn ($state) => $state ? 'success' : 'gray'),

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
                ]),

            // ─── Line Items ───────────────────────────────────────────────
            Section::make('Dispatch Line Items')
                ->columnSpanFull()
                ->components([
                    RepeatableEntry::make('items')
                        ->label('')
                        ->columns(6)
                        ->contained(false)
                        ->schema([
                            TextEntry::make('dispatch.dispatch_ref')
                                ->label('Dispatch Ref')
                                ->badge()
                                ->color('primary'),

                            TextEntry::make('dispatch.flight_date')
                                ->label('Flight Date')
                                ->date('d/m/Y'),

                            TextEntry::make('description')
                                ->label('Description'),

                            TextEntry::make('vehicles_used')
                                ->label('Vehicles')
                                ->badge()
                                ->color('info'),

                            TextEntry::make('vehicle_cost')
                                ->label('Vehicle Cost')
                                ->money('MAD'),

                            TextEntry::make('line_total')
                                ->label('Line Total')
                                ->money('MAD')
                                ->weight('bold'),
                        ]),
                ]),
        ]);
    }
}

