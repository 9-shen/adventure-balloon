<?php

namespace App\Filament\Admin\Resources\Bookings\Pages;

use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Models\Product;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\PartnerBookingCancelledNotification;
use App\Services\BookingService;
use App\Services\DispatchService;
use App\Settings\NotificationSettings;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Confirm Action
            Action::make('confirm')
                ->label('Confirm Booking')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->getRecord()->isPending())
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var Booking $booking */
                    $booking = $this->getRecord();
                    $booking->update([
                        'booking_status' => 'confirmed',
                        'confirmed_by'   => Auth::id(),
                        'confirmed_at'   => now(),
                    ]);
                    Notification::make()
                        ->title('Booking Confirmed')
                        ->body("Booking {$booking->booking_ref} has been confirmed.")
                        ->success()
                        ->send();
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $booking]));
                }),

            // Cancel Action — Phase 26-C: sends notifications to all affected parties
            Action::make('cancel')
                ->label('Cancel Booking')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => !$this->getRecord()->isCancelled())
                ->form([
                    Textarea::make('cancelled_reason')
                        ->label('Cancellation Reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    /** @var Booking $booking */
                    $booking = $this->getRecord();
                    $reason  = $data['cancelled_reason'];

                    $booking->update([
                        'booking_status'   => 'cancelled',
                        'cancelled_reason' => $reason,
                        'cancelled_by'     => Auth::id(),
                        'cancelled_at'     => now(),
                    ]);

                    $booking->loadMissing(['partner', 'product', 'dispatch.transportCompany', 'dispatch.dispatchDriverRows.driver']);
                    $dispatch = $booking->dispatch;
                    $ns       = app(NotificationSettings::class);

                    // ── 1. Notify partner (if partner booking) ────────────────────
                    if ($ns->booking_cancelled_partner_email && $booking->type === 'partner' && $booking->partner?->email) {
                        try {
                            (new AnonymousNotifiable)
                                ->route('mail', $booking->partner->email)
                                ->notify(new PartnerBookingCancelledNotification($booking, $reason));
                        } catch (\Exception $e) {
                            Log::error("CancelBooking: failed to email partner [{$booking->booking_ref}]: " . $e->getMessage());
                        }
                    }

                    // ── 2. If dispatched — notify transport company + drivers ──────
                    if ($dispatch) {
                        // Email transport company
                        if ($ns->booking_cancelled_transport_email && $dispatch->transportCompany?->email) {
                            try {
                                $dispatch->transportCompany->notify(
                                    new BookingCancelledNotification($booking, $dispatch, $reason, false)
                                );
                            } catch (\Exception $e) {
                                Log::error("CancelBooking: failed to email transport [{$booking->booking_ref}]: " . $e->getMessage());
                            }
                        }

                        // Email each driver
                        if ($ns->booking_cancelled_driver_email) {
                            foreach ($dispatch->dispatchDriverRows as $row) {
                                if ($row->driver?->email) {
                                    try {
                                        $row->driver->notify(
                                            new BookingCancelledNotification($booking, $dispatch, $reason, true)
                                        );
                                    } catch (\Exception $e) {
                                        Log::error("CancelBooking: failed to email driver [{$row->driver_id}] [{$booking->booking_ref}]: " . $e->getMessage());
                                    }
                                }
                            }
                        }

                        // WhatsApp each driver
                        if ($ns->booking_cancelled_driver_whatsapp) {
                            try {
                                app(DispatchService::class)->sendCancellationWhatsApp($dispatch, $reason);
                            } catch (\Exception $e) {
                                Log::error("CancelBooking: WhatsApp cancellation failed [{$booking->booking_ref}]: " . $e->getMessage());
                            }
                        }
                    }

                    Notification::make()
                        ->title('Booking Cancelled')
                        ->body("Booking {$booking->booking_ref} has been cancelled. Notifications sent to affected parties.")
                        ->warning()
                        ->send();
                }),

            DeleteAction::make(),
        ];
    }

    /**
     * Recalculate all pricing + balance_due when pax counts or payment data changes.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record    = $this->getRecord();
        $adultPax  = (int) ($data['adult_pax'] ?? $record->adult_pax);
        $childPax  = (int) ($data['child_pax'] ?? $record->child_pax);
        $discount  = (float) ($data['discount_amount'] ?? $record->discount_amount ?? 0);

        // Recalculate totals only when pax changes
        $adultPaxChanged = $adultPax !== (int) $record->adult_pax;
        $childPaxChanged = $childPax !== (int) $record->child_pax;

        if ($adultPaxChanged || $childPaxChanged) {
            $adultPrice  = (float) $record->base_adult_price;
            $childPrice  = (float) $record->base_child_price;
            $adultTotal  = round($adultPrice * $adultPax, 2);
            $childTotal  = round($childPrice * $childPax, 2);
            $finalAmount = max(0, round($adultTotal + $childTotal - $discount, 2));

            $data['adult_total']  = $adultTotal;
            $data['child_total']  = $childTotal;
            $data['final_amount'] = $finalAmount;
        } else {
            $finalAmount = (float) ($record->final_amount ?? 0);
        }

        $amountPaid          = (float) ($data['amount_paid'] ?? 0);
        $data['balance_due'] = max(0, round($finalAmount - $amountPaid, 2));

        return $data;
    }
}


