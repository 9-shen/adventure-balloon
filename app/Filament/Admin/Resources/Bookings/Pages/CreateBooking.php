<?php

namespace App\Filament\Admin\Resources\Bookings\Pages;

use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Filament\Admin\Resources\Bookings\Schemas\BookingWizard;
use App\Models\Booking;
use App\Models\Product;
use App\Services\BookingService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    /**
     * Override the form with our 5-step Wizard.
     */
    public function form(Schema $form): Schema
    {
        return BookingWizard::configure($form);
    }

    /**
     * PRE-VALIDATE: check PAX availability before the DB insert.
     */
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
            $available = $service->getAvailablePax(\Carbon\Carbon::parse($data['flight_date']));

            if ($totalPax > $available) {
                Notification::make()
                    ->title('Insufficient PAX Capacity')
                    ->body("Only {$available} seats remaining on " . \Carbon\Carbon::parse($data['flight_date'])->format('d/m/Y') . '.')
                    ->danger()
                    ->persistent()
                    ->send();
                $this->halt();
            }
        }
    }

    /**
     * Compute all calculated fields and generate booking reference.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var Product $product */
        $product = Product::findOrFail($data['product_id']);

        $adultPax    = (int) ($data['adult_pax'] ?? 0);
        $childPax    = (int) ($data['child_pax'] ?? 0);
        $discount    = (float) ($data['discount_amount'] ?? 0);
        $amountPaid  = (float) ($data['amount_paid'] ?? 0);

        $adultTotal  = round((float) $product->base_adult_price * $adultPax, 2);
        $childTotal  = round((float) $product->base_child_price * $childPax, 2);
        $finalAmount = max(0, round($adultTotal + $childTotal - $discount, 2));
        $balanceDue  = max(0, round($finalAmount - $amountPaid, 2));

        $data['base_adult_price'] = $product->base_adult_price;
        $data['base_child_price'] = $product->base_child_price;
        $data['adult_total']      = $adultTotal;
        $data['child_total']      = $childTotal;
        $data['final_amount']     = $finalAmount;
        $data['balance_due']      = $balanceDue;
        $data['created_by']       = Auth::id();
        $data['type']             = 'regular';
        $data['booking_ref']      = app(BookingService::class)->generateRef();

        return $data;
    }

    /**
     * Transaction: create booking + all booking_customers rows.
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(BookingService::class)->createBooking($data);
    }

    /**
     * After creation: send a success notification with booking ref.
     */
    protected function getCreatedNotificationTitle(): ?string
    {
        return null; // we send our own below
    }

    protected function afterCreate(): void
    {
        /** @var Booking $booking */
        $booking = $this->getRecord();

        Notification::make()
            ->title('Booking Created Successfully')
            ->body("Booking reference: **{$booking->booking_ref}**")
            ->success()
            ->send();
    }
}
