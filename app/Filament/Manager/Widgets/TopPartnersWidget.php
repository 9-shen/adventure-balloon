<?php

namespace App\Filament\Manager\Widgets;

use App\Models\Partner;
use App\Models\Booking;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TopPartnersWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Partner::query()
                    ->withCount(['bookings' => function (Builder $query) {
                        $query->where('booking_status', '!=', 'cancelled');
                    }])
                    ->withSum(['bookings' => function (Builder $query) {
                        $query->where('booking_status', '!=', 'cancelled');
                    }], 'final_amount')
                    ->orderByDesc('bookings_sum_final_amount')
                    ->limit(5)
            )
            ->heading('Top Performing Partners')
            ->columns([
                TextColumn::make('company_name')
                    ->label('Partner')
                    ->weight('bold')
                    ->searchable(),
                
                TextColumn::make('bookings_count')
                    ->label('Total Bookings')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('bookings_sum_final_amount')
                    ->label('Total Revenue Generated')
                    ->money('MAD')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
            ])
            ->paginated(false);
    }
}
