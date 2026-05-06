<?php

namespace App\Filament\BalloonDispatcher\Resources\BalloonDispatchResource\Pages;

use App\Filament\BalloonDispatcher\Resources\BalloonDispatchResource\BalloonDispatchResource;
use App\Filament\BalloonDispatcher\Widgets\BalloonDispatchPaxTodayWidget;
use App\Models\BalloonDispatch;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBalloonDispatches extends ListRecords
{
    protected static string $resource = BalloonDispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Balloon Dispatch')
                ->icon('heroicon-o-plus')
                ->modalWidth('4xl')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['created_by'] = auth()->id();
                    return $data;
                })
                ->after(function (BalloonDispatch $record) {
                    // WhatsApp notification can be triggered after creation
                }),

            \Filament\Actions\Action::make('send_whatsapp')
                ->label('Send WhatsApp')
                ->icon('heroicon-o-device-phone-mobile')
                ->color('success')
                ->modalHeading('Send WhatsApp Notification')
                ->modalSubmitActionLabel('Done')
                ->modalCancelActionLabel('Close')
                ->modalContent(function (): \Illuminate\Contracts\View\View {
                    $latest = BalloonDispatch::latest('dispatch_date')->first();

                    $recipients = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['manager', 'greeter']))
                        ->whereNotNull('phone')
                        ->where('phone', '!=', '')
                        ->where('is_active', true)
                        ->get();

                    $date    = $latest ? Carbon::parse($latest->dispatch_date)->format('d/m/Y') : today()->format('d/m/Y');
                    $excerpt = $latest ? \Illuminate\Support\Str::limit(strip_tags($latest->content ?? ''), 500) : '';

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
                        'dispatch' => $latest,
                    ]);
                })
                ->action(fn () => null),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BalloonDispatchPaxTodayWidget::class,
        ];
    }
}
