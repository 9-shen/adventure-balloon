<?php

namespace App\Filament\Transport\Resources;

use App\Filament\Transport\Resources\TransportBillResource\Pages;
use App\Models\TransportBill;
use App\Services\TransportBillService;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables;
use Filament\Actions\Action as TableAction;
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
        return 'Billing & Finance';
    }

    public static function getNavigationLabel(): string
    {
        return 'My Bills';
    }

    public static function getModelLabel(): string
    {
        return 'Bill';
    }

    protected static ?int $navigationSort = 30;

    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return parent::getEloquentQuery()
            ->where('transport_company_id', $user->transport_company_id);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bill_ref')
                    ->label('Bill Ref')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('period')
                    ->label('Period')
                    ->getStateUsing(fn (TransportBill $b): string =>
                        ($b->period_from?->format('d/m/Y') ?? '—') . ' → ' . ($b->period_to?->format('d/m/Y') ?? '—')
                    ),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total (Inc. Tax)')
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
                        'sent'    => 'warning',
                        'paid'    => 'success',
                        'overdue' => 'danger',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('created_at')
                    ->label('Issued On')
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
                        'sent'    => 'Pending Payment (Sent)',
                        'paid'    => 'Paid',
                        'overdue' => 'Overdue',
                    ]),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')->label('From Issue Date')->displayFormat('d/m/Y'),
                        DatePicker::make('until')->label('Until Issue Date')->displayFormat('d/m/Y'),
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
                \Filament\Actions\ViewAction::make(),
                
                \Filament\Actions\Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(function (TransportBill $record) {
                        $pdf = app(TransportBillService::class)->generatePdf($record);
                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            $record->bill_ref . '.pdf'
                        );
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransportBills::route('/'),
            'view'  => Pages\ViewTransportBill::route('/{record}'),
        ];
    }
}
