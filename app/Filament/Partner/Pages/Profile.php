<?php

namespace App\Filament\Partner\Pages;

use App\Models\Partner;
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

    // Non-static instance property (matches Filament v4 Page base class)
    protected string $view = 'filament.partner.pages.profile';

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
        return 'Partner Profile';
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
        $partner = Partner::findOrFail($user->partner_id);

        $this->form->fill([
            'company_name'        => $partner->company_name,
            'trade_name'          => $partner->trade_name,
            'email'               => $partner->email,
            'phone'               => $partner->phone,
            'address'             => $partner->address,
            'city'                => $partner->city,
            'country'             => $partner->country,
            'bank_name'           => $partner->bank_name,
            'bank_account'        => $partner->bank_account,
            'bank_iban'           => $partner->bank_iban,
            'bank_swift'          => $partner->bank_swift,
            'payment_terms_days'  => $partner->payment_terms_days,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Company Information')
                    ->description('Your company contact and address details.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('company_name')
                                ->label('Company Name')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            TextInput::make('trade_name')
                                ->label('Trade / Commercial Name')
                                ->maxLength(255),

                            TextInput::make('email')
                                ->label('Company Email')
                                ->email()
                                ->maxLength(255),

                            TextInput::make('phone')
                                ->label('Phone')
                                ->tel()
                                ->maxLength(50),

                            TextInput::make('city')
                                ->maxLength(100),

                            TextInput::make('country')
                                ->maxLength(100),

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

                            TextInput::make('bank_swift')
                                ->label('SWIFT / BIC')
                                ->maxLength(50),

                            TextInput::make('payment_terms_days')
                                ->label('Payment Terms (days)')
                                ->numeric()
                                ->minValue(0)
                                ->suffix('days'),
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
        $partner = Partner::findOrFail($user->partner_id);

        $validated = $this->form->getState();

        $partner->update([
            'company_name'       => $validated['company_name'],
            'trade_name'         => $validated['trade_name'] ?? null,
            'email'              => $validated['email'] ?? null,
            'phone'              => $validated['phone'] ?? null,
            'address'            => $validated['address'] ?? null,
            'city'               => $validated['city'] ?? null,
            'country'            => $validated['country'] ?? null,
            'bank_name'          => $validated['bank_name'] ?? null,
            'bank_account'       => $validated['bank_account'] ?? null,
            'bank_iban'          => $validated['bank_iban'] ?? null,
            'bank_swift'         => $validated['bank_swift'] ?? null,
            'payment_terms_days' => $validated['payment_terms_days'] ?? 30,
        ]);

        Notification::make()
            ->title('Profile Updated')
            ->body('Your company profile has been saved successfully.')
            ->success()
            ->send();
    }
}
