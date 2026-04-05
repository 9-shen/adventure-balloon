<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Settings\WhatsAppSettings;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

class WhatsAppSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;
    protected string $view = 'filament.admin.pages.settings.whats-app-settings-page';

    public static function getNavigationIcon(): string|null
    {
        return 'heroicon-o-chat-bubble-left-ellipsis';
    }

    public static function getNavigationLabel(): string
    {
        return 'WhatsApp';
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
    }

    public function getTitle(): \Illuminate\Contracts\Support\Htmlable|string
    {
        return 'WhatsApp (Twilio)';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(WhatsAppSettings::class);

        $this->form->fill([
            'account_sid' => $settings->account_sid,
            'auth_token'  => $settings->auth_token,
            'from_number' => $settings->from_number,
            'enabled'     => $settings->enabled,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Twilio Configuration')
                    ->description('Configure Twilio API credentials for WhatsApp notifications sent to drivers and customers.')
                    ->icon('heroicon-o-key')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Enable WhatsApp Notifications')
                            ->helperText('When disabled, no WhatsApp messages will be sent.')
                            ->columnSpanFull(),

                        Grid::make(2)->schema([
                            TextInput::make('account_sid')
                                ->label('Account SID')
                                ->placeholder('ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx')
                                ->password()
                                ->revealable()
                                ->maxLength(100),

                            TextInput::make('auth_token')
                                ->label('Auth Token')
                                ->password()
                                ->revealable()
                                ->maxLength(100),

                            TextInput::make('from_number')
                                ->label('From Number (WhatsApp)')
                                ->placeholder('whatsapp:+14155238886')
                                ->helperText('Use format: whatsapp:+[country][number]')
                                ->maxLength(50),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save WhatsApp Settings')
                ->icon('heroicon-o-check')
                ->action('save'),

            Action::make('sendTest')
                ->label('Send Test WhatsApp')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->form([
                    TextInput::make('test_number')
                        ->label('Send to Number')
                        ->placeholder('whatsapp:+212600000000')
                        ->required(),
                ])
                ->action('sendTestWhatsApp'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = app(WhatsAppSettings::class);

        $settings->account_sid = $data['account_sid'] ?? null;
        $settings->auth_token  = $data['auth_token'] ?? null;
        $settings->from_number = $data['from_number'] ?? null;
        $settings->enabled     = (bool) ($data['enabled'] ?? false);
        $settings->save();

        Notification::make()
            ->title('WhatsApp settings saved!')
            ->success()
            ->send();
    }

    public function sendTestWhatsApp(array $data): void
    {
        $settings = app(WhatsAppSettings::class);

        if (! $settings->enabled || ! $settings->account_sid) {
            Notification::make()
                ->title('WhatsApp is not configured or disabled.')
                ->warning()
                ->send();
            return;
        }

        try {
            $client = new \Twilio\Rest\Client($settings->account_sid, $settings->auth_token);
            $client->messages->create(
                $data['test_number'],
                [
                    'from' => $settings->from_number,
                    'body' => 'This is a test message from Booklix. WhatsApp integration is working!',
                ]
            );

            Notification::make()
                ->title('Test WhatsApp message sent successfully!')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to send WhatsApp message')
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








