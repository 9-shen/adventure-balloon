<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Settings\NotificationSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NotificationSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.admin.pages.settings.notification-settings-page';

    public static function getNavigationIcon(): string|null
    {
        return 'heroicon-o-bell';
    }

    public static function getNavigationLabel(): string
    {
        return 'Notifications';
    }

    public static function getNavigationSort(): ?int
    {
        return 7;
    }

    public function getTitle(): \Illuminate\Contracts\Support\Htmlable|string
    {
        return 'Notification Settings';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $s = app(NotificationSettings::class);

        $this->form->fill([
            // Partner Booking
            'partner_booking_email'              => $s->partner_booking_email,

            // Booking Confirmation
            'booking_confirmed_partner_email'    => $s->booking_confirmed_partner_email,
            'booking_confirmed_guide_email'      => $s->booking_confirmed_guide_email,

            // Driver Assignment
            'driver_assigned_email'              => $s->driver_assigned_email,

            // Cancellation
            'booking_cancelled_partner_email'    => $s->booking_cancelled_partner_email,
            'booking_cancelled_transport_email'  => $s->booking_cancelled_transport_email,
            'booking_cancelled_driver_email'     => $s->booking_cancelled_driver_email,
            'booking_cancelled_guide_email'      => $s->booking_cancelled_guide_email,
            'booking_cancelled_admin_email'      => $s->booking_cancelled_admin_email,

            // PAX Alerts
            'pax_alert_email'                    => $s->pax_alert_email,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([

                // ── Partner Booking ────────────────────────────────────────────
                Section::make('Partner Booking Alert')
                    ->description('Sent to admin when a partner submits a new booking (from Partner Portal or Admin panel).')
                    ->icon('heroicon-o-building-storefront')
                    ->schema([
                        Grid::make(3)->schema([
                            Toggle::make('partner_booking_email')
                                ->label('Email to Admin')
                                ->helperText('Notify company email when a partner booking is created.')
                                ->onColor('success')
                                ->offColor('danger'),
                        ]),
                    ]),

                // ── Booking Confirmation ───────────────────────────────────────
                Section::make('Booking Confirmation')
                    ->description('Sent to the partner when their booking is confirmed (pending → confirmed) by an admin, manager, or super_admin.')
                    ->icon('heroicon-o-check-badge')
                    ->schema([
                        Grid::make(3)->schema([
                            Toggle::make('booking_confirmed_partner_email')
                                ->label('Email to Partner')
                                ->helperText('Notify the partner by email when their booking is confirmed.')
                                ->onColor('success')
                                ->offColor('danger'),

                            Toggle::make('booking_confirmed_guide_email')
                                ->label('Email to Guide')
                                ->helperText('Notify the assigned guide by email.')
                                ->onColor('success')
                                ->offColor('danger'),
                        ]),
                    ]),

                // ── Driver Dispatch Assignment ─────────────────────────────────
                Section::make('Driver Dispatch Assignment')
                    ->description('Sent to each assigned driver when a dispatch is created or the "Send Notifications" button is clicked.')
                    ->icon('heroicon-o-truck')
                    ->schema([
                        Grid::make(3)->schema([
                            Toggle::make('driver_assigned_email')
                                ->label('Email to Driver')
                                ->helperText('Send assignment details to the driver\'s email address.')
                                ->onColor('success')
                                ->offColor('danger'),
                        ]),
                    ]),

                // ── Booking Cancellation ───────────────────────────────────────
                Section::make('Booking Cancellation')
                    ->description('Sent to all affected parties when a booking is cancelled.')
                    ->icon('heroicon-o-x-circle')
                    ->schema([
                        Grid::make(3)->schema([
                            Toggle::make('booking_cancelled_partner_email')
                                ->label('Email to Partner')
                                ->helperText('Notify the partner by email when their booking is cancelled.')
                                ->onColor('success')
                                ->offColor('danger'),

                            Toggle::make('booking_cancelled_transport_email')
                                ->label('Email to Transport Company')
                                ->helperText('Notify the assigned transport company by email.')
                                ->onColor('success')
                                ->offColor('danger'),

                            Toggle::make('booking_cancelled_driver_email')
                                ->label('Email to Drivers')
                                ->helperText('Notify each assigned driver by email.')
                                ->onColor('success')
                                ->offColor('danger'),

                            Toggle::make('booking_cancelled_guide_email')
                                ->label('Email to Guide')
                                ->helperText('Notify the assigned guide by email.')
                                ->onColor('success')
                                ->offColor('danger'),

                            Toggle::make('booking_cancelled_admin_email')
                                ->label('Email to Admin')
                                ->helperText('Notify the company email when a booking is cancelled.')
                                ->onColor('success')
                                ->offColor('danger'),
                        ]),
                    ]),

                // ── PAX Capacity Alerts ────────────────────────────────────────
                Section::make('PAX Capacity Alerts')
                    ->description('Sent to the admin when remaining daily PAX capacity drops to or below the warning threshold (configured in PAX Settings).')
                    ->icon('heroicon-o-bell-alert')
                    ->schema([
                        Grid::make(3)->schema([
                            Toggle::make('pax_alert_email')
                                ->label('Email Alert')
                                ->helperText('Send capacity warning to company email.')
                                ->onColor('success')
                                ->offColor('danger'),
                        ]),
                    ]),

            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Notification Settings')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $s    = app(NotificationSettings::class);

        $s->partner_booking_email              = (bool) ($data['partner_booking_email'] ?? false);
        $s->booking_confirmed_partner_email    = (bool) ($data['booking_confirmed_partner_email'] ?? false);
        $s->booking_confirmed_guide_email      = (bool) ($data['booking_confirmed_guide_email'] ?? false);
        $s->driver_assigned_email              = (bool) ($data['driver_assigned_email'] ?? false);
        $s->booking_cancelled_partner_email    = (bool) ($data['booking_cancelled_partner_email'] ?? false);
        $s->booking_cancelled_transport_email  = (bool) ($data['booking_cancelled_transport_email'] ?? false);
        $s->booking_cancelled_driver_email     = (bool) ($data['booking_cancelled_driver_email'] ?? false);
        $s->booking_cancelled_guide_email      = (bool) ($data['booking_cancelled_guide_email'] ?? false);
        $s->booking_cancelled_admin_email      = (bool) ($data['booking_cancelled_admin_email'] ?? false);
        $s->pax_alert_email                    = (bool) ($data['pax_alert_email'] ?? false);
        $s->save();

        Notification::make()
            ->title('Notification settings saved!')
            ->success()
            ->send();
    }

    public static function getNavigationGroup(): string|null
    {
        return 'Settings';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}
