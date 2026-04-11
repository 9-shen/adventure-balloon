<?php

namespace App\Filament\Accountant\Resources;

use App\Models\TransportBill;
use App\Services\TransportBillService;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables;
use Filament\Actions\Action as TableAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TransportBillResource extends Resource
{
    protected static ?string $model = TransportBill::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-document-currency-dollar';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Transport Finance';
    }

    public static function getNavigationLabel(): string
    {
        return 'Transport Bills';
    }

    public static function getModelLabel(): string
    {
        return 'Transport Bill';
    }

    protected static ?string $slug = 'transport-finance/bills';
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
            ->query(
                TransportBill::query()
                    ->with('transportCompany')
                    ->withCount('items')
            )
            ->columns([
                TextColumn::make('bill_ref')
                    ->label('Bill Ref')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('transportCompany.company_name')
                    ->label('Transport Company')
                    ->searchable()
                    ->sortable()
                    ->limit(25),

                TextColumn::make('period')
                    ->label('Period')
                    ->getStateUsing(fn (TransportBill $b): string =>
                        ($b->period_from?->format('d/m/Y') ?? '—') . ' → ' . ($b->period_to?->format('d/m/Y') ?? '—')
                    ),

                TextColumn::make('items_count')
                    ->label('Dispatches')
                    ->badge()
                    ->color('info')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('MAD')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('balance_due')
                    ->label('Balance Due')
                    ->money('MAD')
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'success')
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

                SelectFilter::make('transport_company_id')
                    ->label('Transport Company')
                    ->relationship('transportCompany', 'company_name')
                    ->searchable()
                    ->preload(),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')->label('Created From')->displayFormat('d/m/Y'),
                        DatePicker::make('until')->label('Created Until')->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'],  fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                            ->when($data['until'], fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'])  $indicators[] = Tables\Filters\Indicator::make('From: ' . $data['from']);
                        if ($data['until']) $indicators[] = Tables\Filters\Indicator::make('Until: ' . $data['until']);
                        return $indicators;
                    }),
            ])
            ->actions([
                TableAction::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (TransportBill $b): string => TransportBillResource::getUrl('view', ['record' => $b->id])),

                TableAction::make('download_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(function (TransportBill $record) {
                        $pdf = app(TransportBillService::class)->generatePdf($record);
                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            $record->bill_ref . '.pdf'
                        );
                    }),

                TableAction::make('mark_sent')
                    ->label('Mark Sent')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->hidden(fn (TransportBill $b) => !$b->isDraft())
                    ->requiresConfirmation()
                    ->action(function (TransportBill $record) {
                        app(TransportBillService::class)->markSent($record);
                        Notification::make()->title('Bill marked as sent')->success()->send();
                    }),

                TableAction::make('mark_paid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->hidden(fn (TransportBill $b) => $b->isPaid())
                    ->form([
                        TextInput::make('payment_reference')
                            ->label('Payment Reference')
                            ->placeholder('Bank transfer ref, cheque #, etc.'),
                    ])
                    ->action(function (TransportBill $record, array $data) {
                        app(TransportBillService::class)->markPaid($record, $data['payment_reference'] ?? null);
                        Notification::make()->title('✅ Bill marked as paid')->success()->send();
                    })
                    ->slideOver()
                    ->modalWidth('md'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Accountant\Resources\TransportBillResource\Pages\ListTransportBills::route('/'),
            'view'  => \App\Filament\Accountant\Resources\TransportBillResource\Pages\ViewTransportBill::route('/{record}'),
        ];
    }
}

