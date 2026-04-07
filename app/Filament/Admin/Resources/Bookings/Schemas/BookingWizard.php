<?php

namespace App\Filament\Admin\Resources\Bookings\Schemas;

use App\Models\Partner;
use App\Models\Product;
use App\Services\BookingService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class BookingWizard
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([

                // ─── Step 1: Flight Details ───────────────────────────────
                Step::make('Flight Details')
                    ->description('Choose the booking type, product, date, and passenger count.')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([

                        // ── Booking Type Toggle ──────────────────────────
                        Radio::make('booking_type')
                            ->label('Booking Type')
                            ->options([
                                'regular' => '✈️  Regular Booking',
                                'partner' => '🤝  Partner Booking',
                            ])
                            ->default('regular')
                            ->required()
                            ->live()
                            ->columnSpanFull(),

                        // ── Partner Select (partner bookings only) ───────
                        Select::make('partner_id')
                            ->label('Partner')
                            ->options(fn () => Partner::where('is_active', true)
                                ->where('status', 'approved')
                                ->orderBy('company_name')
                                ->pluck('company_name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->live()
                            ->required(fn (Get $get): bool => $get('booking_type') === 'partner')
                            ->visible(fn (Get $get): bool => $get('booking_type') === 'partner')
                            ->placeholder('Search for a partner…')
                            ->columnSpanFull(),

                        Grid::make(2)->components([

                            // ── Product Select — filters to partner when partner type ──
                            Select::make('product_id')
                                ->label('Product / Experience')
                                ->options(function (Get $get) {
                                    $type      = $get('booking_type');
                                    $partnerId = $get('partner_id');

                                    if ($type === 'partner' && $partnerId) {
                                        // Only products assigned to this partner (via pivot)
                                        return Product::where('is_active', true)
                                            ->whereHas('partners', fn ($q) => $q->where('partners.id', $partnerId))
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    }

                                    // Default: all active products
                                    return Product::where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                })
                                ->required()
                                ->native(false)
                                ->live()
                                ->columnSpan(2),

                            DatePicker::make('flight_date')
                                ->label('Flight Date')
                                ->required()
                                ->native(false)
                                ->minDate(now())
                                ->live()
                                ->afterStateUpdated(fn () => null) // triggers re-render for PAX info
                                ->hint(fn (Get $get): string => self::paxHint($get)),

                            TimePicker::make('flight_time')
                                ->label('Flight Time')
                                ->nullable()
                                ->native(false)
                                ->seconds(false),

                            TextInput::make('adult_pax')
                                ->label('Adult Passengers')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(1)
                                ->live(),

                            TextInput::make('child_pax')
                                ->label('Child Passengers')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->minValue(0)
                                ->live(),

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

                // ─── Step 2: Customer Details ─────────────────────────────
                Step::make('Customer Details')
                    ->description('Enter one record per passenger.')
                    ->icon('heroicon-o-users')
                    ->schema([

                        Placeholder::make('pax_guide')
                            ->label('')
                            ->content(fn (Get $get): string => sprintf(
                                'Add %d customer record(s): %d adult(s) + %d child(ren).',
                                (int) $get('adult_pax') + (int) $get('child_pax'),
                                (int) $get('adult_pax'),
                                (int) $get('child_pax'),
                            )),

                        Repeater::make('booking_customers')
                            ->label('Passengers')
                            ->schema([
                                Grid::make(3)->components([

                                    TextInput::make('full_name')
                                        ->label('Full Name')
                                        ->required()
                                        ->maxLength(255),

                                    Select::make('type')
                                        ->label('Type')
                                        ->options(['adult' => 'Adult', 'child' => 'Child'])
                                        ->default('adult')
                                        ->required()
                                        ->native(false),

                                    Toggle::make('is_primary')
                                        ->label('Primary Contact')
                                        ->default(false)
                                        ->inline(false),

                                    TextInput::make('email')
                                        ->label('Email')
                                        ->email()
                                        ->nullable()
                                        ->maxLength(255),

                                    TextInput::make('phone')
                                        ->label('Phone')
                                        ->tel()
                                        ->nullable()
                                        ->maxLength(50),

                                    TextInput::make('nationality')
                                        ->label('Nationality')
                                        ->nullable()
                                        ->maxLength(100),

                                    TextInput::make('passport_number')
                                        ->label('Passport No. (optional)')
                                        ->nullable()
                                        ->maxLength(100),

                                    DatePicker::make('date_of_birth')
                                        ->label('Date of Birth (optional)')
                                        ->nullable()
                                        ->native(false),

                                    TextInput::make('weight_kg')
                                        ->label('Weight kg (optional)')
                                        ->numeric()
                                        ->suffix('kg')
                                        ->nullable(),
                                ]),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Add Passenger')
                            ->reorderable(false)
                            ->columnSpanFull()
                            ->itemLabel(fn (array $state): ?string => $state['full_name'] ?? null),
                    ]),

                // ─── Step 3: Pricing & Discounts ──────────────────────────
                Step::make('Pricing & Discounts')
                    ->description('Review auto-calculated totals and apply any discount.')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Grid::make(2)->components([

                            Placeholder::make('adult_total_display')
                                ->label('Adult Total')
                                ->content(fn (Get $get): string => self::formatCurrency(
                                    self::calcAdultTotal($get)
                                )),

                            Placeholder::make('child_total_display')
                                ->label('Child Total')
                                ->content(fn (Get $get): string => self::formatCurrency(
                                    self::calcChildTotal($get)
                                )),

                            // Partner price info badge
                            Placeholder::make('partner_price_info')
                                ->label('Price Source')
                                ->content(fn (Get $get): string => self::priceSourceInfo($get))
                                ->visible(fn (Get $get): bool => $get('booking_type') === 'partner')
                                ->columnSpan(2),

                            TextInput::make('discount_amount')
                                ->label('Discount Amount (MAD)')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->live()
                                ->prefix('MAD'),

                            TextInput::make('discount_reason')
                                ->label('Discount Reason')
                                ->nullable()
                                ->maxLength(255),

                            Placeholder::make('final_amount_display')
                                ->label('Final Amount')
                                ->content(fn (Get $get): string => self::formatCurrency(
                                    self::calcFinalAmount($get)
                                ))
                                ->columnSpan(2),
                        ]),
                    ]),

                // ─── Step 4: Payment ──────────────────────────────────────
                Step::make('Payment')
                    ->description('Record payment method and amount received.')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Grid::make(2)->components([

                            Select::make('payment_method')
                                ->label('Payment Method')
                                ->options([
                                    'cash'   => 'Cash',
                                    'wire'   => 'Wire Transfer',
                                    'online' => 'Online',
                                ])
                                ->default('cash')
                                ->required()
                                ->native(false),

                            Select::make('payment_status')
                                ->label('Payment Status')
                                ->options([
                                    'due'     => 'Due (Nothing Received)',
                                    'partial' => 'Partial (Deposit Paid)',
                                    'paid'    => 'Paid in Full',
                                    'on_site' => 'Pay On-Site',
                                ])
                                ->default('due')
                                ->required()
                                ->native(false)
                                ->live(),

                            TextInput::make('amount_paid')
                                ->label('Amount Paid (MAD)')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->prefix('MAD')
                                ->live(),

                            Placeholder::make('balance_due_display')
                                ->label('Balance Due')
                                ->content(fn (Get $get): string => self::formatCurrency(
                                    max(0, self::calcFinalAmount($get) - (float) ($get('amount_paid') ?? 0))
                                )),
                        ]),
                    ]),

                // ─── Step 5: Review & Confirm ─────────────────────────────
                Step::make('Review & Confirm')
                    ->description('Review all details before creating the booking.')
                    ->icon('heroicon-o-check-circle')
                    ->schema([
                        Section::make('Booking Summary')
                            ->components([
                                Grid::make(3)->components([

                                    Placeholder::make('review_type')
                                        ->label('Booking Type')
                                        ->content(fn (Get $get): string =>
                                            $get('booking_type') === 'partner' ? '🤝 Partner Booking' : '✈️ Regular Booking'
                                        ),

                                    Placeholder::make('review_partner')
                                        ->label('Partner')
                                        ->content(fn (Get $get): string =>
                                            $get('booking_type') === 'partner'
                                                ? (Partner::find($get('partner_id'))?->company_name ?? '—')
                                                : '—'
                                        ),

                                    Placeholder::make('review_product')
                                        ->label('Product')
                                        ->content(fn (Get $get): string =>
                                            Product::find($get('product_id'))?->name ?? '—'
                                        ),

                                    Placeholder::make('review_flight_date')
                                        ->label('Flight Date')
                                        ->content(fn (Get $get): string =>
                                            $get('flight_date')
                                                ? Carbon::parse($get('flight_date'))->format('d/m/Y')
                                                : '—'
                                        ),

                                    Placeholder::make('review_pax')
                                        ->label('Total PAX')
                                        ->content(fn (Get $get): string =>
                                            (int) $get('adult_pax') + (int) $get('child_pax')
                                            . ' (' . $get('adult_pax') . ' adults + ' . $get('child_pax') . ' children)'
                                        ),

                                    Placeholder::make('review_source')
                                        ->label('Booking Source')
                                        ->content(fn (Get $get): string => ucfirst($get('booking_source') ?? '—')),

                                    Placeholder::make('review_payment_method')
                                        ->label('Payment Method')
                                        ->content(fn (Get $get): string => ucfirst($get('payment_method') ?? '—')),

                                    Placeholder::make('review_final')
                                        ->label('Final Amount')
                                        ->content(fn (Get $get): string => self::formatCurrency(
                                            self::calcFinalAmount($get)
                                        )),
                                ]),
                            ]),

                        Textarea::make('notes')
                            ->label('Internal Notes')
                            ->rows(3)
                            ->nullable()
                            ->placeholder('Any additional notes about this booking...')
                            ->columnSpanFull(),
                    ]),

            ])->columnSpanFull(),
        ]);
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Resolve the adult unit price for the currently selected product/partner combo.
     */
    private static function resolveAdultPrice(Get $get): float
    {
        $product   = Product::find($get('product_id'));
        $partnerId = $get('booking_type') === 'partner' ? (int) $get('partner_id') : null;

        if (!$product) {
            return 0.0;
        }

        if ($partnerId) {
            $pivot = DB::table('partner_products')
                ->where('partner_id', $partnerId)
                ->where('product_id', $product->id)
                ->first();

            if ($pivot) {
                return (float) $pivot->partner_adult_price;
            }
        }

        return (float) $product->base_adult_price;
    }

    /**
     * Resolve the child unit price for the currently selected product/partner combo.
     */
    private static function resolveChildPrice(Get $get): float
    {
        $product   = Product::find($get('product_id'));
        $partnerId = $get('booking_type') === 'partner' ? (int) $get('partner_id') : null;

        if (!$product) {
            return 0.0;
        }

        if ($partnerId) {
            $pivot = DB::table('partner_products')
                ->where('partner_id', $partnerId)
                ->where('product_id', $product->id)
                ->first();

            if ($pivot) {
                return (float) $pivot->partner_child_price;
            }
        }

        return (float) $product->base_child_price;
    }

    private static function calcAdultTotal(Get $get): float
    {
        $adultPax = (int) ($get('adult_pax') ?? 0);
        return round(self::resolveAdultPrice($get) * $adultPax, 2);
    }

    private static function calcChildTotal(Get $get): float
    {
        $childPax = (int) ($get('child_pax') ?? 0);
        return round(self::resolveChildPrice($get) * $childPax, 2);
    }

    private static function calcFinalAmount(Get $get): float
    {
        $discount = (float) ($get('discount_amount') ?? 0);
        return max(0, self::calcAdultTotal($get) + self::calcChildTotal($get) - $discount);
    }

    private static function formatCurrency(float $amount): string
    {
        return 'MAD ' . number_format($amount, 2);
    }

    private static function paxHint(Get $get): string
    {
        $date = $get('flight_date');
        if (!$date) {
            return 'Select a date to see available capacity.';
        }
        $available = app(BookingService::class)->getAvailablePax(Carbon::parse($date));
        $color     = $available < 20 ? '⚠️ ' : '';
        return "{$color}{$available} PAX available on this date.";
    }

    private static function priceSourceInfo(Get $get): string
    {
        $productId = $get('product_id');
        $partnerId = $get('partner_id');

        if (!$productId || !$partnerId) {
            return 'Select a partner and product to see pricing.';
        }

        $pivot = DB::table('partner_products')
            ->where('partner_id', $partnerId)
            ->where('product_id', $productId)
            ->first();

        if ($pivot) {
            return "✅ Partner prices applied — Adult: MAD " . number_format((float) $pivot->partner_adult_price, 2)
                . " | Child: MAD " . number_format((float) $pivot->partner_child_price, 2);
        }

        $product = Product::find($productId);
        return "⚠️ No partner-specific price found. Using base price — Adult: MAD "
            . number_format((float) ($product?->base_adult_price ?? 0), 2)
            . " | Child: MAD "
            . number_format((float) ($product?->base_child_price ?? 0), 2);
    }
}
