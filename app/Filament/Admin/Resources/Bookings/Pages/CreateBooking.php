<?php

namespace App\Filament\Admin\Resources\Bookings\Pages;

use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Filament\Admin\Resources\Bookings\Schemas\BookingWizard;
use App\Models\Booking;
use App\Models\Product;
use App\Notifications\PartnerBookingNotification;
use App\Services\BookingService;
use App\Settings\AppSettings;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
     * Handles both regular (BLX-YYYY-NNNN) and partner (PBX-YYYY-NNNN) bookings.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var Product $product */
        $product = Product::findOrFail($data['product_id']);

        $isPartner   = ($data['booking_type'] ?? 'regular') === 'partner';
        $partnerId   = $isPartner ? ((int) ($data['partner_id'] ?? 0) ?: null) : null;
        $adultPax    = (int) ($data['adult_pax'] ?? 1);
        $childPax    = (int) ($data['child_pax'] ?? 0);
        $discount    = (float) ($data['discount_amount'] ?? 0);
        $amountPaid  = (float) ($data['amount_paid'] ?? 0);

        // Resolve prices — partner pivot if partner booking, else base product price
        $service       = app(BookingService::class);
        $pricing       = $service->calculatePricing($product, $adultPax, $childPax, $discount, $partnerId);
        $balanceDue    = max(0, round($pricing['final_amount'] - $amountPaid, 2));

        $data['base_adult_price'] = $pricing['base_adult_price'];
        $data['base_child_price'] = $pricing['base_child_price'];
        $data['adult_total']      = $pricing['adult_total'];
        $data['child_total']      = $pricing['child_total'];
        $data['final_amount']     = $pricing['final_amount'];
        $data['balance_due']      = $balanceDue;
        $data['created_by']       = Auth::id();
        $data['type']             = $isPartner ? 'partner' : 'regular';
        $data['partner_id']       = $partnerId;
        $data['booking_ref']      = $service->generateRef($isPartner ? 'PBX' : 'BLX');

        // ── Null-safe defaults for all NOT NULL / non-nullable columns ─────────
        // Prevents SQL constraint violations when wizard fields are left blank.
        $data['discount_amount']  = $discount;                       // decimal NOT NULL default 0
        $data['amount_paid']      = $amountPaid;                     // decimal NOT NULL default 0
        $data['adult_pax']        = $adultPax;                       // int NOT NULL
        $data['child_pax']        = $childPax;                       // int NOT NULL
        $data['flight_time']      = $data['flight_time'] ?: null;    // nullable time
        $data['booking_source']   = $data['booking_source'] ?: null; // nullable string
        $data['discount_reason']  = $data['discount_reason'] ?? null; // nullable string
        $data['notes']            = $data['notes'] ?? null;          // nullable text
        $data['cancelled_reason'] = $data['cancelled_reason'] ?? null; // nullable text

        // Remove wizard-only fields not stored on the booking
        unset($data['booking_type']);

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

        // ── Partner booking: alert admin email ──────────────────────────────
        if ($booking->type === 'partner') {
            $adminEmail = app(AppSettings::class)->company_email;

            if ($adminEmail) {
                $booking->loadMissing(['partner', 'product']);

                try {
                    (new AnonymousNotifiable)
                        ->route('mail', $adminEmail)
                        ->notify(new PartnerBookingNotification($booking));
                } catch (\Exception $e) {
                    Log::error("CreateBooking: failed to send partner booking alert [{$booking->booking_ref}]: " . $e->getMessage());
                }
            }
        }
    }
}
