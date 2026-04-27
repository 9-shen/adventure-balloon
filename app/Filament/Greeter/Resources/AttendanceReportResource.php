<?php

namespace App\Filament\Greeter\Resources;

use App\Models\Booking;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Filament\Greeter\Resources\AttendanceReportResource\Pages;

class AttendanceReportResource extends Resource
{
    protected static ?string $model = Booking::class;
    
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
        return 'Attendance Reports';
    }
    
    public static function getModelLabel(): string
    {
        return 'Attendance Report';
    }
    
    public static function getPluralModelLabel(): string
    {
        return 'Attendance Reports';
    }
    
    protected static ?int $navigationSort = 2;
    
    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin', 'greeter']) ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_ref')
                    ->label('Ref')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'partner' ? 'purple' : 'info')
                    ->formatStateUsing(fn (string $state): string => $state === 'partner' ? '🤝 Partner' : '✈️ Regular'),

                TextColumn::make('flight_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('flight_time')
                    ->label('Time')
                    ->time('H:i')
                    ->placeholder('—'),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->limit(25),

                TextColumn::make('adult_pax')
                    ->label('Adults')
                    ->alignCenter(),

                TextColumn::make('child_pax')
                    ->label('Children')
                    ->alignCenter(),

                TextColumn::make('booking_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'info',
                        default     => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('pax_attendance')
                    ->label('PAX Attendance')
                    ->getStateUsing(fn (Booking $record): string => $record->getPaxAttendanceLabel())
                    ->badge()
                    ->color(fn (Booking $record): string => match (true) {
                        $record->customers->isEmpty()                                                               => 'gray',
                        $record->customers->where('attendance', 'show')->count() === $record->customers->count()    => 'success',
                        $record->customers->where('attendance', 'pending')->count() === $record->customers->count() => 'gray',
                        default => 'warning',
                    }),
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

                SelectFilter::make('type')
                    ->label('Booking Type')
                    ->options([
                        'regular' => 'Regular',
                        'partner' => 'Partner',
                    ]),

                SelectFilter::make('booking_status')
                    ->options([
                        'pending'     => 'Pending',
                        'confirmed'   => 'Confirmed',
                        'completed'   => 'Completed',
                        'cancelled'   => 'Cancelled',
                    ]),
            ])
            ->actions([])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('export_csv')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $query = \App\Models\Booking::query()->whereIn('id', $records->pluck('id'));
                            return \Maatwebsite\Excel\Facades\Excel::download(
                                new \App\Exports\AttendanceReportQueryExport($query),
                                'attendance_reports_selected.csv',
                                \Maatwebsite\Excel\Excel::CSV
                            );
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendanceReports::route('/'),
        ];
    }
}
