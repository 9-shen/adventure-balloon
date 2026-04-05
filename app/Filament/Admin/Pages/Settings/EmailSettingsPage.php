<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Settings\EmailSettings;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Facades\Mail;

class EmailSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;
    protected string $view = 'filament.admin.pages.settings.email-settings-page';

    public static function getNavigationIcon(): string|null
    {
        return 'heroicon-o-envelope';
    }

    public static function getNavigationLabel(): string
    {
        return 'Email (SMTP)';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public function getTitle(): \Illuminate\Contracts\Support\Htmlable|string
    {
        return 'Email Configuration';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(EmailSettings::class);

        $this->form->fill([
            'host'         => $settings->host,
            'port'         => $settings->port,
            'username'     => $settings->username,
            'password'     => $settings->password,
            'encryption'   => $settings->encryption,
            'from_address' => $settings->from_address,
            'from_name'    => $settings->from_name,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('SMTP Configuration')
                    ->description('Configure your outgoing mail server. Changes take effect immediately.')
                    ->icon('heroicon-o-server')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('host')
                                ->label('SMTP Host')
                                ->required()
                                ->placeholder('e.g. smtp.gmail.com'),

                            TextInput::make('port')
                                ->label('SMTP Port')
                                ->numeric()
                                ->required()
                                ->placeholder('e.g. 587'),

                            TextInput::make('username')
                                ->label('Username')
                                ->placeholder('your@email.com'),

                            TextInput::make('password')
                                ->label('Password')
                                ->password()
                                ->revealable(),

                            Select::make('encryption')
                                ->label('Encryption')
                                ->options([
                                    'tls'  => 'TLS',
                                    'ssl'  => 'SSL',
                                    'none' => 'None',
                                ])
                                ->required(),
                        ]),
                    ]),

                Section::make('From Address')
                    ->description('This name and address will appear in all outgoing emails.')
                    ->icon('heroicon-o-at-symbol')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('from_address')
                                ->label('From Email Address')
                                ->email()
                                ->required(),

                            TextInput::make('from_name')
                                ->label('From Name')
                                ->required()
                                ->placeholder('e.g. Booklix'),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Email Settings')
                ->icon('heroicon-o-check')
                ->action('save'),

            Action::make('sendTest')
                ->label('Send Test Email')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Send Test Email')
                ->modalDescription('This will send a test email to your account email using the current saved settings.')
                ->action('sendTestEmail'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = app(EmailSettings::class);

        $settings->host         = $data['host'];
        $settings->port         = (int) $data['port'];
        $settings->username     = $data['username'] ?? null;
        $settings->password     = $data['password'] ?? null;
        $settings->encryption   = $data['encryption'];
        $settings->from_address = $data['from_address'];
        $settings->from_name    = $data['from_name'];
        $settings->save();

        Notification::make()
            ->title('Email settings saved!')
            ->success()
            ->send();
    }

    public function sendTestEmail(): void
    {
        try {
            $settings = app(EmailSettings::class);

            // Apply settings at runtime
            config([
                'mail.mailers.smtp.host'       => $settings->host,
                'mail.mailers.smtp.port'        => $settings->port,
                'mail.mailers.smtp.username'    => $settings->username,
                'mail.mailers.smtp.password'    => $settings->password,
                'mail.mailers.smtp.encryption'  => $settings->encryption,
                'mail.from.address'             => $settings->from_address,
                'mail.from.name'                => $settings->from_name,
            ]);

            $recipient = auth()->user()->email;

            Mail::raw('This is a test email from Booklix. Your SMTP settings are working correctly!', function ($msg) use ($recipient, $settings) {
                $msg->to($recipient)->subject('Booklix — Email Test');
            });

            Notification::make()
                ->title("Test email sent to {$recipient}")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to send test email')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
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








