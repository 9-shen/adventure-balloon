<?php

namespace App\Filament\Dispatcher\Resources;

use App\Models\Dispatch;
use App\Models\TransportCompany;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Filament\Dispatcher\Resources\DispatchesReportResource\Pages;

class DispatchesReportResource extends Resource
{
    protected static ?string $model = Dispatch::class;
    
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-document-chart-bar';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Reports';
    }
    
    public static function getNavigationLabel(): string
    {
        return 'Dispatching Report';
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $managedPartnerIds = $user->managedPartners()->pluck('partners.id');
        
        return parent::getEloquentQuery()->whereHas('booking', function ($q) use ($managedPartnerIds) {
            $q->whereIn('partner_id', $managedPartnerIds);
        });
    }
    
    public static function getModelLabel(): string
    {
        return 'Dispatches Report';
    }
    
    public static function getPluralModelLabel(): string
    {
        return 'Dispatches Reports';
    }
    
    protected static ?int $navigationSort = 2;
    
    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasRole('dispatcher') ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
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

                TextColumn::make('transportCompany.company_name')
                    ->label('Transport Company')
                    ->searchable()
                    ->sortable(),

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

                TextColumn::make('notified_at')
                    ->label('Notified')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Not sent'),
            ])
            ->filters([
                Filter::make('flight_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('flight_from')
                            ->label('Flight Date From')
                            ->native(false),
                        \Filament\Forms\Components\DatePicker::make('flight_until')
                            ->label('Flight Date Until')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['flight_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('flight_date', '>=', $date),
                            )
                            ->when(
                                $data['flight_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('flight_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['flight_from'] ?? null) {
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('Flight from: ' . \Carbon\Carbon::parse($data['flight_from'])->toFormattedDateString())
                                ->removeField('flight_from');
                        }
                        if ($data['flight_until'] ?? null) {
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('Flight until: ' . \Carbon\Carbon::parse($data['flight_until'])->toFormattedDateString())
                                ->removeField('flight_until');
                        }
                        return $indicators;
                    }),

                SelectFilter::make('status')
                    ->options([
                        'pending'     => 'Pending',
                        'confirmed'   => 'Confirmed',
                        'in_progress' => 'In Progress',
                        'delivered'   => 'Delivered',
                        'cancelled'   => 'Cancelled',
                    ]),

                SelectFilter::make('transport_company_id')
                    ->label('Transport Company')
                    ->options(TransportCompany::where('is_active', true)->pluck('company_name', 'id'))
                    ->searchable(),
            ])
            ->actions([])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('export_csv')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $query = \App\Models\Dispatch::query()->whereIn('id', $records->pluck('id'));
                            return \Maatwebsite\Excel\Facades\Excel::download(
                                new \App\Exports\DispatchesReportQueryExport($query),
                                'dispatches_reports_selected.csv',
                                \Maatwebsite\Excel\Excel::CSV
                            );
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDispatchesReports::route('/'),
        ];
    }
}
