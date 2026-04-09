<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class AccountantRecentPaymentsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'accountant']) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->where('amount_paid', '>', 0)
                    ->latest('updated_at')
                    ->limit(5)
            )
            ->heading('Recent Payment Activity')
            ->columns([
                Tables\Columns\TextColumn::make('booking_ref')
                    ->label('Reference')
                    ->weight('bold')
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('adult_total')
                    ->label('PAX')
                    ->getStateUsing(fn (Booking $record) => $record->adult_pax . ' Adult(s)' . ($record->child_pax > 0 ? ', ' . $record->child_pax . ' Child(ren)' : '')),
                    
                Tables\Columns\TextColumn::make('final_amount')
                    ->label('Total Price')
                    ->money('MAD'),
                    
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Amount Paid')
                    ->money('MAD')
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'due'     => 'danger',
                        'partial' => 'warning',
                        'on_site' => 'info',
                        'paid'    => 'success',
                        default   => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}
