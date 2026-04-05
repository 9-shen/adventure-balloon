<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Settings\BankSettings;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

class BankSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;
    protected string $view = 'filament.admin.pages.settings.bank-settings-page';

    public static function getNavigationIcon(): string|null
    {
        return 'heroicon-o-currency-dollar';
    }

    public static function getNavigationLabel(): string
    {
        return 'Bank Account';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public function getTitle(): \Illuminate\Contracts\Support\Htmlable|string
    {
        return 'Bank Details';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(BankSettings::class);

        $this->form->fill([
            'bank_name'        => $settings->bank_name,
            'bank_holder_name' => $settings->bank_holder_name,
            'bank_account'     => $settings->bank_account,
            'iban'             => $settings->iban,
            'swift'            => $settings->swift,
            'routing_number'   => $settings->routing_number,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Bank Account Details')
                    ->description('These details are printed on all invoices sent to partners.')
                    ->icon('heroicon-o-building-library')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('bank_name')
                                ->label('Bank Name')
                                ->placeholder('e.g. Attijariwafa Bank')
                                ->maxLength(255),

                            TextInput::make('bank_holder_name')
                                ->label('Account Holder Name')
                                ->placeholder('e.g. Booklix SARL')
                                ->maxLength(255),

                            TextInput::make('bank_account')
                                ->label('Bank Account Number')
                                ->maxLength(50),

                            TextInput::make('iban')
                                ->label('IBAN')
                                ->placeholder('e.g. MA64 XXXX XXXX XXXX XXXX XXXX XXX')
                                ->maxLength(34)
                                ->helperText('International Bank Account Number'),

                            TextInput::make('swift')
                                ->label('SWIFT / BIC')
                                ->placeholder('e.g. BCMAMAMCXXX')
                                ->maxLength(11)
                                ->helperText('8 or 11 character SWIFT code'),

                            TextInput::make('routing_number')
                                ->label('Routing Number')
                                ->maxLength(20)
                                ->helperText('Used for international wire transfers'),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Bank Details')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = app(BankSettings::class);

        $settings->bank_name        = $data['bank_name'] ?? null;
        $settings->bank_holder_name = $data['bank_holder_name'] ?? null;
        $settings->bank_account     = $data['bank_account'] ?? null;
        $settings->iban             = $data['iban'] ?? null;
        $settings->swift            = $data['swift'] ?? null;
        $settings->routing_number   = $data['routing_number'] ?? null;
        $settings->save();

        Notification::make()
            ->title('Bank details saved!')
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








