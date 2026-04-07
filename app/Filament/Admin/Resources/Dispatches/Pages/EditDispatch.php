<?php

namespace App\Filament\Admin\Resources\Dispatches\Pages;

use App\Filament\Admin\Resources\Dispatches\DispatchResource;
use App\Filament\Admin\Resources\Dispatches\Schemas\DispatchForm;
use App\Models\Dispatch;
use App\Services\DispatchService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

class EditDispatch extends EditRecord
{
    protected static string $resource = DispatchResource::class;

    // ─── Form: Read-only booking info + editable transport + repeater ─────────

    public function form(Schema $form): Schema
    {
        /** @var Dispatch $dispatch */
        $dispatch = $this->getRecord();

        return DispatchForm::forEdit($form, $dispatch);
    }

    // ─── Pre-fill: Load existing dispatch_driver rows into the Repeater ───────

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $dispatch = $this->getRecord();

        // Populate the Repeater with existing driver assignment rows
        $data['dispatch_drivers'] = $dispatch->dispatchDriverRows
            ->map(fn ($row) => [
                'driver_id'    => $row->driver_id,
                'vehicle_id'   => $row->vehicle_id,
                'pax_assigned' => $row->pax_assigned,
            ])
            ->toArray();

        return $data;
    }

    // ─── Before Save: Clean up nullable fields ────────────────────────────────

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Strip the read-only booking info placeholder keys (not DB columns)
        foreach (array_keys($data) as $key) {
            if (str_starts_with($key, '_b_')) {
                unset($data[$key]);
            }
        }

        $data['pickup_time']      = $data['pickup_time'] ?: null;
        $data['pickup_location']  = $data['pickup_location'] ?? null;
        $data['dropoff_location'] = $data['dropoff_location'] ?? null;
        $data['notes']            = $data['notes'] ?? null;

        return $data;
    }

    // ─── After Save: Sync dispatch_driver rows ────────────────────────────────

    protected function afterSave(): void
    {
        $dispatch   = $this->getRecord();
        $driverRows = $this->data['dispatch_drivers'] ?? [];

        // Delete current rows and recreate from the Repeater state
        $dispatch->dispatchDriverRows()->delete();

        foreach ($driverRows as $row) {
            $dispatch->dispatchDriverRows()->create([
                'driver_id'    => $row['driver_id'],
                'vehicle_id'   => $row['vehicle_id'],
                'pax_assigned' => (int) ($row['pax_assigned'] ?? 0),
                'status'       => 'pending',
            ]);
        }
    }

    // ─── Header Actions ───────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send_notifications')
                ->label('Send Notifications')
                ->icon('heroicon-o-bell')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Send Dispatch Notifications')
                ->modalDescription(
                    'This will email the transport company and notify all assigned drivers. Continue?'
                )
                ->action(function () {
                    /** @var Dispatch $dispatch */
                    $dispatch = $this->getRecord();
                    $service  = app(DispatchService::class);

                    $service->notifyTransporter($dispatch);
                    $service->notifyDrivers($dispatch);

                    Notification::make()
                        ->title('Notifications Sent')
                        ->body('Transporter and drivers have been notified.')
                        ->success()
                        ->send();
                }),

            Action::make('update_status')
                ->label('Update Status')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->form([
                    Select::make('status')
                        ->label('New Status')
                        ->required()
                        ->native(false)
                        ->options([
                            'pending'     => 'Pending',
                            'confirmed'   => 'Confirmed',
                            'in_progress' => 'In Progress',
                            'delivered'   => 'Delivered',
                            'cancelled'   => 'Cancelled',
                        ]),
                ])
                ->action(function (array $data) {
                    $this->getRecord()->update(['status' => $data['status']]);

                    Notification::make()
                        ->title('Status Updated')
                        ->success()
                        ->send();
                }),

            DeleteAction::make(),
        ];
    }
}
