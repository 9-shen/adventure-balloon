<?php

namespace App\Filament\Admin\Resources\Bookings\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class BookingEditForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Grid::make(1)
                ->schema([
                    // ── Partner Info section (read-only, visible only for partner bookings) ──
                    Section::make('Partner Information')
                        ->collapsible()
                        ->visible(fn($record): bool => $record && $record->type === 'partner')
                        ->columns(2)
                        ->components([
                            Placeholder::make('partner_display')
                                ->label('Partner')
                                ->content(fn($record): string => $record?->partner?->company_name ?? '—'),

                            Placeholder::make('type_display')
                                ->label('Booking Type')
                                ->content(fn($record): HtmlString => new HtmlString(
                                    '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">🤝 Partner</span>'
                                )),
                        ]),

                    Section::make('Flight Details')
                        ->components([
                            Grid::make(2)->components([

                                DatePicker::make('flight_date')
                                    ->label('Flight Date')
                                    ->required()
                                    ->native(false),

                                TimePicker::make('flight_time')
                                    ->label('Flight Time')
                                    ->nullable()
                                    ->native(false)
                                    ->seconds(false),

                                TextInput::make('adult_pax')
                                    ->label('Adult Passengers')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1),

                                TextInput::make('child_pax')
                                    ->label('Child Passengers')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0),

                                Select::make('booking_source')
                                    ->label('Booking Source')
                                    ->options([
                                        'walk-in'  => 'Walk-In',
                                        'phone'    => 'Phone',
                                        'website'  => 'Website',
                                        'email'    => 'Email',
                                        'referral' => 'Referral',
                                        'other'    => 'Other',
                                    ])
                                    ->nullable()
                                    ->native(false)
                                    ->columnSpanFull(),
                            ]),
                        ]),

                    Section::make('Status & Notes')
                        ->components([
                            Grid::make(2)->components([
                                Select::make('booking_status')
                                    ->label('Booking Status')
                                    ->options([
                                        'pending'   => 'Pending',
                                        'confirmed' => 'Confirmed',
                                        'cancelled' => 'Cancelled',
                                        'completed' => 'Completed',
                                    ])
                                    ->required()
                                    ->native(false),

                                Select::make('attendance')
                                    ->label('Attendance')
                                    ->options([
                                        'pending' => '⏳ Pending',
                                        'show'    => '✅ Show',
                                        'no_show' => '❌ No-Show',
                                    ])
                                    ->required()
                                    ->native(false),
                            ]),

                            Textarea::make('notes')
                                ->label('Internal Notes')
                                ->rows(3)
                                ->nullable()
                                ->columnSpanFull(),

                            Textarea::make('cancelled_reason')
                                ->label('Cancellation Reason')
                                ->rows(2)
                                ->nullable()
                                ->columnSpanFull(),
                        ]),

                ]),

            Grid::make(1)
                ->schema([
                    Section::make('Payment')
                        ->components([
                            Grid::make(2)->components([

                                Select::make('payment_method')
                                    ->label('Payment Method')
                                    ->options([
                                        'cash'   => 'Cash',
                                        'wire'   => 'Wire Transfer',
                                        'online' => 'Online',
                                    ])
                                    ->required()
                                    ->native(false),

                                Select::make('payment_status')
                                    ->label('Payment Status')
                                    ->options([
                                        'due'     => 'Due',
                                        'partial' => 'Partial',
                                        'paid'    => 'Paid',
                                        'on_site' => 'On-Site',
                                    ])
                                    ->required()
                                    ->native(false),

                                TextInput::make('amount_paid')
                                    ->label('Amount Paid (MAD)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('MAD'),

                                TextInput::make('discount_amount')
                                    ->label('Discount Amount (MAD)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('MAD'),

                                TextInput::make('discount_reason')
                                    ->label('Discount Reason')
                                    ->nullable()
                                    ->columnSpan(2),
                            ]),
                        ]),
                    // ── Pricing Summary (read-only) ────────────────────────────────────────
                    Section::make('Pricing Summary')
                        ->description('Based on the saved pricing at time of booking.')
                        ->collapsible()
                        ->columns(2)
                        ->components([

                            Placeholder::make('adult_price_display')
                                ->label('Adult Price (each)')
                                ->content(fn($record): string => $record
                                    ? 'MAD ' . number_format((float) $record->base_adult_price, 2)
                                    : '—'),

                            Placeholder::make('child_price_display')
                                ->label('Child Price (each)')
                                ->content(fn($record): string => $record
                                    ? 'MAD ' . number_format((float) $record->base_child_price, 2)
                                    : '—'),

                            Placeholder::make('adult_total_display')
                                ->label('Adult Total')
                                ->content(fn($record): string => $record
                                    ? 'MAD ' . number_format((float) $record->adult_total, 2)
                                    : '—'),

                            Placeholder::make('child_total_display')
                                ->label('Child Total')
                                ->content(fn($record): string => $record
                                    ? 'MAD ' . number_format((float) $record->child_total, 2)
                                    : '—'),

                            Placeholder::make('discount_display')
                                ->label('Discount (MAD)')
                                ->content(fn($record): string => $record
                                    ? 'MAD ' . number_format((float) $record->discount_amount, 2)
                                    : '—'),

                            Placeholder::make('final_amount_display')
                                ->label('Final Amount')
                                ->content(fn($record): HtmlString => $record
                                    ? new HtmlString(
                                        '<span class="text-lg font-bold text-primary-600">MAD '
                                            . number_format((float) $record->final_amount, 2)
                                            . '</span>'
                                    )
                                    : new HtmlString('—')),
                        ]),

                ]),


        ]);
    }
}
