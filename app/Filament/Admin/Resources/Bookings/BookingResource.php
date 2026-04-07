<?php

namespace App\Filament\Admin\Resources\Bookings;

use App\Filament\Admin\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Admin\Resources\Bookings\Pages\EditBooking;
use App\Filament\Admin\Resources\Bookings\Pages\ListBookings;
use App\Filament\Admin\Resources\Bookings\Pages\ViewBooking;
use App\Filament\Admin\Resources\Bookings\RelationManagers\BookingCustomersRelationManager;
use App\Filament\Admin\Resources\Bookings\Schemas\BookingEditForm;
use App\Filament\Admin\Resources\Bookings\Tables\BookingsTable;
use App\Models\Booking;
use BackedEnum;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedCalendarDays;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Bookings';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return 'booking_ref';
    }

    // ─── Access Control ───────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }

    // ─── Form (Edit page) ─────────────────────────────────────────────────────

    public static function form(Schema $form): Schema
    {
        return BookingEditForm::configure($form);
    }

    // ─── Infolist (View page) ─────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Booking Details')
                ->columns(3)
                ->components([
                    TextEntry::make('booking_ref')
                        ->label('Reference')
                        ->badge()
                        ->color('primary')
                        ->copyable(),

                    TextEntry::make('product.name')
                        ->label('Product'),

                    TextEntry::make('booking_status')
                        ->label('Status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'confirmed' => 'success',
                            'cancelled' => 'danger',
                            'completed' => 'info',
                            default     => 'warning',
                        })
                        ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                    TextEntry::make('flight_date')
                        ->label('Flight Date')
                        ->date('d/m/Y'),

                    TextEntry::make('flight_time')
                        ->label('Flight Time'),

                    TextEntry::make('booking_source')
                        ->label('Source')
                        ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? '—')),
                ]),

            Section::make('Passengers')
                ->columns(3)
                ->components([
                    TextEntry::make('adult_pax')
                        ->label('Adults'),

                    TextEntry::make('child_pax')
                        ->label('Children'),

                    TextEntry::make('adult_pax')
                        ->label('Total PAX')
                        ->formatStateUsing(fn ($state, Booking $record): string =>
                            (string) $record->getTotalPax()
                        ),
                ]),

            Section::make('Pricing')
                ->columns(3)
                ->components([
                    TextEntry::make('base_adult_price')
                        ->label('Adult Price (each)')
                        ->money('MAD'),

                    TextEntry::make('base_child_price')
                        ->label('Child Price (each)')
                        ->money('MAD'),

                    TextEntry::make('adult_total')
                        ->label('Adult Total')
                        ->money('MAD'),

                    TextEntry::make('child_total')
                        ->label('Child Total')
                        ->money('MAD'),

                    TextEntry::make('discount_amount')
                        ->label('Discount (MAD)')
                        ->money('MAD'),

                    TextEntry::make('final_amount')
                        ->label('Final Amount')
                        ->money('MAD'),
                ]),

            Section::make('Payment')
                ->columns(3)
                ->components([
                    TextEntry::make('payment_method')
                        ->label('Method')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'cash'   => 'Cash',
                            'wire'   => 'Wire Transfer',
                            'online' => 'Online',
                            default  => ucfirst($state),
                        }),

                    TextEntry::make('payment_status')
                        ->label('Payment Status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'paid'    => 'success',
                            'partial' => 'warning',
                            'on_site' => 'info',
                            default   => 'danger',
                        })
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'paid'    => 'Paid',
                            'partial' => 'Partial',
                            'on_site' => 'On-Site',
                            default   => 'Due',
                        }),

                    TextEntry::make('amount_paid')
                        ->label('Amount Paid')
                        ->money('MAD'),

                    TextEntry::make('balance_due')
                        ->label('Balance Due')
                        ->money('MAD'),
                ]),

            Section::make('Notes & Audit')
                ->columns(2)
                ->components([
                    TextEntry::make('notes')
                        ->label('Internal Notes')
                        ->placeholder('No notes.')
                        ->columnSpan(2),

                    TextEntry::make('cancelled_reason')
                        ->label('Cancellation Reason')
                        ->placeholder('—')
                        ->columnSpan(2),

                    TextEntry::make('createdBy.name')
                        ->label('Created By'),

                    TextEntry::make('created_at')
                        ->label('Created At')
                        ->dateTime('d/m/Y H:i'),

                    TextEntry::make('confirmedBy.name')
                        ->label('Confirmed By'),

                    TextEntry::make('confirmed_at')
                        ->label('Confirmed At')
                        ->dateTime('d/m/Y H:i'),
                ]),
        ]);
    }

    // ─── Table ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
    }

    // ─── Relation Managers ────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [
            BookingCustomersRelationManager::class,
        ];
    }

    // ─── Soft Delete Scope ────────────────────────────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
                     ->withoutGlobalScope(SoftDeletingScope::class);
    }

    // ─── Pages ────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListBookings::route('/'),
            'create' => CreateBooking::route('/create'),
            'edit'   => EditBooking::route('/{record}/edit'),
            'view'   => ViewBooking::route('/{record}'),
        ];
    }
}
