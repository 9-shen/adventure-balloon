<?php

namespace App\Filament\Admin\Resources\Dispatches\Pages;

use App\Filament\Admin\Resources\Dispatches\DispatchResource;
use App\Filament\Admin\Resources\Dispatches\Schemas\DispatchForm;
use App\Models\Booking;
use App\Models\Dispatch;
use App\Services\DispatchService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateDispatch extends CreateRecord
{
    protected static string $resource = DispatchResource::class;

    public function form(Schema $form): Schema
    {
        return DispatchForm::configure($form);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $booking = Booking::findOrFail($data['booking_id']);
        $service = app(DispatchService::class);

        $data['dispatch_ref']  = $service->generateRef();
        $data['flight_date']   = $booking->flight_date;
        $data['total_pax']     = $booking->getTotalPax();
        $data['created_by']    = Auth::id();

        // If admin left the Repeater empty, run auto-assign
        if (empty($data['dispatch_drivers']) && !empty($data['transport_company_id'])) {
            $data['dispatch_drivers'] = $service->suggestDriverAssignments(
                $booking->getTotalPax(),
                (int) $data['transport_company_id'],
            );
        }

        // Null-safe optional fields
        $data['pickup_time']      = $data['pickup_time'] ?: null;
        $data['pickup_location']  = $data['pickup_location'] ?? null;
        $data['dropoff_location'] = $data['dropoff_location'] ?? null;
        $data['notes']            = $data['notes'] ?? null;

        // Strip display-only placeholder keys (not DB columns)
        unset($data['_booking_info_card']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(DispatchService::class)->createDispatch($data);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    protected function afterCreate(): void
    {
        /** @var Dispatch $dispatch */
        $dispatch = $this->getRecord();

        // Pre-load all relations the notification needs
        $dispatch->load([
            'transportCompany',
            'booking.product',
            'booking.customers',
            'booking.partner',
            'dispatchDriverRows.driver',
            'dispatchDriverRows.vehicle',
        ]);

        $service = app(DispatchService::class);

        // ── Auto-send transporter email ───────────────────────────────────────
        $emailSent = false;
        if ($dispatch->transportCompany?->email) {
            try {
                $service->notifyTransporter($dispatch);
                $emailSent = true;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error(
                    "CreateDispatch: failed to email transporter [{$dispatch->dispatch_ref}]: "
                    . $e->getMessage()
                );
            }
        }

        // ── UI notification ───────────────────────────────────────────────────
        $body = "Reference **{$dispatch->dispatch_ref}** created successfully.";
        if ($emailSent) {
            $body .= "\n✉️ Email sent to **{$dispatch->transportCompany->company_name}** ({$dispatch->transportCompany->email}).";
        } elseif ($dispatch->transportCompany && ! $dispatch->transportCompany->email) {
            $body .= "\n⚠️ No email address on file for the transport company — notification skipped.";
        }

        Notification::make()
            ->title('Dispatch Created')
            ->body($body)
            ->success()
            ->send();
    }
}
