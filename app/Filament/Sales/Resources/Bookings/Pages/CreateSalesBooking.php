<?php

namespace App\Filament\Sales\Resources\Bookings\Pages;

use App\Filament\Sales\Resources\Bookings\SalesBookingResource;
use App\Models\Product;
use App\Services\BookingService;
use App\Settings\AppSettings;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class CreateSalesBooking extends CreateRecord
{
    protected static string $resource = SalesBookingResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([

                // ─── Step 1: Flight Details ──────────────────────────────────
                Step::make('Flight Details')
                    ->description('Choose the experience, date, and passenger count.')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Grid::make(2)->components([

                            Select::make('product_id')
                                ->label('Experience / Product')
                                ->options(
                                    fn () => Product::where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                )
                                ->required()
                                ->native(false)
                                ->live()
                                ->columnSpan(2)
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $product = Product::find($get('product_id'));
                                    if ($product) {
                                        $set('base_adult_price', (float) $product->base_adult_price);
                                        $set('base_child_price', (float) $product->base_child_price);
                                    }
                                }),

                            DatePicker::make('flight_date')
                                ->label('Flight Date')
                                ->required()
                                ->native(false)
                                ->minDate(now())
                                ->live()
                                ->hint(function ($state) {
                                    if (! $state) return '';
                                    $available = app(BookingService::class)->getAvailablePax(Carbon::parse($state));
                                    return "Available PAX on this date: {$available}";
                                }),

                            TimePicker::make('flight_time')
                                ->label('Preferred Flight Time')
                                ->nullable()
                                ->native(false)
                                ->seconds(false),

                            TextInput::make('adult_pax')
                                ->label('Number Of Adults')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(1)
                                ->live(),

                            TextInput::make('child_pax')
                                ->label('Number Of Children')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->minValue(0)
                                ->live(),

                            TextInput::make('partner_reference')
                                ->label('External / Partner Reference')
                                ->nullable()
                                ->maxLength(100)
                                ->placeholder('Enter booking reference (optional)…')
                                ->columnSpan(2),

                            Grid::make(3)->schema([
                                TextInput::make('pickup_location')
                                    ->label('Pick-up Location')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('pickup_map_link')
                                    ->label('Pick-up Map Link')
                                    ->url()
                                    ->nullable()
                                    ->maxLength(2000)
                                    ->placeholder('https://maps.google.com/…'),

                                TextInput::make('dropoff_location')
                                    ->label('Drop-off Location (optional)')
                                    ->nullable()
                                    ->maxLength(255),
                            ])->columnSpanFull(),
                        ]),
                    ]),

                // ─── Step 2: Passengers ──────────────────────────────────────
                Step::make('Passengers')
                    ->description('Enter one row per passenger.')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Placeholder::make('pax_guide')
                            ->label('')
                            ->content(fn ($get): string => sprintf(
                                'Please add %d passenger record(s): %d adult(s) + %d child(ren).',
                                (int) $get('adult_pax') + (int) $get('child_pax'),
                                (int) $get('adult_pax'),
                                (int) $get('child_pax'),
                            )),

                        Repeater::make('booking_customers')
                            ->label('Passengers')
                            ->schema([
                                Grid::make(3)->components([
                                    TextInput::make('full_name')->label('Full Name')->required()->maxLength(255),
                                    Select::make('type')
                                        ->label('Type')
                                        ->options(['adult' => 'Adult', 'child' => 'Child'])
                                        ->default('adult')->required()->native(false),
                                    Toggle::make('is_primary')->label('Primary Contact')->default(false)->inline(false)->live(),
                                    TextInput::make('email')->label('Email')->email()->nullable()->maxLength(255),
                                    TextInput::make('phone')->label('Phone')->tel()
                                        ->required(fn ($get): bool => (bool) $get('is_primary'))->maxLength(50)
                                        ->placeholder('+212669611393 | Country Code | Number'),
                                    TextInput::make('nationality')->label('Nationality')->nullable()->maxLength(100),
                                    TextInput::make('passport_number')->label('Passport No.')->nullable()->maxLength(100),
                                    DatePicker::make('date_of_birth')->label('Date of Birth')->nullable()->native(false),
                                    TextInput::make('weight_kg')->label('Weight (kg)')->numeric()->nullable()->suffix('kg'),
                                ]),
                            ])
                            ->minItems(1)
                            ->collapsible()
                            ->columnSpanFull(),
                    ]),

                // ─── Step 3: Review & Submit ─────────────────────────────────
                Step::make('Review & Submit')
                    ->description('Review defaults, customize pricing, and submit.')
                    ->icon('heroicon-o-check-circle')
                    ->schema([
                        Section::make('Pricing Configuration')
                            ->components([
                                Grid::make(2)->components([
                                    Placeholder::make('pax_reminder')
                                        ->label('Selected Passengers')
                                        ->content(function (Get $get) {
                                            $adultPax = (int) ($get('adult_pax') ?? 1);
                                            $childPax = (int) ($get('child_pax') ?? 0);
                                            return new HtmlString(
                                                "<strong>{$adultPax} Adult(s)</strong>, <strong>{$childPax} Child(ren)</strong>"
                                            );
                                        })
                                        ->columnSpanFull(),

                                    Placeholder::make('system_default_pricing')
                                        ->label('System Default Pricing')
                                        ->content(function (Get $get) {
                                            $productId = $get('product_id');
                                            $adultPax  = (int) ($get('adult_pax') ?? 1);
                                            $childPax  = (int) ($get('child_pax') ?? 0);
                                            if (!$productId) return 'Please select a product in Step 1.';
                                            $product = Product::find($productId);
                                            if (!$product) return '—';

                                            $defAdult = (float) $product->base_adult_price;
                                            $defChild = (float) $product->base_child_price;
                                            $defTotal = ($defAdult * $adultPax) + ($defChild * $childPax);

                                            $currency = app(AppSettings::class)->getIsoCurrency();
                                            return "Adult: {$currency} " . number_format($defAdult, 2) . " | Child: {$currency} " . number_format($defChild, 2) . " | Calculated Subtotal: {$currency} " . number_format($defTotal, 2);
                                        })
                                        ->columnSpanFull(),

                                    TextInput::make('base_adult_price')
                                        ->label('Custom Adult Unit Price')
                                        ->numeric()
                                        ->required(fn (Get $get) => (int) $get('adult_pax') > 0)
                                        ->disabled(fn (Get $get) => (int) $get('adult_pax') === 0)
                                        ->live()
                                        ->prefix(fn() => app(AppSettings::class)->getIsoCurrency()),

                                    TextInput::make('base_child_price')
                                        ->label('Custom Child Unit Price')
                                        ->numeric()
                                        ->required(fn (Get $get) => (int) $get('child_pax') > 0)
                                        ->disabled(fn (Get $get) => (int) $get('child_pax') === 0)
                                        ->live()
                                        ->prefix(fn() => app(AppSettings::class)->getIsoCurrency()),

                                    TextInput::make('discount_amount')
                                        ->label('Discount Amount')
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->live()
                                        ->prefix(fn() => app(AppSettings::class)->getIsoCurrency()),

                                    TextInput::make('discount_reason')
                                        ->label('Discount Reason')
                                        ->required(fn (Get $get) => (float) $get('discount_amount') > 0)
                                        ->maxLength(255)
                                        ->placeholder('Enter reason (mandatory for discounts)')
                                        ->hint('Mandatory if discount is configured'),

                                    Placeholder::make('sales_final_amount_placeholder')
                                        ->label('Sales Final Total')
                                        ->content(function (Get $get) {
                                            $adultPrice = (float) ($get('base_adult_price') ?? 0);
                                            $childPrice = (float) ($get('base_child_price') ?? 0);
                                            $adultPax   = (int) ($get('adult_pax') ?? 1);
                                            $childPax   = (int) ($get('child_pax') ?? 0);
                                            $discount   = (float) ($get('discount_amount') ?? 0);

                                            $total = ($adultPrice * $adultPax) + ($childPrice * $childPax) - $discount;
                                            $currency = app(AppSettings::class)->getIsoCurrency();

                                            return new HtmlString(
                                                '<span class="font-bold text-lg text-primary-600">' . $currency . ' ' . number_format(max(0, $total), 2) . '</span>'
                                            );
                                        })
                                        ->columnSpanFull(),
                                ]),
                            ]),

                        Textarea::make('notes')
                            ->label('Internal Notes / Requests')
                            ->rows(3)
                            ->nullable()
                            ->columnSpanFull(),
                    ]),

            ])->columnSpanFull(),
        ]);
    }

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();

        $adultPax = (int) ($data['adult_pax'] ?? 0);
        $childPax = (int) ($data['child_pax'] ?? 0);
        $totalPax = $adultPax + $childPax;

        if ($totalPax < 1) {
            Notification::make()
                ->title('Passenger Count Error')
                ->body('Please add at least 1 passenger.')
                ->danger()
                ->send();
            $this->halt();
        }

        if (!empty($data['flight_date'])) {
            $service   = app(BookingService::class);
            $available = $service->getAvailablePax(Carbon::parse($data['flight_date']));

            if ($totalPax > $available) {
                Notification::make()
                    ->title('Insufficient PAX Capacity')
                    ->body("Only {$available} seats remaining on " . Carbon::parse($data['flight_date'])->format('d/m/Y') . '.')
                    ->danger()
                    ->persistent()
                    ->send();
                $this->halt();
            }
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $adultPax    = (int) ($data['adult_pax'] ?? 1);
        $childPax    = (int) ($data['child_pax'] ?? 0);
        $adultPrice  = (float) ($data['base_adult_price'] ?? 0);
        $childPrice  = (float) ($data['base_child_price'] ?? 0);
        $discount    = (float) ($data['discount_amount'] ?? 0);

        $adultTotal  = round($adultPrice * $adultPax, 2);
        $childTotal  = round($childPrice * $childPax, 2);
        $finalAmount = max(0, round($adultTotal + $childTotal - $discount, 2));

        $service = app(BookingService::class);
        $ref     = $service->generateRef('BLX'); // Sales portal creates regular (BLX) bookings

        return array_merge($data, [
            'type'             => 'regular',
            'partner_id'       => null,
            'booking_ref'      => $ref,
            'base_adult_price' => $adultPrice,
            'base_child_price' => $childPrice,
            'adult_total'      => $adultTotal,
            'child_total'      => $childTotal,
            'final_amount'     => $finalAmount,
            'payment_method'   => 'cash', // Default regular payment method
            'payment_status'   => 'due',
            'amount_paid'      => 0,
            'balance_due'      => $finalAmount,
            'booking_status'   => 'pending',
            'created_by'       => Auth::id(),
            'discount_amount'  => $discount,
            'discount_reason'  => $data['discount_reason'] ?? null,
            'notes'            => $data['notes'] ?? null,
        ]);
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return app(BookingService::class)->createBooking($data);
    }

    protected function afterCreate(): void
    {
        $booking = $this->getRecord();

        Notification::make()
            ->title('Booking Created')
            ->body("Booking reference **{$booking->booking_ref}** has been successfully created.")
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', panel: 'sales');
    }
}
