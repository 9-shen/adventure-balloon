<?php

namespace App\Filament\Exports;

use App\Models\Booking;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class BookingExporter extends Exporter
{
    protected static ?string $model = Booking::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('booking_ref'),
            ExportColumn::make('type'),
            ExportColumn::make('partner.id'),
            ExportColumn::make('guide.name'),
            ExportColumn::make('product.name'),
            ExportColumn::make('flight_date'),
            ExportColumn::make('flight_time'),
            ExportColumn::make('adult_pax'),
            ExportColumn::make('child_pax'),
            ExportColumn::make('booking_source'),
            ExportColumn::make('pickup_location'),
            ExportColumn::make('dropoff_location'),
            ExportColumn::make('partner_reference'),
            ExportColumn::make('base_adult_price'),
            ExportColumn::make('base_child_price'),
            ExportColumn::make('adult_total'),
            ExportColumn::make('child_total'),
            ExportColumn::make('discount_amount'),
            ExportColumn::make('discount_reason'),
            ExportColumn::make('final_amount'),
            ExportColumn::make('payment_method'),
            ExportColumn::make('payment_status'),
            ExportColumn::make('amount_paid'),
            ExportColumn::make('balance_due'),
            ExportColumn::make('booking_status'),
            ExportColumn::make('attendance'),
            ExportColumn::make('cancelled_reason'),
            ExportColumn::make('notes'),
            ExportColumn::make('created_by'),
            ExportColumn::make('confirmed_by'),
            ExportColumn::make('confirmed_at'),
            ExportColumn::make('cancelled_by'),
            ExportColumn::make('cancelled_at'),
            ExportColumn::make('invoice.id'),
            ExportColumn::make('invoiced_at'),
            ExportColumn::make('deleted_at'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your booking export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
