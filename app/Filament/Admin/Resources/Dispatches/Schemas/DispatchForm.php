<?php

namespace App\Filament\Admin\Resources\Dispatches\Schemas;

use App\Models\Booking;
use App\Models\Driver;
use App\Models\TransportCompany;
use App\Models\Vehicle;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class DispatchForm
{
    // ─────────────────────────────────────────────────────────────────────────
    //  CREATE form — full reactive form with booking selector + info card
    // ─────────────────────────────────────────────────────────────────────────

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ── 1. Booking Selection ──────────────────────────────────────────
            Section::make('Booking')
                ->columns(1)
                ->components([

                    Select::make('booking_id')
                        ->label('Select Confirmed Booking')
                        ->required()
                        ->searchable()
                        ->native(false)
                        ->placeholder('Search by booking ref, date or passenger count…')
                        ->options(function (?Model $record): array {
                            // On Edit: include the already-dispatched booking for this record
                            $query = Booking::where('booking_status', 'confirmed')
                                ->with(['product', 'partner']);

                            if ($record === null) {
                                // Create form: only show bookings without a dispatch
                                $query->whereDoesntHave('dispatch');
                            } else {
                                // Edit form: show undispatched + the current booking
                                $currentBookingId = $record->booking_id ?? null;
                                $query->where(function ($q) use ($currentBookingId) {
                                    $q->whereDoesntHave('dispatch');
                                    if ($currentBookingId) {
                                        $q->orWhere('id', $currentBookingId);
                                    }
                                });
                            }

                            return $query->get()
                                ->mapWithKeys(fn(Booking $b) => [
                                    $b->id => self::formatBookingLabel($b),
                                ])
                                ->toArray();
                        })
                        ->disabled(fn(?Model $record): bool => $record !== null)
                        ->live(),

                    // ── Reactive Booking Info Card (Get $get only — OOM-safe) ──
                    Placeholder::make('_booking_info_card')
                        ->label('')
                        ->content(function (Get $get): HtmlString {
                            return self::buildBookingInfoCard((int) $get('booking_id'));
                        })
                        ->columnSpanFull(),
                ]),

            // ── 2. Transport & Logistics ──────────────────────────────────────
            Section::make('Transport & Logistics')
                ->columns(2)
                ->components([

                    Select::make('transport_company_id')
                        ->label('Transport Company')
                        ->required()
                        ->searchable()
                        ->native(false)
                        ->options(function (): array {
                            return TransportCompany::where('is_active', true)
                                ->pluck('company_name', 'id')
                                ->toArray();
                        })
                        ->live()
                        ->columnSpanFull(),

                    Select::make('status')
                        ->label('Dispatch Status')
                        ->required()
                        ->native(false)
                        ->default('pending')
                        ->options([
                            'pending'     => '⏳ Pending',
                            'confirmed'   => '✅ Confirmed',
                            'in_progress' => '🚌 In Progress',
                            'delivered'   => '🏁 Delivered',
                            'cancelled'   => '❌ Cancelled',
                        ]),

                    TimePicker::make('pickup_time')
                        ->label('Pickup Time')
                        ->seconds(false)
                        ->native(false),

                    TextInput::make('pickup_location')
                        ->label('Pickup Location')
                        ->maxLength(500),

                    TextInput::make('dropoff_location')
                        ->label('Dropoff Location')
                        ->maxLength(500),
                ]),

            // ── 3. Driver Assignments ─────────────────────────────────────────
            Section::make('Driver Assignments')
                ->description(
                    'Add driver rows manually, or leave empty to let the system auto-assign '
                        . 'based on total PAX ÷ vehicle capacity.'
                )
                ->components([
                    self::buildDriverRepeater(),
                ])->columnSpanFull(),

            // ── 4. Notes ──────────────────────────────────────────────────────
            Section::make('Notes')
                ->components([
                    Textarea::make('notes')
                        ->label('Internal Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columnSpanFull(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  EDIT form — same transport + driver sections, but booking is read-only.
    //  Called from EditDispatch::form() which passes the loaded Dispatch record.
    // ─────────────────────────────────────────────────────────────────────────

    public static function forEdit(Schema $schema, \App\Models\Dispatch $dispatch): Schema
    {
        $booking = $dispatch->booking?->load(['product', 'partner']);

        return $schema->components([

            // ── 1. Booking Info (Read-Only) ───────────────────────────────────
            Section::make('Booking')
                ->description('Booking assignment is locked after dispatch creation.')
                ->columns(3)
                ->components(
                    array_merge(
                        // Hidden: keeps booking_id in form state (required by DB column)
                        [\Filament\Forms\Components\Hidden::make('booking_id')],
                        self::staticBookingComponents($booking)
                    )
                ),

            // ── 2. Transport & Logistics ──────────────────────────────────────
            Section::make('Transport & Logistics')
                ->columns(2)
                ->components([

                    Select::make('transport_company_id')
                        ->label('Transport Company')
                        ->required()
                        ->searchable()
                        ->native(false)
                        ->options(function (): array {
                            return TransportCompany::where('is_active', true)
                                ->pluck('company_name', 'id')
                                ->toArray();
                        })
                        ->live()
                        ->columnSpanFull(),

                    Select::make('status')
                        ->label('Dispatch Status')
                        ->required()
                        ->native(false)
                        ->options([
                            'pending'     => '⏳ Pending',
                            'confirmed'   => '✅ Confirmed',
                            'in_progress' => '🚌 In Progress',
                            'delivered'   => '🏁 Delivered',
                            'cancelled'   => '❌ Cancelled',
                        ]),

                    TimePicker::make('pickup_time')
                        ->label('Pickup Time')
                        ->seconds(false)
                        ->native(false),

                    TextInput::make('pickup_location')
                        ->label('Pickup Location')
                        ->maxLength(500),

                    TextInput::make('dropoff_location')
                        ->label('Dropoff Location')
                        ->maxLength(500),
                ]),

            // ── 3. Driver Assignments ─────────────────────────────────────────
            Section::make('Driver Assignments')
                ->description('Add, remove or adjust driver rows. Changes are saved when you click Save.')
                ->components([
                    self::buildDriverRepeater(),
                ])->columnSpanFull(),

            // ── 4. Notes ──────────────────────────────────────────────────────
            Section::make('Notes')
                ->components([
                    Textarea::make('notes')
                        ->label('Internal Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columnSpanFull(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Shared: Driver Repeater (driver_id & vehicle_id filtered by company)
    // ─────────────────────────────────────────────────────────────────────────

    public static function buildDriverRepeater(): Repeater
    {
        return Repeater::make('dispatch_drivers')
            ->label('')
            ->schema([
                Grid::make(3)->components([

                    Select::make('driver_id')
                        ->label('Driver')
                        ->required()
                        ->searchable()
                        ->native(false)
                        ->options(function (Get $get): array {
                            $companyId = (int) $get('../../transport_company_id');
                            if (!$companyId) {
                                return [];
                            }
                            return Driver::where('transport_company_id', $companyId)
                                ->where('is_active', true)
                                ->pluck('name', 'id')
                                ->toArray();
                        }),

                    Select::make('vehicle_id')
                        ->label('Vehicle')
                        ->required()
                        ->searchable()
                        ->native(false)
                        ->options(function (Get $get): array {
                            $companyId = (int) $get('../../transport_company_id');
                            if (!$companyId) {
                                return [];
                            }
                            return Vehicle::where('transport_company_id', $companyId)
                                ->where('is_active', true)
                                ->get()
                                ->mapWithKeys(fn($v) => [
                                    $v->id => "{$v->make} {$v->model} — {$v->plate_number} (cap: {$v->capacity})",
                                ])
                                ->toArray();
                        }),

                    TextInput::make('pax_assigned')
                        ->label('PAX Assigned')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->default(1),
                ]),
            ])
            ->addActionLabel('+ Add Driver')
            ->reorderable(false)
            ->collapsible()
            ->defaultItems(0)
            ->itemLabel(function (array $state): ?string {
                // Generate a visible label for each collapsed repeater item
                $driverId  = $state['driver_id'] ?? null;
                $pax       = $state['pax_assigned'] ?? '?';
                $driverName = $driverId ? (Driver::find($driverId)?->name ?? 'Driver #' . $driverId) : 'New Driver';
                return "{$driverName} — {$pax} PAX";
            });
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Format the booking dropdown option label.
     * e.g. "[REGULAR] BLX-2026-0001 — 13/05/2026 — 3 PAX"
     * e.g. "[PARTNER] PBX-2026-0001 — Atlas Co — 13/05/2026 — 5 PAX"
     */
    public static function formatBookingLabel(Booking $b): string
    {
        $tag      = strtoupper($b->type ?? 'regular');  // REGULAR | PARTNER
        $ref      = $b->booking_ref;
        $date     = $b->flight_date?->format('d/m/Y') ?? '—';
        $pax      = $b->getTotalPax();
        $extra    = ($b->type === 'partner' && $b->partner)
            ? ' — ' . $b->partner->company_name
            : '';

        return "[{$tag}] {$ref}{$extra} — {$date} — {$pax} PAX";
    }

    /**
     * Build the reactive booking info card for the CREATE form.
     * Uses Get $get (read-only, OOM-safe — no Set involved).
     */
    public static function buildBookingInfoCard(int $bookingId): HtmlString
    {
        if (!$bookingId) {
            return new HtmlString(
                '<div style="color:#9ca3af;font-style:italic;font-size:14px;padding:8px 0;">'
                    . '← Select a confirmed booking above to view its details.'
                    . '</div>'
            );
        }

        $booking = Booking::with(['product', 'partner'])->find($bookingId);

        if (!$booking) {
            return new HtmlString('<div style="color:#ef4444;">Booking not found.</div>');
        }

        return new HtmlString(self::renderBookingCard($booking));
    }

    /**
     * Build static Placeholder components for the EDIT form (no reactive needed).
     */
    public static function staticBookingComponents(?Booking $booking): array
    {
        if (!$booking) {
            return [
                Placeholder::make('_no_booking')->label('')->content('No booking linked.'),
            ];
        }

        $components = [
            Placeholder::make('_b_ref')
                ->label('Booking Ref')
                ->content($booking->booking_ref),

            Placeholder::make('_b_type')
                ->label('Type')
                ->content(new HtmlString(
                    $booking->type === 'partner'
                        ? '<span style="background:#7c3aed;color:#fff;padding:2px 10px;border-radius:4px;font-size:12px;font-weight:600;">PARTNER</span>'
                        : '<span style="background:#0ea5e9;color:#fff;padding:2px 10px;border-radius:4px;font-size:12px;font-weight:600;">REGULAR</span>'
                )),

            Placeholder::make('_b_date')
                ->label('Flight Date')
                ->content($booking->flight_date?->format('d F Y') ?? '—'),

            Placeholder::make('_b_product')
                ->label('Product')
                ->content($booking->product?->name ?? '—'),

            Placeholder::make('_b_pax')
                ->label('Total PAX')
                ->content(new HtmlString(
                    '<span style="font-size:22px;font-weight:700;color:' . self::paxColor($booking->getTotalPax()) . ';">'
                        . $booking->getTotalPax()
                        . '</span>'
                        . '<span style="font-size:12px;color:#6b7280;margin-left:6px;">'
                        . "{$booking->adult_pax} adults · {$booking->child_pax} children"
                        . '</span>'
                )),
        ];

        if ($booking->type === 'partner' && $booking->partner) {
            $components[] = Placeholder::make('_b_partner')
                ->label('Partner Company')
                ->content($booking->partner->company_name);

            $components[] = Placeholder::make('_b_partner_email')
                ->label('Partner Email')
                ->content($booking->partner->email ?? '—');
        }

        return $components;
    }

    /**
     * Render a styled HTML card with booking details.
     * Used in the reactive Create form Placeholder.
     */
    private static function renderBookingCard(Booking $booking): string
    {
        $isPartner = $booking->type === 'partner';
        $totalPax  = $booking->getTotalPax();
        $paxColor  = self::paxColor($totalPax);

        $typeBadge = $isPartner
            ? '<span style="background:#7c3aed;color:#fff;padding:2px 10px;border-radius:4px;font-size:11px;font-weight:700;letter-spacing:.5px;">PARTNER</span>'
            : '<span style="background:#0ea5e9;color:#fff;padding:2px 10px;border-radius:4px;font-size:11px;font-weight:700;letter-spacing:.5px;">REGULAR</span>';

        $partnerBlock = '';
        if ($isPartner && $booking->partner) {
            $partnerBlock = "
                <div style='grid-column:span 2;border-top:1px solid #e5e7eb;padding-top:12px;margin-top:4px;'>
                    <div style='font-size:11px;color:#7c3aed;text-transform:uppercase;font-weight:700;margin-bottom:4px;'>Partner Details</div>
                    <div style='font-weight:700;color:#1f2937;font-size:15px;'>{$booking->partner->company_name}</div>
                    <div style='font-size:13px;color:#6b7280;'>{$booking->partner->email}</div>
                </div>";
        }

        $slot = fn(string $lbl, string $val) => "
            <div>
                <div style='font-size:10px;color:#9ca3af;text-transform:uppercase;font-weight:700;letter-spacing:.5px;margin-bottom:3px;'>{$lbl}</div>
                <div style='font-weight:600;color:#1f2937;font-size:14px;'>{$val}</div>
            </div>";

        return "
        <div style='background:linear-gradient(135deg,#f8fafc,#f1f5f9);border:1px solid #e2e8f0;border-radius:10px;padding:16px 20px;margin-top:4px;'>
            <div style='display:grid;grid-template-columns:repeat(3,1fr);gap:14px;'>
                <div>
                    <div style='font-size:10px;color:#9ca3af;text-transform:uppercase;font-weight:700;letter-spacing:.5px;margin-bottom:6px;'>Booking</div>
                    <div style='font-weight:800;color:#1f2937;font-size:16px;'>{$booking->booking_ref}</div>
                    <div style='margin-top:6px;'>{$typeBadge}</div>
                </div>
                <div>
                    <div style='font-size:10px;color:#9ca3af;text-transform:uppercase;font-weight:700;letter-spacing:.5px;margin-bottom:3px;'>Flight Date</div>
                    <div style='font-weight:700;color:#1f2937;font-size:15px;'>{$booking->flight_date?->format('d F Y')}</div>
                    <div style='font-size:12px;color:#6b7280;margin-top:2px;'>" . ($booking->flight_time ?? '') . "</div>
                </div>
                <div>
                    <div style='font-size:10px;color:#9ca3af;text-transform:uppercase;font-weight:700;letter-spacing:.5px;margin-bottom:3px;'>Total PAX</div>
                    <div style='font-size:32px;font-weight:800;color:{$paxColor};line-height:1;'>{$totalPax}</div>
                    <div style='font-size:12px;color:#6b7280;margin-top:3px;'>{$booking->adult_pax} adults · {$booking->child_pax} children</div>
                </div>
                " . $slot('Product', $booking->product?->name ?? '—') . "
                " . $slot('Booking Source', ucfirst($booking->booking_source ?? '—')) . "
                " . $slot('Payment Status', ucfirst(str_replace('_', ' ', $booking->payment_status ?? '—'))) . "
                {$partnerBlock}
            </div>
        </div>";
    }

    private static function paxColor(int $pax): string
    {
        return $pax >= 10 ? '#dc2626' : ($pax >= 5 ? '#d97706' : '#059669');
    }
}
