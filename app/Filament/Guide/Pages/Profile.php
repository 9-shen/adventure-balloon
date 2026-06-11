<?php

namespace App\Filament\Guide\Pages;

use App\Models\Guide;
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

    protected string $view = 'filament.guide.pages.profile';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-user-circle';
    }

    public static function getNavigationLabel(): string
    {
        return 'My Profile';
    }

    public function getTitle(): string
    {
        return 'My Profile';
    }

    public static function getNavigationSort(): ?int
    {
        return 20;
    }

    public ?array $data = [];

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->form->fill([
            'name'             => $user->name,
            'email'            => $user->email,
            'phone'            => $user->phone,
            'current_password' => '',
            'new_password'     => '',
            'new_password_confirmation' => '',
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Personal Information')
                    ->description('Update your display name, email address, and phone number.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Full Name')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            TextInput::make('email')
                                ->label('Email Address')
                                ->email()
                                ->required()
                                ->maxLength(255),

                            TextInput::make('phone')
                                ->label('Phone Number')
                                ->tel()
                                ->maxLength(50)
                                ->placeholder('+212669611393 | Country Code | Number'),
                        ]),
                    ]),

                Section::make('Change Password')
                    ->description('Leave blank if you do not want to change your password.')
                    ->schema([
                        Grid::make(1)->schema([
                            TextInput::make('current_password')
                                ->label('Current Password')
                                ->password()
                                ->revealable()
                                ->currentPassword()
                                ->dehydrated(false),

                            TextInput::make('new_password')
                                ->label('New Password')
                                ->password()
                                ->revealable()
                                ->minLength(8)
                                ->rules(['min:8'])
                                ->rule(Password::defaults())
                                ->confirmed()
                                ->dehydrated(false),

                            TextInput::make('new_password_confirmation')
                                ->label('Confirm New Password')
                                ->password()
                                ->revealable()
                                ->dehydrated(false),
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

        $data = $this->form->getState();

        // Update personal info
        $user->name  = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;

        // Update Guide record if it exists
        if ($user->guide_id) {
            Guide::where('id', $user->guide_id)->update([
                'name'  => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
            ]);
        }

        // Change password only if a new one is supplied
        if (!empty($data['new_password'])) {
            $user->password = Hash::make($data['new_password']);
        }

        $user->save();

        Notification::make()
            ->title('Profile Updated')
            ->body('Your profile has been saved successfully.')
            ->success()
            ->send();
    }
}
