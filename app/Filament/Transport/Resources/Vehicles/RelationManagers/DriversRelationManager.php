<?php

namespace App\Filament\Transport\Resources\Vehicles\RelationManagers;

use App\Models\Driver;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class DriversRelationManager extends RelationManager
{
    protected static string $relationship = 'drivers';

    protected static ?string $recordTitleAttribute = 'name';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Assigned Drivers')
            ->description('Drivers assigned to this vehicle. Set one as the default.')
            ->columns([
                TextColumn::make('name')
                    ->label('Driver Name')
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('Phone'),

                TextColumn::make('license_number')
                    ->label('License No.'),

                ToggleColumn::make('is_default')
                    ->label('Default Driver')
                    ->getStateUsing(fn($record) => (bool) $record->pivot->is_default)
                    ->updateStateUsing(function ($record, $state) {
                        /** @var \App\Models\Vehicle $vehicle */
                        $vehicle = $this->getOwnerRecord();

                        if ($state) {
                            // Unset all other defaults first
                            $vehicle->drivers()->updateExistingPivot(
                                $vehicle->drivers->pluck('id')->toArray(),
                                ['is_default' => false]
                            );
                        }

                        $vehicle->drivers()->updateExistingPivot($record->id, [
                            'is_default' => $state,
                        ]);
                    }),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Assign Driver')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function () {
                        /** @var \App\Models\User $user */
                        $user = Auth::user();
                        return Driver::where('transport_company_id', $user->transport_company_id)
                            ->where('is_active', true);
                    }),
            ])
            ->actions([
                DetachAction::make()->label('Remove'),
            ]);
    }
}
