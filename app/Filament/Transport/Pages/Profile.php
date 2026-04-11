<?php

namespace App\Filament\Transport\Pages;

use App\Models\TransportCompany;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class Profile extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.transport.pages.profile';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-building-office-2';
    }

    public static function getNavigationLabel(): string
    {
        return 'Our Profile';
    }

    public function getTitle(): string
    {
        return 'Company Profile';
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public ?array $data = [];

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $company = TransportCompany::findOrFail($user->transport_company_id);

        $this->form->fill([
            'company_name'  => $company->company_name,
            'contact_name'  => $company->contact_name,
            'email'         => $company->email,
            'phone'         => $company->phone,
            'address'       => $company->address,
            'bank_name'     => $company->bank_name,
            'bank_account'  => $company->bank_account,
            'bank_iban'     => $company->bank_iban,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Company Information')
                    ->description('Your transport company contact and address details.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('company_name')
                                ->label('Company Name')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            TextInput::make('contact_name')
                                ->label('Contact Name')
                                ->maxLength(255),

                            TextInput::make('email')
                                ->label('Company Email')
                                ->email()
                                ->maxLength(255),

                            TextInput::make('phone')
                                ->label('Phone')
                                ->tel()
                                ->maxLength(50),

                            Textarea::make('address')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                    ]),

                Section::make('Banking Details')
                    ->description('Used for invoice and payment processing.')
                    ->collapsed()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('bank_name')
                                ->maxLength(255),

                            TextInput::make('bank_account')
                                ->label('Account Number')
                                ->maxLength(100),

                            TextInput::make('bank_iban')
                                ->label('IBAN')
                                ->maxLength(100),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Changes')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $company = TransportCompany::findOrFail($user->transport_company_id);

        $validated = $this->form->getState();

        $company->update([
            'company_name'  => $validated['company_name'],
            'contact_name'  => $validated['contact_name'] ?? null,
            'email'         => $validated['email'] ?? null,
            'phone'         => $validated['phone'] ?? null,
            'address'       => $validated['address'] ?? null,
            'bank_name'     => $validated['bank_name'] ?? null,
            'bank_account'  => $validated['bank_account'] ?? null,
            'bank_iban'     => $validated['bank_iban'] ?? null,
        ]);

        Notification::make()
            ->title('Profile Updated')
            ->body('Your company profile has been saved successfully.')
            ->success()
            ->send();
    }
}
