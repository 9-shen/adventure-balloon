<?php

namespace App\Filament\Transport\Resources\TransportBillResource\Pages;

use App\Filament\Transport\Resources\TransportBillResource;
use App\Models\TransportBill;
use App\Services\TransportBillService;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Actions\Action;

class ViewTransportBill extends ViewRecord
{
    protected static string $resource = TransportBillResource::class;

    public function getTitle(): string
    {
        return 'View Bill ' . $this->record->bill_ref;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(function (): \Symfony\Component\HttpFoundation\StreamedResponse {
                    $pdf = app(TransportBillService::class)->generatePdf($this->record);
                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        $this->record->bill_ref . '.pdf',
                        ['Content-Type' => 'application/pdf']
                    );
                }),
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
                            'sent'    => 'warning',
                            'paid'    => 'success',
                            'overdue' => 'danger',
                            default   => 'gray',
                        })
                        ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                    TextEntry::make('created_at')
                        ->label('Issued On')
                        ->date('d/m/Y'),

                    TextEntry::make('period_range')
                        ->label('Period')
                        ->getStateUsing(fn ($record) =>
                            ($record->period_from?->format('d/m/Y') ?? '—') . ' → ' . ($record->period_to?->format('d/m/Y') ?? '—')
                        ),
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
                        ->label('Total (Inc. Tax)')
                        ->money('MAD')
                        ->weight('bold')
                        ->color('primary'),

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
                        
                    TextEntry::make('notes')
                        ->label('Notes')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),

            // ─── Line Items ───────────────────────────────────────────────
            Section::make('Dispatches Involved')
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
                                ->color('gray'),

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
                                ->label('Base Cost')
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
