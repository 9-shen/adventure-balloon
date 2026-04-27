<?php

namespace App\Filament\Manager\Pages;

use App\Filament\Admin\Pages\BookingCalendarPage as AdminBookingCalendarPage;

class BookingCalendarPage extends AdminBookingCalendarPage
{
    /**
     * Point to the manager-namespaced blade so its Tailwind classes are
     * compiled by the manager theme (resources/css/filament/manager/theme.css).
     */
    public function getView(): string
    {
        return 'filament.manager.pages.booking-calendar';
    }
}
