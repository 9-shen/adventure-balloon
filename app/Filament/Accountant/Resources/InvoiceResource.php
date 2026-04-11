<?php

namespace App\Filament\Accountant\Resources;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Invoicing';
    }

    public static function getNavigationLabel(): string
    {
        return 'Invoices';
    }

    public static function getModelLabel(): string
    {
        return 'Invoice';
    }

    protected static ?string $slug = 'invoicing/invoices';
    protected static ?string $recordTitleAttribute = 'invoice_ref';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin', 'accountant']) ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Invoice::query()->with(['partner'])->withCount('items'))
            ->columns([
                TextColumn::make('invoice_ref')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->copyable(),

                TextColumn::make('partner.company_name')
                    ->label('Partner')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('period')
                    ->label('Period')
                    ->getStateUsing(fn (Invoice $inv) =>
                        $inv->period_from->format('d/m/Y') . ' → ' . $inv->period_to->format('d/m/Y')
                    ),

                TextColumn::make('items_count')
                    ->label('Bookings')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('MAD'),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('MAD')
                    ->weight('bold'),

                TextColumn::make('status')
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

                TextColumn::make('created_at')
                    ->label('Created')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('paid_at')
                    ->label('Paid On')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft'   => 'Draft',
                        'sent'    => 'Sent',
                        'paid'    => 'Paid',
                        'overdue' => 'Overdue',
                    ]),

                SelectFilter::make('partner')
                    ->relationship('partner', 'company_name')
                    ->searchable()
                    ->preload(),

                Filter::make('date_range')
                    ->label('Date Range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From')->displayFormat('d/m/Y'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Until')->displayFormat('d/m/Y'),
                    ])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['from'],  fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                        ->when($data['until'], fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
                    ),
            ])
            ->actions([
                ViewAction::make()->label('View'),

                Action::make('download_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (Invoice $record): \Symfony\Component\HttpFoundation\StreamedResponse {
                        $service = app(InvoiceService::class);
                        $pdf     = $service->generatePdf($record);
                        $filename = $record->invoice_ref . '.pdf';

                        return response()->streamDownload(fn () => print($pdf), $filename, [
                            'Content-Type' => 'application/pdf',
                        ]);
                    }),

                Action::make('mark_sent')
                    ->label('Mark Sent')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (Invoice $record) => $record->isDraft())
                    ->requiresConfirmation()
                    ->action(function (Invoice $record): void {
                        app(InvoiceService::class)->markSent($record);
                        \Filament\Notifications\Notification::make()->title('Invoice marked as sent')->success()->send();
                    }),

                Action::make('mark_paid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Invoice $record) => !$record->isPaid())
                    ->form([
                        TextInput::make('payment_reference')
                            ->label('Payment Reference')
                            ->placeholder('e.g. WIRE-2026-00123')
                            ->required(),
                    ])
                    ->action(function (array $data, Invoice $record): void {
                        app(InvoiceService::class)->markPaid($record, $data['payment_reference']);
                        \Filament\Notifications\Notification::make()->title('Invoice marked as paid ✅')->success()->send();
                    })
                    ->slideOver(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Accountant\Resources\InvoiceResource\Pages\ListInvoices::route('/'),
            'view'  => \App\Filament\Accountant\Resources\InvoiceResource\Pages\ViewInvoice::route('/{record}'),
        ];
    }
}

