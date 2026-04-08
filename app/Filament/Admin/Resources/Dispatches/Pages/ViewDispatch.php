<?php

namespace App\Filament\Admin\Resources\Dispatches\Pages;

use App\Filament\Admin\Resources\Dispatches\DispatchResource;
use App\Models\Dispatch;
use App\Services\DispatchService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewDispatch extends ViewRecord
{
    protected static string $resource = DispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ── Send WhatsApp to Drivers ──────────────────────────────────────
            Action::make('send_whatsapp')
                ->label('Send WhatsApp to Drivers')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Send WhatsApp Notifications')
                ->modalDescription(
                    'This will send a WhatsApp message to all assigned drivers with '
                    . 'the dispatch details (booking ref, date, time, pickup/dropoff, '
                    . 'PAX list). Continue?'
                )
                ->modalSubmitActionLabel('Yes, Send Now')
                ->action(function () {
                    /** @var Dispatch $dispatch */
                    $dispatch = $this->getRecord();
                    $service  = app(DispatchService::class);

                    $result = $service->sendWhatsAppToDrivers($dispatch);

                    $sent    = $result['sent'];
                    $failed  = $result['failed'];
                    $skipped = $result['skipped'];
                    $errors  = $result['errors'] ?? [];

                    // ── No drivers / WhatsApp disabled ────────────────────────
                    if ($sent === 0 && $failed === 0 && $skipped === 0) {
                        Notification::make()
                            ->title('WhatsApp Not Sent')
                            ->body($errors[0] ?? 'No drivers were notified.')
                            ->warning()
                            ->send();
                        return;
                    }

                    // ── Skipped only (no phone numbers) ───────────────────────
                    if ($sent === 0 && $failed === 0) {
                        Notification::make()
                            ->title('No Phone Numbers Found')
                            ->body("All {$skipped} driver(s) were skipped — no phone number on record.")
                            ->warning()
                            ->send();
                        return;
                    }

                    // ── All sent ──────────────────────────────────────────────
                    if ($failed === 0 && $skipped === 0) {
                        Notification::make()
                            ->title('WhatsApp Sent ✅')
                            ->body("Message delivered to {$sent} driver(s) successfully.")
                            ->success()
                            ->send();
                        return;
                    }

                    // ── Partial / errors ──────────────────────────────────────
                    $body = "Sent: {$sent} | Failed: {$failed} | Skipped: {$skipped}";
                    if (! empty($errors)) {
                        $body .= "\n" . implode("\n", array_slice($errors, 0, 3));
                    }

                    Notification::make()
                        ->title('WhatsApp Partially Sent')
                        ->body($body)
                        ->warning()
                        ->send();
                }),

            EditAction::make(),
        ];
    }
}
