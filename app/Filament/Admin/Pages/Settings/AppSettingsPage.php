<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Settings\AppSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

class AppSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;
    protected string $view = 'filament.admin.pages.settings.app-settings-page';

    public static function getNavigationIcon(): string|null
    {
        return 'heroicon-o-building-office';
    }

    public static function getNavigationLabel(): string
    {
        return 'General';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public function getTitle(): \Illuminate\Contracts\Support\Htmlable|string
    {
        return 'General Settings';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(AppSettings::class);

        $this->form->fill([
            'company_name'    => $settings->company_name,
            'company_email'   => $settings->company_email,
            'company_phone'   => $settings->company_phone,
            'company_address' => $settings->company_address,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Company Information')
                    ->description('Your company\'s main contact and identity details.')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('company_name')
                                ->label('Company Name')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('company_email')
                                ->label('Company Email')
                                ->email()
                                ->required()
                                ->maxLength(255),

                            TextInput::make('company_phone')
                                ->label('Phone Number')
                                ->tel()
                                ->maxLength(50),
                        ]),

                        Textarea::make('company_address')
                            ->label('Company Address')
                            ->rows(3)
                            ->maxLength(500),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = app(AppSettings::class);

        $settings->company_name    = $data['company_name'];
        $settings->company_email   = $data['company_email'];
        $settings->company_phone   = $data['company_phone'] ?? '';
        $settings->company_address = $data['company_address'] ?? '';
        $settings->save();

        Notification::make()
            ->title('Settings saved successfully!')
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







