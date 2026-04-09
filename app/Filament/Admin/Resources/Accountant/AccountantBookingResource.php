<?php

namespace App\Filament\Admin\Resources\Accountant;

use App\Models\Booking;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Filament\Admin\Resources\Accountant\AccountantBookingResource\Pages\ListAccountantBookings;
use App\Filament\Admin\Resources\Accountant\AccountantBookingResource\Pages\ViewAccountantBooking;

class AccountantBookingResource extends Resource
{
    protected static ?string $model = Booking::class;
    
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-currency-dollar';
    }
    public static function getNavigationGroup(): ?string
    {
        return 'Accountant Module';
    }
    
    public static function getNavigationLabel(): string
    {
        return 'Finance Bookings';
    }
    
    public static function getModelLabel(): string
    {
        return 'Financial Booking';
    }
    
    public static function getPluralModelLabel(): string
    {
        return 'Finance Bookings';
    }
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
            ->columns([
                TextColumn::make('booking_ref')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('flight_date')
                    ->label('Flight Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('partner_display')
                    ->label('Partner / Type')
                    ->getStateUsing(fn (Booking $record): string => $record->type === 'partner' && $record->partner
                        ? $record->partner->company_name ?? $record->partner->name ?? 'Partner'
                        : '🔵 Regular')
                    ->searchable(query: fn (Builder $query, string $search) => $query
                        ->where('type', 'like', "%{$search}%")
                        ->orWhereHas('partner', fn ($q) => $q->where('company_name', 'like', "%{$search}%"))
                    )
                    ->sortable(),

                TextColumn::make('pax_summary')
                    ->label('PAX')
                    ->getStateUsing(fn (Booking $record) => $record->adult_pax . ' Adult(s)' . ($record->child_pax > 0 ? ', ' . $record->child_pax . ' Child(ren)' : ''))
                    ->description(fn (Booking $record) => $record->getPaxAttendanceLabel()),

                TextColumn::make('final_amount')
                    ->label('Final Amount')
                    ->money('MAD')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('amount_paid')
                    ->label('Amount Paid')
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('balance_due')
                    ->label('Balance Due')
                    ->money('MAD')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->weight('bold'),

                TextColumn::make('payment_status')
                    ->label('Payment Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'due'     => 'danger',
                        'partial' => 'warning',
                        'on_site' => 'info',
                        'paid'    => 'success',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->options([
                        'due' => 'Due',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                        'on_site' => 'On Site',
                    ]),
                SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'online' => 'Online',
                        'wire' => 'Wire Transfer',
                    ]),
                Filter::make('outstanding_balance')
                    ->label('Has Outstanding Balance')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('balance_due', '>', 0)),
            ])
            ->actions([
                Action::make('process_payment')
                    ->label('Payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->form([
                        Select::make('payment_status')
                            ->label('Payment Status')
                            ->options([
                                'due' => 'Due',
                                'partial' => 'Partial',
                                'paid' => 'Paid',
                                'on_site' => 'On Site',
                            ])
                            ->required(),
                        Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'cash' => 'Cash',
                                'online' => 'Online',
                                'wire' => 'Wire Transfer',
                            ])
                            ->required(),
                        TextInput::make('amount_paid')
                            ->label('Amount Paid (MAD)')
                            ->numeric()
                            ->prefix('MAD')
                            ->required()
                            ->rules(['min:0'])
                            ->maxValue(fn (Booking $record) => $record->final_amount),
                    ])
                    ->fillForm(fn (Booking $record): array => [
                        'payment_status' => $record->payment_status,
                        'payment_method' => $record->payment_method,
                        'amount_paid' => $record->amount_paid,
                    ])
                    ->action(function (array $data, Booking $record): void {
                        $balanceDue = max(0, round($record->final_amount - $data['amount_paid'], 2));
                        $record->update([
                            'payment_status' => $data['payment_status'],
                            'payment_method' => $data['payment_method'],
                            'amount_paid' => $data['amount_paid'],
                            'balance_due' => $balanceDue,
                        ]);
                    })
                    ->slideOver()
                    ->modalWidth('md'),

                ViewAction::make()
                    ->label('Details'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\Accountant\AccountantBookingResource\Pages\ListAccountantBookings::route('/'),
            'view' => \App\Filament\Admin\Resources\Accountant\AccountantBookingResource\Pages\ViewAccountantBooking::route('/{record}'),
        ];
    }
}
