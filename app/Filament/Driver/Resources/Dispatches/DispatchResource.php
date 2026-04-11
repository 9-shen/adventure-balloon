<?php

namespace App\Filament\Driver\Resources\Dispatches;

use App\Filament\Driver\Resources\Dispatches\Pages\ManageDispatches;
use App\Models\Dispatch;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DispatchResource extends Resource
{
    protected static ?string $model = \App\Models\Dispatch::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $recordTitleAttribute = 'dispatch_ref';
    
    public static function getNavigationLabel(): string
    {
        return 'My Dispatches';
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        
        return parent::getEloquentQuery()
            ->whereHas('drivers', function ($query) use ($user) {
                $query->where('drivers.id', $user->driver_id);
            });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Read-only info or status update field
                \Filament\Forms\Components\Select::make('status')
                    ->options([
                        'delivered'   => 'Delivered',
                        'cancelled'   => 'Cancelled (Issue / No Show)',
                    ])
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Dispatch Job Details')
                    ->description('All necessary information regarding the pickup and customer.')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('dispatch_ref')
                            ->label('Dispatch Ref')
                            ->badge()
                            ->color('warning')
                            ->inlineLabel(),
                            
                        \Filament\Infolists\Components\TextEntry::make('flight_date')
                            ->label('Flight Date')
                            ->date('d/m/Y')
                            ->inlineLabel(),

                        \Filament\Infolists\Components\TextEntry::make('pickup_time')
                            ->label('Pickup Time')
                            ->badge()
                            ->color('danger')
                            ->size('lg')
                            ->inlineLabel(),
                            
                        \Filament\Infolists\Components\TextEntry::make('total_pax')
                            ->label('Total PAX')
                            ->badge()
                            ->color('info')
                            ->inlineLabel(),

                        \Filament\Infolists\Components\TextEntry::make('pickup_location')
                            ->label('Pickup Location (Hotel/Room)')
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->icon('heroicon-m-map-pin')
                            ->inlineLabel(),

                        \Filament\Infolists\Components\TextEntry::make('customer_name')
                            ->label('Customer Name')
                            ->state(function ($record) {
                                $customer = $record->booking?->customers->where('is_primary', true)->first() 
                                         ?? $record->booking?->customers->first();
                                return $customer ? $customer->full_name : 'N/A';
                            })
                            ->icon('heroicon-m-user')
                            ->inlineLabel(),

                        \Filament\Infolists\Components\TextEntry::make('customer_phone')
                            ->label('Customer Contact')
                            ->state(function ($record) {
                                $customer = $record->booking?->customers->where('is_primary', true)->first() 
                                         ?? $record->booking?->customers->first();
                                return $customer ? $customer->phone : 'N/A';
                            })
                            ->icon('heroicon-m-phone')
                            ->copyable()
                            ->inlineLabel(),

                        \Filament\Infolists\Components\TextEntry::make('notes')
                            ->label('Dispatch Notes')
                            
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('dispatch_ref')
            ->columns([
                TextColumn::make('dispatch_ref')
                    ->label('Ref')
                    ->badge()
                    ->color('warning')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('booking.booking_ref')
                    ->label('Booking')
                    ->badge()
                    ->color(fn ($record) => $record->booking?->type === 'partner' ? 'purple' : 'info')
                    ->searchable(),

                TextColumn::make('flight_date')
                    ->label('Flight Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('pickup_time')
                    ->label('Pickup')
                    ->time('H:i'),

                TextColumn::make('total_pax')
                    ->label('PAX')
                    ->alignCenter(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed'   => 'success',
                        'in_progress' => 'warning',
                        'delivered'   => 'info',
                        'cancelled'   => 'danger',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in_progress' => 'In Progress',
                        default       => ucfirst($state),
                    }),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'     => 'Pending',
                        'confirmed'   => 'Confirmed',
                        'in_progress' => 'In Progress',
                        'delivered'   => 'Delivered',
                        'cancelled'   => 'Cancelled',
                    ]),
            ])
            ->actions([
                ViewAction::make()
                    ->modalWidth('full'),
                EditAction::make()->label('Update Status'),
            ])
            ->defaultSort('flight_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Driver\Resources\Dispatches\Pages\ManageDispatches::route('/'),
        ];
    }
}
