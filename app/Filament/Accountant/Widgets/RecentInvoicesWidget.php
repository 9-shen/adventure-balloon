<?php

namespace App\Filament\Accountant\Widgets;

use App\Filament\Accountant\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentInvoicesWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                InvoiceResource::getEloquentQuery()
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_ref')
                    ->label('Invoice #')
                    ->weight('bold')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('partner.company_name')
                    ->label('Partner'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('MAD')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'   => 'gray',
                        'sent'    => 'info',
                        'paid'    => 'success',
                        'overdue' => 'danger',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->date('d/m/Y'),
            ])
            ->actions([
                \Filament\Actions\Action::make('view')
                    ->url(fn (Invoice $record): string => InvoiceResource::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-m-eye'),
            ])
            ->paginated(false);
    }
}
