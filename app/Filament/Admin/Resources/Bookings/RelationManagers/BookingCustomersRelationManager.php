<?php

namespace App\Filament\Admin\Resources\Bookings\RelationManagers;

use App\Models\Booking;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingCustomersRelationManager extends RelationManager
{
    protected static string $relationship = 'customers';

    protected static ?string $title = 'Passengers';

    protected static ?string $recordTitleAttribute = 'full_name';

    // ─── Sync pax counts & pricing back to the booking ───────────────────────

    /**
     * Recalculate adult_pax, child_pax, totals, and final_amount on the parent booking.
     */
    private function syncBookingPax(): void
    {
        /** @var Booking $booking */
        $booking = $this->getOwnerRecord();
        $booking->refresh();

        $adultPax = $booking->customers()->where('type', 'adult')->count();
        $childPax = $booking->customers()->where('type', 'child')->count();

        $adultPrice  = (float) $booking->base_adult_price;
        $childPrice  = (float) $booking->base_child_price;
        $discount    = (float) ($booking->discount_amount ?? 0);

        $adultTotal  = round($adultPrice * $adultPax, 2);
        $childTotal  = round($childPrice * $childPax, 2);
        $finalAmount = max(0, round($adultTotal + $childTotal - $discount, 2));
        $balanceDue  = max(0, round($finalAmount - (float) $booking->amount_paid, 2));

        $booking->update([
            'adult_pax'    => $adultPax,
            'child_pax'    => $childPax,
            'adult_total'  => $adultTotal,
            'child_total'  => $childTotal,
            'final_amount' => $finalAmount,
            'balance_due'  => $balanceDue,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form->components([
            Select::make('type')
                ->label('Type')
                ->options(['adult' => 'Adult', 'child' => 'Child'])
                ->default('adult')
                ->required()
                ->native(false),

            TextInput::make('full_name')
                ->label('Full Name')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->email()
                ->nullable(),

            TextInput::make('phone')
                ->tel()
                ->nullable()
                ->placeholder('+212669611393 | Country Code | Number'),

            TextInput::make('nationality')
                ->nullable(),

            TextInput::make('passport_number')
                ->label('Passport No.')
                ->nullable(),

            DatePicker::make('date_of_birth')
                ->label('Date of Birth')
                ->nullable()
                ->native(false),

            TextInput::make('weight_kg')
                ->label('Weight (kg)')
                ->numeric()
                ->suffix('kg')
                ->nullable(),

            Toggle::make('is_primary')
                ->label('Primary Contact')
                ->default(false)
                ->inline(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'adult' ? 'info' : 'warning')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('email')
                    ->label('Email')
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->toggleable(),

                TextColumn::make('nationality')
                    ->label('Nationality')
                    ->toggleable(),

                TextColumn::make('weight_kg')
                    ->label('Weight')
                    ->suffix(' kg')
                    ->toggleable(),

                IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(fn () => $this->syncBookingPax()),
                DeleteAction::make()
                    ->after(fn () => $this->syncBookingPax()),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label('Add Passenger')
                    ->after(fn () => $this->syncBookingPax()),
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(fn () => $this->syncBookingPax()),
                ]),
            ]);
    }
}
