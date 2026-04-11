<?php

namespace App\Filament\Accountant\Resources\AccountantBookingResource\Pages;

use App\Filament\Accountant\Resources\AccountantBookingResource;
use App\Models\Booking;
use App\Models\BookingCustomer;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class ViewAccountantBooking extends ViewRecord
{
    protected static string $resource = AccountantBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('process_payment')
                ->label('Process Payment')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->form([
                    Select::make('payment_status')
                        ->label('Payment Status')
                        ->options([
                            'due'     => 'Due',
                            'partial' => 'Partial',
                            'paid'    => 'Paid',
                            'on_site' => 'On Site',
                        ])
                        ->required(),
                    Select::make('payment_method')
                        ->label('Payment Method')
                        ->options([
                            'cash'   => 'Cash',
                            'online' => 'Online',
                            'wire'   => 'Wire Transfer',
                        ])
                        ->required(),
                    TextInput::make('amount_paid')
                        ->label('Amount Paid (MAD)')
                        ->numeric()
                        ->prefix('MAD')
                        ->required()
                        ->rules(['min:0']),
                ])
                ->fillForm(fn (): array => [
                    'payment_status' => $this->record->payment_status,
                    'payment_method' => $this->record->payment_method,
                    'amount_paid'    => $this->record->amount_paid,
                ])
                ->action(function (array $data): void {
                    $record     = $this->record;
                    $balanceDue = max(0, round($record->final_amount - $data['amount_paid'], 2));
                    $record->update([
                        'payment_status' => $data['payment_status'],
                        'payment_method' => $data['payment_method'],
                        'amount_paid'    => $data['amount_paid'],
                        'balance_due'    => $balanceDue,
                    ]);
                    $this->refreshFormData(['payment_status', 'payment_method', 'amount_paid', 'balance_due']);
                    Notification::make()
                        ->title('Payment Updated')
                        ->success()
                        ->send();
                })
                ->slideOver()
                ->modalWidth('md'),
        ];
    }

    public function infolist(Schema $infolist): Schema
    {
        return $infolist->components([

            // ─── Row 1: Booking Identity ──────────────────────────────────────
            Section::make('Booking Details')
                ->columns(4)
                ->components([
                    TextEntry::make('booking_ref')
                        ->label('Reference')
                        ->badge()
                        ->color('primary')
                        ->copyable(),

                    TextEntry::make('type')
                        ->label('Booking Type')
                        ->badge()
                        ->color(fn (string $state): string => $state === 'partner' ? 'purple' : 'info')
                        ->formatStateUsing(fn (string $state): string => $state === 'partner' ? '🤝 Partner' : '🔵 Regular'),

                    TextEntry::make('booking_status')
                        ->label('Booking Status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'confirmed' => 'success',
                            'cancelled' => 'danger',
                            'completed' => 'info',
                            default     => 'warning',
                        })
                        ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                    TextEntry::make('attendance_display')
                        ->label('PAX Attendance')
                        ->getStateUsing(fn ($record) => $record->getPaxAttendanceLabel())
                        ->badge()
                        ->color('info'),
                ]),

            // ─── Row 2: Flight & Partner Info ────────────────────────────────
            Section::make('Flight & Partner Information')
                ->columns(4)
                ->components([
                    TextEntry::make('product.name')
                        ->label('Product / Tour'),

                    TextEntry::make('flight_date')
                        ->label('Flight Date')
                        ->date('d/m/Y'),

                    TextEntry::make('flight_time')
                        ->label('Flight Time')
                        ->time('H:i')
                        ->placeholder('—'),

                    TextEntry::make('partner_info')
                        ->label('Partner')
                        ->getStateUsing(fn ($record): string => $record->type === 'partner' && $record->partner
                            ? ($record->partner->company_name ?? $record->partner->name ?? 'Partner')
                            : '🔵 Regular Booking'),
                ]),

            // ─── Row 3: PAX Summary ───────────────────────────────────────────
            Section::make('Passenger Summary')
                ->columns(4)
                ->components([
                    TextEntry::make('adult_pax')
                        ->label('Adults')
                        ->badge()
                        ->color('info')
                        ->formatStateUsing(fn ($state): string => $state . ' Adult(s)'),

                    TextEntry::make('child_pax')
                        ->label('Children')
                        ->badge()
                        ->color('warning')
                        ->formatStateUsing(fn ($state): string => $state . ' Child(ren)'),

                    TextEntry::make('total_pax')
                        ->label('Total PAX')
                        ->getStateUsing(fn ($record): int => $record->getTotalPax())
                        ->badge()
                        ->color('gray'),

                    TextEntry::make('booking_source')
                        ->label('Booking Source')
                        ->badge()
                        ->color('gray')
                        ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : '—')
                        ->placeholder('—'),
                ]),

            // ─── Row 4: Financial Summary ──────────────────────────────────────
            Section::make('Financial Summary')
                ->columns(4)
                ->components([
                    TextEntry::make('final_amount')
                        ->label('Total Amount Due')
                        ->money('MAD')
                        ->weight('bold')
                        ->color('gray'),

                    TextEntry::make('amount_paid')
                        ->label('Amount Paid')
                        ->money('MAD')
                        ->weight('bold')
                        ->color('success'),

                    TextEntry::make('balance_due')
                        ->label('Balance Due')
                        ->money('MAD')
                        ->weight('bold')
                        ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),

                    TextEntry::make('payment_status')
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
                ]),

            Section::make('Pricing Breakdown')
                ->columns(4)
                ->collapsed()
                ->components([
                    TextEntry::make('base_adult_price')
                        ->label('Adult Unit Price')
                        ->money('MAD'),

                    TextEntry::make('adult_total')
                        ->label('Adult Subtotal')
                        ->money('MAD'),

                    TextEntry::make('base_child_price')
                        ->label('Child Unit Price')
                        ->money('MAD'),

                    TextEntry::make('child_total')
                        ->label('Child Subtotal')
                        ->money('MAD'),

                    TextEntry::make('discount_amount')
                        ->label('Discount')
                        ->money('MAD')
                        ->color('warning'),

                    TextEntry::make('discount_reason')
                        ->label('Discount Reason')
                        ->placeholder('—'),

                    TextEntry::make('payment_method')
                        ->label('Payment Method')
                        ->badge()
                        ->color('gray')
                        ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                    TextEntry::make('notes')
                        ->label('Notes')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),

            // ─── Row 5: Passenger Attendance Table ────────────────────────────
            Section::make('Passenger List & Attendance')
                ->components([
                    RepeatableEntry::make('customers')
                        ->label('')
                        ->columns(5)
                        ->contained(false)
                        ->schema([
                            TextEntry::make('full_name')
                                ->label('Name')
                                ->weight('bold'),

                            TextEntry::make('type')
                                ->label('Type')
                                ->badge()
                                ->color(fn (string $state): string => $state === 'adult' ? 'info' : 'warning')
                                ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                            TextEntry::make('phone')
                                ->label('Phone')
                                ->placeholder('—')
                                ->copyable(),

                            TextEntry::make('nationality')
                                ->label('Nationality')
                                ->placeholder('—'),

                            TextEntry::make('attendance')
                                ->label('Attendance')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'show'    => 'success',
                                    'no_show' => 'danger',
                                    default   => 'gray',
                                })
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'show'    => '✅ Show',
                                    'no_show' => '❌ No-Show',
                                    default   => '⏳ Pending',
                                }),
                        ]),
                ]),
        ]);
    }
}

