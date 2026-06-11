<?php

namespace App\Filament\Admin\Resources\BalloonDispatches\Pages;

use App\Filament\Admin\Resources\BalloonDispatches\BalloonDispatchResource;
use App\Models\BalloonDispatch;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\View\View;

class ViewBalloonDispatch extends ViewRecord
{
    protected static string $resource = BalloonDispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send_whatsapp')
                ->label('Send WhatsApp Notification')
                ->icon('heroicon-o-device-phone-mobile')
                ->color('success')
                ->modalHeading('Send WhatsApp to Managers & Greeters')
                ->modalSubmitActionLabel('Done')
                ->modalCancelActionLabel('Close')
                ->modalContent(function (): View {
                    /** @var BalloonDispatch $dispatch */
                    $dispatch = $this->getRecord();

                    $recipients = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['manager', 'greeter']))
                        ->whereNotNull('phone')
                        ->where('phone', '!=', '')
                        ->where('is_active', true)
                        ->get();

                    $date    = Carbon::parse($dispatch->dispatch_date)->format('d/m/Y');
                    $rawHtml = $dispatch->content ?? '';
                    // Convert block-level HTML tags to newlines
                    $withNewlines = preg_replace(
                        ['/<\/p\s*>/i', '/<br\s*\/?>/i', '/<\/li\s*>/i', '/<\/div\s*>/i'],
                        "\n",
                        $rawHtml
                    );
                    $plainText = html_entity_decode(strip_tags($withNewlines), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    // Collapse 3+ consecutive newlines to 2, and trim
                    $plainText = preg_replace('/\n{3,}/', "\n\n", trim($plainText));
                    $excerpt   = \Illuminate\Support\Str::limit($plainText, 500);

                    $links = $recipients->map(function (User $user) use ($date, $excerpt): array {
                        $msg   = "*Adventure Balloon — Balloon Dispatch Update*\n\n📅 Date: {$date}\n\n{$excerpt}\n\n— Adventure Balloon Operations";
                        $phone = ltrim($user->phone, '+');

                        return [
                            'name'  => $user->name,
                            'role'  => $user->getRoleNames()->first() ?? 'user',
                            'phone' => $user->phone,
                            'url'   => 'https://wa.me/' . $phone . '?text=' . rawurlencode($msg),
                        ];
                    })->all();

                    return view('filament.balloon-dispatcher.pages.whatsapp-recipients-modal', [
                        'links'    => $links,
                        'dispatch' => $dispatch,
                    ]);
                })
                ->action(fn () => null),

            Action::make('download_image')
                ->label('Download Image')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->url(fn () => $this->getRecord()->hasImage()
                    ? route('balloon-dispatch.image.download', $this->getRecord())
                    : null
                )
                ->hidden(fn () => ! $this->getRecord()->hasImage())
                ->openUrlInNewTab(false),

            \Filament\Actions\EditAction::make(),
        ];
    }
}
