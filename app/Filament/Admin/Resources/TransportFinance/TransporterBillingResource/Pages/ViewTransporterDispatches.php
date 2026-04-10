<?php

namespace App\Filament\Admin\Resources\TransportFinance\TransporterBillingResource\Pages;

use App\Filament\Admin\Resources\TransportFinance\TransporterBillingResource;
use App\Filament\Admin\Resources\TransportFinance\TransportBillResource;
use App\Models\Dispatch;
use App\Models\TransportCompany;
use App\Services\TransportBillService;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Builder;

class ViewTransporterDispatches extends ManageRelatedRecords
{
    protected static string $resource     = TransporterBillingResource::class;
    protected static string $relationship = 'dispatches';
    protected static ?string $title       = 'Transporter Dispatches';

    public static function getNavigationLabel(): string
    {
        return 'Dispatches';
    }

    // Track the IDs the accountant has selected for billing
    public array $selectedForBill = [];

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return $this->record->company_name . ' — Dispatches';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_bill')
                ->label(fn () => 'Create Bill' . (count($this->selectedForBill) > 0 ? ' (' . count($this->selectedForBill) . ' dispatches)' : ''))
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->disabled(fn () => empty($this->selectedForBill))
                ->form([
                    TextInput::make('dispatch_count')
                        ->label('Dispatches Selected')
                        ->disabled()
                        ->dehydrated(false)
                        ->default(fn () => count($this->selectedForBill)),

                    TextInput::make('estimated_total')
                        ->label('Estimated Total (MAD)')
                        ->disabled()
                        ->dehydrated(false)
                        ->default(fn () => number_format(
                            (float) Dispatch::whereIn('id', $this->selectedForBill)->sum('transport_cost'), 2
                        )),

                    TextInput::make('tax_rate')
                        ->label('Tax Rate (%)')
                        ->numeric()
                        ->default(0)
                        ->suffix('%')
                        ->helperText('Use 0 for tax-free bills'),

                    Textarea::make('notes')
                        ->label('Bill Notes (optional)')
                        ->placeholder('Will appear on the PDF bill...')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    if (empty($this->selectedForBill)) {
                        Notification::make()->title('No dispatches selected')->warning()->send();
                        return;
                    }

                    /** @var TransportCompany $company */
                    $company = $this->record;
                    $bill = app(TransportBillService::class)->generate($company, $this->selectedForBill, [
                        'tax_rate' => (float) $data['tax_rate'],
                        'notes'    => $data['notes'] ?? null,
                    ]);

                    $this->selectedForBill = [];

                    Notification::make()
                        ->title('✅ Bill ' . $bill->bill_ref . ' created!')
                        ->body($bill->items->count() . ' dispatch(es) — MAD ' . number_format((float) $bill->total_amount, 2))
                        ->success()
                        ->send();

                    $this->redirect(TransportBillResource::getUrl('view', ['record' => $bill->id]));
                })
                ->slideOver()
                ->modalWidth('md'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('dispatch_ref')
            ->columns([
                TextColumn::make('dispatch_ref')
                    ->label('Dispatch Ref')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color(fn (Dispatch $d) => $d->isBilled() ? 'gray' : 'primary'),

                TextColumn::make('booking.booking_ref')
                    ->label('Booking')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('flight_date')
                    ->label('Flight Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('total_pax')
                    ->label('PAX')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                TextColumn::make('vehicles_count')
                    ->label('Vehicles')
                    ->getStateUsing(fn (Dispatch $d) => $d->dispatchDriverRows()->count())
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('transport_cost')
                    ->label('Cost (MAD)')
                    ->money('MAD')
                    ->sortable()
                    ->weight('bold')
                    ->default(0),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed'   => 'success',
                        'in_progress' => 'info',
                        'delivered'   => 'primary',
                        'cancelled'   => 'danger',
                        default       => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),

                IconColumn::make('billed_at')
                    ->label('Billed')
                    ->boolean()
                    ->getStateUsing(fn (Dispatch $d) => $d->isBilled())
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->filters([
                // ─── Date Range ──────────────────────────────────────────────
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

                SelectFilter::make('status')
                    ->label('Dispatch Status')
                    ->options([
                        'pending'     => 'Pending',
                        'confirmed'   => 'Confirmed',
                        'in_progress' => 'In Progress',
                        'delivered'   => 'Delivered',
                        'cancelled'   => 'Cancelled',
                    ]),

                Filter::make('not_billed')
                    ->label('Not Yet Billed')
                    ->toggle()
                    ->query(fn (Builder $q) => $q->whereNull('billed_at')),
            ])
            ->bulkActions([
                BulkAction::make('add_to_bill')
                    ->label('Add to Bill')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->action(function ($records): void {
                        $eligible = $records->filter(fn (Dispatch $d) => !$d->isBilled());

                        if ($eligible->isEmpty()) {
                            Notification::make()
                                ->title('All selected dispatches are already billed')
                                ->warning()->send();
                            return;
                        }

                        $ids = $eligible->pluck('id')->toArray();
                        $this->selectedForBill = array_unique(
                            array_merge($this->selectedForBill, $ids)
                        );

                        Notification::make()
                            ->title(count($ids) . ' dispatch(es) added to bill basket')
                            ->body('Total basket: ' . count($this->selectedForBill) . ' dispatches — MAD ' . number_format((float) Dispatch::whereIn('id', $this->selectedForBill)->sum('transport_cost'), 2))
                            ->success()->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('remove_from_bill')
                    ->label('Remove from Basket')
                    ->icon('heroicon-o-minus-circle')
                    ->color('danger')
                    ->action(function ($records): void {
                        $ids = $records->pluck('id')->toArray();
                        $this->selectedForBill = array_values(
                            array_diff($this->selectedForBill, $ids)
                        );

                        Notification::make()
                            ->title(count($ids) . ' dispatch(es) removed from basket')
                            ->warning()->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('flight_date', 'desc')
            ->striped();
    }
}
