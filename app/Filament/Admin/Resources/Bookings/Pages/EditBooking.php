<?php

namespace App\Filament\Admin\Resources\Bookings\Pages;

use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

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

            // Cancel Action
            Action::make('cancel')
                ->label('Cancel Booking')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => !$this->getRecord()->isCancelled())
                ->form([
                    \Filament\Forms\Components\Textarea::make('cancelled_reason')
                        ->label('Cancellation Reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    /** @var Booking $booking */
                    $booking = $this->getRecord();
                    $booking->update([
                        'booking_status'   => 'cancelled',
                        'cancelled_reason' => $data['cancelled_reason'],
                        'cancelled_by'     => Auth::id(),
                        'cancelled_at'     => now(),
                    ]);
                    Notification::make()
                        ->title('Booking Cancelled')
                        ->body("Booking {$booking->booking_ref} has been cancelled.")
                        ->warning()
                        ->send();
                }),

            DeleteAction::make(),
        ];
    }

    /**
     * Recalculate balance_due when payment data changes.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $finalAmount = (float) ($this->getRecord()->final_amount ?? 0);
        $amountPaid  = (float) ($data['amount_paid'] ?? 0);
        $data['balance_due'] = max(0, round($finalAmount - $amountPaid, 2));

        return $data;
    }
}
