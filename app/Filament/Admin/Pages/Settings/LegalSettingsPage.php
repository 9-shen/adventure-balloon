<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Settings\LegalSettings;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

class LegalSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;
    protected string $view = 'filament.admin.pages.settings.legal-settings-page';

    public static function getNavigationIcon(): string|null
    {
        return 'heroicon-o-scale';
    }

    public static function getNavigationLabel(): string
    {
        return 'Legal Info';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public function getTitle(): \Illuminate\Contracts\Support\Htmlable|string
    {
        return 'Legal Information';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(LegalSettings::class);

        $this->form->fill([
            'identifiant_fiscal' => $settings->identifiant_fiscal,
            'cnss_number'        => $settings->cnss_number,
            'patente_number'     => $settings->patente_number,
            'registre_commerce'  => $settings->registre_commerce,
            'ice_number'         => $settings->ice_number,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Moroccan Legal Identifiers')
                    ->description('These identifiers appear on all official documents and invoices.')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('identifiant_fiscal')
                                ->label('Identifiant Fiscal (IF)')
                                ->placeholder('e.g. 12345678')
                                ->maxLength(50),

                            TextInput::make('cnss_number')
                                ->label('N° CNSS')
                                ->placeholder('e.g. 9876543')
                                ->maxLength(50),

                            TextInput::make('patente_number')
                                ->label('N° Patente')
                                ->placeholder('e.g. 47856321')
                                ->maxLength(50),

                            TextInput::make('registre_commerce')
                                ->label('Registre de Commerce (RC)')
                                ->placeholder('e.g. 123456 Marrakech')
                                ->maxLength(100),

                            TextInput::make('ice_number')
                                ->label('ICE (Identifiant Commun de l\'Entreprise)')
                                ->placeholder('e.g. 001234567000012')
                                ->maxLength(15)
                                ->helperText('15-digit Moroccan unified business identifier'),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Legal Info')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = app(LegalSettings::class);

        $settings->identifiant_fiscal = $data['identifiant_fiscal'] ?? null;
        $settings->cnss_number        = $data['cnss_number'] ?? null;
        $settings->patente_number     = $data['patente_number'] ?? null;
        $settings->registre_commerce  = $data['registre_commerce'] ?? null;
        $settings->ice_number         = $data['ice_number'] ?? null;
        $settings->save();

        Notification::make()
            ->title('Legal information saved!')
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








