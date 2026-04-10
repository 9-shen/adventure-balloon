<?php

namespace App\Filament\Partner\Resources\Bookings\Pages;

use App\Filament\Partner\Resources\Bookings\PartnerBookingResource;
use App\Models\Product;
use App\Notifications\PartnerBookingNotification;
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
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreatePartnerBooking extends CreateRecord
{
    protected static string $resource = PartnerBookingResource::class;

    public function form(Schema $schema): Schema
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $partnerId = $user->partner_id;

        return $schema->components([
            Wizard::make([

                // ─── Step 1: Flight Details ──────────────────────────────────────
                Step::make('Flight Details')
                    ->description('Choose the experience, date, and passenger count.')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Grid::make(2)->components([

                            Select::make('product_id')
                                ->label('Experience / Product')
                                ->options(fn () =>
                                    Product::where('is_active', true)
                                        ->whereHas('partners', fn ($q) =>
                                            $q->where('partners.id', $partnerId)
                                        )
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                )
                                ->required()
                                ->native(false)
                                ->live()
                                ->columnSpan(2)
                                ->hint('Only products available to your agency are listed.'),

                            DatePicker::make('flight_date')
                                ->label('Flight Date')
                                ->required()
                                ->native(false)
                                ->minDate(now())
                                ->live()
                                ->hint(function ($state) {
                                    if (!$state) return '';
                                    $available = app(BookingService::class)
                                        ->getAvailablePax(Carbon::parse($state));
                                    return "Available PAX on this date: {$available}";
                                }),

                            TimePicker::make('flight_time')
                                ->label('Preferred Flight Time')
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
                        ]),
                    ]),

                // ─── Step 2: Passengers ──────────────────────────────────────────
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
                                        ->label('Passport No.')
                                        ->nullable()
                                        ->maxLength(100),

                                    DatePicker::make('date_of_birth')
                                        ->label('Date of Birth')
                                        ->nullable()
                                        ->native(false),

                                    TextInput::make('weight_kg')
                                        ->label('Weight (kg)')
                                        ->numeric()
                                        ->nullable()
                                        ->suffix('kg'),
                                ]),
                            ])
                            ->minItems(1)
                            ->collapsible()
                            ->columnSpanFull(),
                    ]),

                // ─── Step 3: Review & Submit ─────────────────────────────────────
                Step::make('Review & Submit')
                    ->description('Confirm pricing and add any notes.')
                    ->icon('heroicon-o-check-circle')
                    ->schema([
                        Section::make('Pricing Summary')
                            ->components([
                                Placeholder::make('pricing_summary')
                                    ->label('Estimated Total')
                                    ->content(function ($get) use ($partnerId): string {
                                        $productId = $get('product_id');
                                        $adultPax  = (int) $get('adult_pax');
                                        $childPax  = (int) $get('child_pax');

                                        if (!$productId) return 'Select a product in Step 1.';

                                        $product = Product::find($productId);
                                        if (!$product) return '—';

                                        $pricing = app(BookingService::class)
                                            ->calculatePricing($product, $adultPax, $childPax, 0, $partnerId);

                                        return sprintf(
                                            "%d Adult(s) × %.2f MAD + %d Child(ren) × %.2f MAD = **%.2f MAD**",
                                            $adultPax, $pricing['base_adult_price'],
                                            $childPax, $pricing['base_child_price'],
                                            $pricing['final_amount'],
                                        );
                                    }),
                            ]),

                        Textarea::make('notes')
                            ->label('Special Notes / Requests')
                            ->rows(3)
                            ->nullable()
                            ->columnSpanFull(),
                    ]),

            ])->columnSpanFull(),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var \App\Models\User $user */
        $user      = Auth::user();
        $partnerId = $user->partner_id;

        $product = Product::find($data['product_id']);

        // Calculate pricing with partner rates
        $pricing = app(BookingService::class)->calculatePricing(
            product:   $product,
            adultPax:  (int) ($data['adult_pax'] ?? 0),
            childPax:  (int) ($data['child_pax'] ?? 0),
            discount:  0,
            partnerId: $partnerId,
        );

        $ref = app(BookingService::class)->generateRef('PBX');

        return array_merge($data, $pricing, [
            'type'           => 'partner',
            'partner_id'     => $partnerId,
            'booking_ref'    => $ref,
            'booking_status' => 'pending',
            'payment_status' => 'due',
            'amount_paid'    => 0,
            'balance_due'    => $pricing['final_amount'],
            'discount'       => 0,
            'created_by'     => $user->id,
        ]);
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return app(BookingService::class)->createBooking($data);
    }

    protected function afterCreate(): void
    {
        $booking = $this->getRecord();
        $booking->loadMissing(['partner', 'product']);

        // Notify admin
        $adminEmail = app(AppSettings::class)->company_email;
        if ($adminEmail) {
            try {
                (new AnonymousNotifiable)
                    ->route('mail', $adminEmail)
                    ->notify(new PartnerBookingNotification($booking));
            } catch (\Exception $e) {
                Log::error("PartnerPortal: failed to send booking alert [{$booking->booking_ref}]: " . $e->getMessage());
            }
        }

        Notification::make()
            ->title('Booking Submitted')
            ->body("Your booking **{$booking->booking_ref}** has been submitted. Our team will confirm shortly.")
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
