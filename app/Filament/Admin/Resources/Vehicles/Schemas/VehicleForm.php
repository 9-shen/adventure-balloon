<?php

namespace App\Filament\Admin\Resources\Vehicles\Schemas;

use App\Models\TransportCompany;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class VehicleForm
{
    public static function make(Schema $form): Schema
    {
        return $form->components([

            Section::make('Vehicle Information')
                ->icon('heroicon-o-truck')
                ->columns(2)
                ->schema([
                    Select::make('transport_company_id')
                        ->label('Transport Company')
                        ->relationship('transportCompany', 'company_name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull(),

                    TextInput::make('make')
                        ->label('Make')
                        ->placeholder('e.g. Mercedes')
                        ->required()
                        ->maxLength(100),

                    TextInput::make('model')
                        ->label('Model')
                        ->placeholder('e.g. Sprinter')
                        ->required()
                        ->maxLength(100),

                    TextInput::make('plate_number')
                        ->label('Plate Number')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50),

                    Select::make('vehicle_type')
                        ->label('Vehicle Type')
                        ->options([
                            'van'     => 'Van',
                            'minibus' => 'Minibus',
                            'bus'     => 'Bus',
                            'car'     => 'Car',
                        ])
                        ->default('van')
                        ->required(),

                    TextInput::make('capacity')
                        ->label('Passenger Capacity')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(100)
                        ->required()
                        ->suffix('seats'),

                    TextInput::make('price_per_trip')
                        ->label(fn() => 'Price Per Trip (' . app(\App\Settings\AppSettings::class)->getIsoCurrency() . ')')
                        ->numeric()
                        ->minValue(0)
                        ->prefix(fn() => app(\App\Settings\AppSettings::class)->getIsoCurrency())
                        ->default(0),
                ]),

            Section::make('Status & Notes')
                ->icon('heroicon-o-cog-6-tooth')
                ->columns(2)
                ->schema([
                    Toggle::make('is_active')
                        ->label('Vehicle Active')
                        ->default(true)
                        ->inline(false),

                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(9)
                        ->columnSpanFull(),
                ]),

            Section::make('Assigned Driver')
                ->icon('heroicon-o-user')
                ->description('The driver currently assigned to this vehicle.')
                ->schema([
                    Placeholder::make('_assigned_driver')
                        ->label('')
                        ->content(function ($record): HtmlString {
                            if (! $record) {
                                return new HtmlString('<span style="color:#9ca3af;font-style:italic;">Save the vehicle first to see its assigned driver.</span>');
                            }
                            $driver = $record->driver;
                            if (! $driver) {
                                return new HtmlString('<span style="color:#9ca3af;font-style:italic;">No driver assigned to this vehicle yet. Assign one via the Drivers form.</span>');
                            }
                            return new HtmlString(
                                '<div style="display:flex;align-items:center;gap:12px;">' .
                                    '<div style="background:#0e7490;color:#fff;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px;flex-shrink:0;">' .
                                    strtoupper(substr($driver->name, 0, 1)) .
                                    '</div>' .
                                    '<div>' .
                                    '<div style="font-weight:700;color:#1f2937;font-size:15px;">' . e($driver->name) . '</div>' .
                                    '<div style="font-size:13px;color:#6b7280;">' . e($driver->phone ?? '—') . '</div>' .
                                    '</div>' .
                                    '</div>'
                            );
                        })
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
