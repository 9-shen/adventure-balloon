<?php

namespace App\Filament\Admin\Resources\Greeter\Pages;

use App\Filament\Admin\Resources\Greeter\GreeterBookingResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ViewGreeterBooking extends ViewRecord
{
    protected static string $resource = GreeterBookingResource::class;

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->components([
                // ── Booking Summary ──────────────────────────────────────────────
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
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'confirmed' => 'success',
                                'cancelled' => 'danger',
                                'completed' => 'info',
                                default     => 'warning',
                            })
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                        TextEntry::make('pax_label')
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
                    ])->columnSpanFull(),

                // ── Transport Assignment — one card per vehicle/driver row ────────
                Section::make('Transport Assignment')
                    ->icon('heroicon-o-truck')
                    ->visible(fn ($record): bool => $record->dispatch?->dispatchDriverRows->isNotEmpty() ?? false)
                    ->components([
                        TextEntry::make('transport_assignment_html')
                            ->label('')
                            ->columnSpanFull()
                            ->html()
                            ->getStateUsing(function ($record): HtmlString {
                                $rows = $record->dispatch?->dispatchDriverRows ?? collect();

                                if ($rows->isEmpty()) {
                                    return new HtmlString('<p class="text-sm text-gray-400">No vehicles assigned.</p>');
                                }

                                $cards = $rows->map(function ($row, $index) {
                                    $vehicle     = $row->vehicle;
                                    $driver      = $row->driver;
                                    $vehicleName = $vehicle
                                        ? trim(($vehicle->make ?? '') . ' ' . ($vehicle->model ?? ''))
                                        : '—';
                                    $plate       = e($vehicle?->plate_number ?? '—');
                                    $driverName  = e($driver?->name ?? '—');
                                    $driverPhone = e($driver?->phone ?? '—');
                                    $pax         = $row->pax_assigned ? "{$row->pax_assigned} PAX" : '—';
                                    $num         = $index + 1;

                                    return <<<HTML
<div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-3">
  <div class="flex items-center justify-between">
    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
      Vehicle {$num}
    </span>
    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-300">
      {$pax}
    </span>
  </div>
  <div class="grid grid-cols-2 gap-x-6 gap-y-3">
    <div>
      <p class="text-xs font-medium text-gray-400 dark:text-gray-500">Vehicle</p>
      <p class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-1">
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
        </svg>
        {$vehicleName}
      </p>
    </div>
    <div>
      <p class="text-xs font-medium text-gray-400 dark:text-gray-500">License Plate</p>
      <p class="mt-0.5">
        <span class="inline-flex items-center rounded bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-sm font-mono font-semibold text-gray-700 dark:text-gray-200">
          {$plate}
        </span>
      </p>
    </div>
    <div>
      <p class="text-xs font-medium text-gray-400 dark:text-gray-500">Driver</p>
      <p class="mt-0.5 text-sm text-gray-800 dark:text-gray-100 flex items-center gap-1">
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
        </svg>
        {$driverName}
      </p>
    </div>
    <div>
      <p class="text-xs font-medium text-gray-400 dark:text-gray-500">Phone</p>
      <p class="mt-0.5 text-sm text-gray-800 dark:text-gray-100 flex items-center gap-1">
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 6.75Z"/>
        </svg>
        {$driverPhone}
      </p>
    </div>
  </div>
</div>
HTML;
                                });

                                $grid = '<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">' . $cards->join('') . '</div>';
                                return new HtmlString($grid);
                            }),
                    ])->columnSpanFull(),
            ]);
    }
}
