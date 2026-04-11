<?php

namespace App\Filament\Transport\Resources\Dispatches;

use App\Filament\Transport\Resources\Dispatches\Pages\ListDispatches;
use App\Filament\Transport\Resources\Dispatches\Pages\ViewDispatch;
use App\Models\Dispatch;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DispatchResource extends Resource
{
    protected static ?string $model = Dispatch::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-map-pin';
    }

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Dispatches';
    }

    public static function getNavigationLabel(): string
    {
        return 'My Dispatches';
    }

    // Scope to this transport company only — read-only
    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return parent::getEloquentQuery()
            ->where('transport_company_id', $user->transport_company_id)
            ->with(['booking.product', 'drivers', 'dispatchDriverRows.driver', 'dispatchDriverRows.vehicle']);
    }

    public static function form(Schema $schema): Schema
    {
        // No create/edit — read-only resource
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dispatch Details')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('dispatch_ref')
                            ->label('Dispatch Ref')
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('booking.booking_ref')
                            ->label('Booking Ref'),

                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state) => match($state) {
                                'confirmed'   => 'success',
                                'in_progress' => 'warning',
                                'delivered'   => 'success',
                                default       => 'gray',
                            }),

                        TextEntry::make('flight_date')
                            ->label('Flight Date')
                            ->date('d M Y'),

                        TextEntry::make('pickup_time')
                            ->label('Pickup Time'),

                        TextEntry::make('total_pax')
                            ->label('Total PAX')
                            ->suffix(' pax'),
                    ]),
                ]),

            Section::make('Route')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('pickup_location')
                            ->label('Pickup Location'),
                        TextEntry::make('dropoff_location')
                            ->label('Drop-off Location'),
                    ]),
                ]),

            Section::make('Assigned Drivers')
                ->schema([
                    RepeatableEntry::make('dispatchDriverRows')
                        ->label('')
                        ->contained(false)
                        ->schema([
                            Grid::make(4)->schema([
                                TextEntry::make('driver.name')->label('Driver'),
                                TextEntry::make('vehicle.make')->label('Vehicle Make'),
                                TextEntry::make('vehicle.plate_number')->label('Plate'),
                                TextEntry::make('pax_assigned')->label('PAX'),
                            ]),
                        ]),
                ]),

            Section::make('Passengers')
                ->collapsed()
                ->schema([
                    RepeatableEntry::make('booking.customers')
                        ->label('')
                        ->contained(false)
                        ->schema([
                            Grid::make(4)->schema([
                                TextEntry::make('full_name')->label('Name'),
                                TextEntry::make('nationality')->label('Nationality'),
                                TextEntry::make('phone')->label('Phone'),
                                TextEntry::make('passport_number')->label('Passport'),
                            ]),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('dispatch_ref')
                    ->label('Dispatch Ref')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('booking.booking_ref')
                    ->label('Booking Ref')
                    ->searchable(),

                TextColumn::make('flight_date')
                    ->label('Flight Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('pickup_time')
                    ->label('Pickup Time'),

                TextColumn::make('total_pax')
                    ->label('PAX')
                    ->suffix(' pax'),

                TextColumn::make('booking.product.name')
                    ->label('Product'),

                TextColumn::make('drivers_count')
                    ->label('Drivers')
                    ->counts('drivers')
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match($state) {
                        'confirmed'   => 'success',
                        'in_progress' => 'warning',
                        'delivered'   => 'success',
                        default       => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending'     => 'Pending',
                        'confirmed'   => 'Confirmed',
                        'in_progress' => 'In Progress',
                        'delivered'   => 'Delivered',
                    ]),
            ])
            ->defaultSort('flight_date', 'desc')
            ->recordUrl(fn($record) => static::getUrl('view', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDispatches::route('/'),
            'view'  => ViewDispatch::route('/{record}'),
        ];
    }

    // Read-only — no create/edit/delete
    public static function canCreate(): bool
    {
        return false;
    }
}
