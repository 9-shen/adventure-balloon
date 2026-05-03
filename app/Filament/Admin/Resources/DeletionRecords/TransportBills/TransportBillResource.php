<?php

namespace App\Filament\Admin\Resources\DeletionRecords\TransportBills;

use App\Filament\Admin\Resources\DeletionRecords\TransportBills\Pages\CreateTransportBill;
use App\Filament\Admin\Resources\DeletionRecords\TransportBills\Pages\EditTransportBill;
use App\Filament\Admin\Resources\DeletionRecords\TransportBills\Pages\ListTransportBills;
use App\Filament\Admin\Resources\DeletionRecords\TransportBills\Schemas\TransportBillForm;
use App\Filament\Admin\Resources\DeletionRecords\TransportBills\Tables\TransportBillsTable;
use App\Models\DeletionRecords\TransportBill;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransportBillResource extends Resource
{
    protected static ?string $model = TransportBill::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TransportBillForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransportBillsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransportBills::route('/'),
            'create' => CreateTransportBill::route('/create'),
            'edit' => EditTransportBill::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

