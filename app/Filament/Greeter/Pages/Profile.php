<?php

namespace App\Filament\Greeter\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class Profile extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.greeter.pages.profile';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-identification';
    }

    public static function getNavigationLabel(): string
    {
        return 'My Profile';
    }

    public function getTitle(): string
    {
        return 'Profile Settings';
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

        $this->form->fill([
            'name'  => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Personal Information')
                    ->description('Update your contact details.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Full Name')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('phone')
                                ->label('Phone / WhatsApp')
                                ->tel()
                                ->required()
                                ->maxLength(50),

                            TextInput::make('email')
                                ->label('Login Email')
                                ->email()
                                ->disabled()
                                ->helperText('Contact the admin to change your email.'),
                        ]),
                    ]),

                Section::make('Change Password')
                    ->description('Update the password used to log in.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('new_password')
                                ->label('New Password')
                                ->password()
                                ->rule(Password::default())
                                ->requiredWith('new_password_confirmation'),

                            TextInput::make('new_password_confirmation')
                                ->label('Confirm New Password')
                                ->password()
                                ->same('new_password')
                                ->requiredWith('new_password'),
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

        $validated = $this->form->getState();

        // Update User
        $user->update([
            'name'  => $validated['name'],
            'phone' => $validated['phone'],
        ]);

        if (!empty($validated['new_password'])) {
            $user->update([
                'password' => Hash::make($validated['new_password']),
            ]);
            
            // Clear passwords from form state
            $this->data['new_password'] = null;
            $this->data['new_password_confirmation'] = null;
        }

        Notification::make()
            ->title('Profile Updated')
            ->body('Your profile has been saved successfully.')
            ->success()
            ->send();
    }
}
