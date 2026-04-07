<?php

namespace App\Filament\Admin\Resources\Bookings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingEditForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

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
                            ->native(false),
                    ]),
                ]),

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
        ]);
    }
}
