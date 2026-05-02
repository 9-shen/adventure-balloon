<?php

namespace App\Filament\Admin\Resources\Dispatches\Pages;

use App\Filament\Admin\Resources\Dispatches\DispatchResource;
use App\Models\Dispatch;
use App\Services\DispatchService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\View\View;

class ViewDispatch extends ViewRecord
{
    protected static string $resource = DispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [

            // ── Send via WhatsApp Web (wa.me — no Twilio required) ────────────
            Action::make('send_whatsapp_web')
                ->label('Send via WhatsApp Web')
                ->icon('heroicon-o-device-phone-mobile')
                ->color('info')
                ->modalHeading('Open WhatsApp for Each Driver')
                ->modalSubmitActionLabel('Done')
                ->modalCancelActionLabel('Close')
                ->modalContent(function (): View {
                    /** @var Dispatch $dispatch */
                    $dispatch = $this->getRecord();
                    $links    = app(DispatchService::class)->buildWhatsAppWebUrls($dispatch);

                    return view('filament.dispatches.whatsapp-web-links', [
                        'links' => $links,
                    ]);
                })
                ->action(fn () => null), // Links open in new tabs; no server action needed

            EditAction::make(),
        ];
    }
}
