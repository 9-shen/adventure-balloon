<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Settings\PaxSettings;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

class PaxSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;
    protected string $view = 'filament.admin.pages.settings.pax-settings-page';

    public static function getNavigationIcon(): string|null
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationLabel(): string
    {
        return 'PAX Capacity';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public function getTitle(): \Illuminate\Contracts\Support\Htmlable|string
    {
        return 'PAX Settings';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(PaxSettings::class);

        $this->form->fill([
            'daily_pax_capacity' => $settings->daily_pax_capacity,
            'warning_threshold'  => $settings->warning_threshold,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Daily PAX Capacity')
                    ->description('Control the maximum number of passengers per day and configure the dashboard alert threshold.')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('daily_pax_capacity')
                                ->label('Daily PAX Capacity')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(9999)
                                ->required()
                                ->suffix('passengers')
                                ->helperText('Maximum total passengers allowed per day across all bookings.'),

                            TextInput::make('warning_threshold')
                                ->label('Warning Threshold')
                                ->numeric()
                                ->minValue(1)
                                ->required()
                                ->suffix('remaining PAX')
                                ->helperText('Show a dashboard alert when remaining daily capacity drops to or below this number.'),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Capacity Settings')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = app(PaxSettings::class);

        $settings->daily_pax_capacity = (int) $data['daily_pax_capacity'];
        $settings->warning_threshold  = (int) $data['warning_threshold'];
        $settings->save();

        Notification::make()
            ->title('PAX capacity settings saved!')
            ->success()
            ->send();
    }

    public static function getNavigationGroup(): string|null
    {
        return 'Settings';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}








