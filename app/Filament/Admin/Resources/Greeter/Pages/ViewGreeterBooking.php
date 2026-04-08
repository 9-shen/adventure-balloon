<?php

namespace App\Filament\Admin\Resources\Greeter\Pages;

use App\Filament\Admin\Resources\Greeter\GreeterBookingResource;
use App\Models\BookingCustomer;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;

class ViewGreeterBooking extends ViewRecord
{
    protected static string $resource = GreeterBookingResource::class;

    /**
     * Track which customers have been toggled so Livewire re-renders.
     */
    public array $customerAttendance = [];

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Load initial attendance state from DB into component state
        $this->customerAttendance = $this->record
            ->customers
            ->mapWithKeys(fn ($c) => [$c->id => $c->attendance])
            ->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mark_all_show')
                ->label('✅ All Showed')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Mark All as Show')
                ->modalDescription('Mark every passenger in this booking as showed up?')
                ->action(function (): void {
                    $this->record->customers()->update(['attendance' => 'show']);
                    $this->record->update(['attendance' => 'show']);
                    $this->customerAttendance = $this->record
                        ->fresh('customers')
                        ->customers
                        ->mapWithKeys(fn ($c) => [$c->id => 'show'])
                        ->toArray();
                    Notification::make()
                        ->title('✅ All passengers marked as Show')
                        ->success()
                        ->send();
                }),

            Action::make('mark_all_no_show')
                ->label('❌ All No-Show')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalHeading('Mark All as No-Show')
                ->modalDescription('Mark ALL passengers as no-show?')
                ->action(function (): void {
                    $this->record->customers()->update(['attendance' => 'no_show']);
                    $this->record->update(['attendance' => 'no_show']);
                    $this->customerAttendance = $this->record
                        ->fresh('customers')
                        ->customers
                        ->mapWithKeys(fn ($c) => [$c->id => 'no_show'])
                        ->toArray();
                    Notification::make()
                        ->title('❌ All passengers marked as No-Show')
                        ->danger()
                        ->send();
                }),
        ];
    }

    /**
     * Livewire action — called from blade via wire:click.
     * Updates a single passenger's attendance.
     */
    public function setCustomerAttendance(int $customerId, string $attendance): void
    {
        $customer = BookingCustomer::find($customerId);

        if (! $customer || $customer->booking_id !== $this->record->id) {
            return;
        }

        $customer->update(['attendance' => $attendance]);
        $this->customerAttendance[$customerId] = $attendance;

        // Update booking-level attendance summary
        $this->syncBookingAttendance();

        $label = $attendance === 'show' ? '✅ Show' : '❌ No-Show';
        Notification::make()
            ->title("{$label} — {$customer->full_name}")
            ->color($attendance === 'show' ? 'success' : 'danger')
            ->send();
    }

    /**
     * Sync the booking-level attendance based on all PAX statuses.
     */
    private function syncBookingAttendance(): void
    {
        $customers = $this->record->customers()->get();
        $total   = $customers->count();
        $show    = $customers->where('attendance', 'show')->count();
        $noShow  = $customers->where('attendance', 'no_show')->count();

        $bookingAttendance = match (true) {
            $show === $total              => 'show',
            $noShow === $total            => 'no_show',
            default                      => 'pending',
        };

        $this->record->update(['attendance' => $bookingAttendance]);
    }

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->components([
                Section::make('Booking Summary')
                    ->columns(4)
                    ->components([
                        TextEntry::make('booking_ref')
                            ->label('Booking Ref')
                            ->badge()
                            ->color('primary')
                            ->copyable(),

                        TextEntry::make('type')
                            ->label('Type')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'partner' ? 'purple' : 'info')
                            ->formatStateUsing(fn (string $state): string => $state === 'partner' ? '🤝 Partner' : '✈️ Regular'),

                        TextEntry::make('booking_status')
                            ->label('Booking Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'confirmed' => 'success',
                                'cancelled' => 'danger',
                                'completed' => 'info',
                                default     => 'warning',
                            })
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                        TextEntry::make('pax_summary')
                            ->label('PAX Attendance')
                            ->getStateUsing(fn ($record) => $record->getPaxAttendanceLabel())
                            ->badge()
                            ->color('info'),

                        TextEntry::make('product.name')
                            ->label('Product'),

                        TextEntry::make('flight_date')
                            ->label('Flight Date')
                            ->date('d/m/Y'),

                        TextEntry::make('flight_time')
                            ->label('Flight Time')
                            ->time('H:i')
                            ->placeholder('—'),

                        TextEntry::make('partner.company_name')
                            ->label('Partner')
                            ->placeholder('Individual Booking'),
                    ]),
            ]);
    }

    /**
     * Override the default view to add our per-passenger attendance UI.
     */
    protected function getViewData(): array
    {
        return [
            'customers'          => $this->record->customers,
            'customerAttendance' => $this->customerAttendance,
        ];
    }
}
