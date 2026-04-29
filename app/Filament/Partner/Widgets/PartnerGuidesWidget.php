<?php

namespace App\Filament\Partner\Widgets;

use App\Models\Booking;
use App\Models\Guide;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class PartnerGuidesWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    public int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Guides & Their Bookings';

    protected function getTableQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return Guide::query()
            ->where('partner_id', $user->partner_id)
            ->where('is_active', true)
            ->withCount([
                'bookings as total_bookings',
                'bookings as confirmed_bookings' => fn ($q) => $q->where('booking_status', 'confirmed'),
                'bookings as pending_bookings'   => fn ($q) => $q->where('booking_status', 'pending'),
                'bookings as completed_bookings' => fn ($q) => $q->where('booking_status', 'completed'),
            ]);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label('Guide Name')
                ->weight('bold')
                ->searchable(),

            TextColumn::make('email')
                ->label('Email')
                ->placeholder('—')
                ->copyable()
                ->copyMessage('Copied!'),

            TextColumn::make('phone')
                ->label('Phone')
                ->placeholder('—'),

            TextColumn::make('total_bookings')
                ->label('Total')
                ->alignCenter()
                ->badge()
                ->color('primary'),

            TextColumn::make('confirmed_bookings')
                ->label('Confirmed')
                ->alignCenter()
                ->badge()
                ->color('success'),

            TextColumn::make('pending_bookings')
                ->label('Pending')
                ->alignCenter()
                ->badge()
                ->color('warning'),

            TextColumn::make('completed_bookings')
                ->label('Completed')
                ->alignCenter()
                ->badge()
                ->color('info'),

            TextColumn::make('is_active')
                ->label('Status')
                ->badge()
                ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive'),
        ];
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25];
    }
}
