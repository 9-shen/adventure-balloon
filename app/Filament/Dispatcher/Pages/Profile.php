<?php

namespace App\Filament\Dispatcher\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class Profile extends Page implements HasForms
{
    use InteractsWithForms;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-user';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Operations';
    }

    public static function getNavigationSort(): ?int
    {
        return 100;
    }

    public function getTitle(): string
    {
        return 'My Profile';
    }

    protected string $view = 'filament.dispatcher.pages.profile';

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();

        $this->form->fill([
            'name'          => $user->name,
            'email'         => $user->email,
            'phone'         => $user->phone,
            'national_id'   => $user->national_id,
            'nationality'   => $user->nationality,
            'date_of_birth' => $user->date_of_birth,
            'address'       => $user->address,
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
                    ->description('Update your account details.')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('avatar')
                            ->collection('avatar')
                            ->avatar()
                            ->alignCenter()
                            ->columnSpanFull(),

                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('email')
                                ->email()
                                ->disabled()
                                ->maxLength(255),

                            TextInput::make('phone')
                                ->tel()
                                ->required()
                                ->maxLength(50)
                                ->placeholder('+212669611393 | Country Code | Number'),

                            TextInput::make('national_id')
                                ->label('National ID / Passport')
                                ->maxLength(50),

                            TextInput::make('nationality')
                                ->maxLength(50),

                            DatePicker::make('date_of_birth')
                                ->maxDate(now()->subYears(18)),

                            Textarea::make('address')
                                ->rows(3)
                                ->columnSpanFull(),
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
                                ->rules(['required_with:new_password', 'current_password']),

                            TextInput::make('new_password')
                                ->label('New Password')
                                ->password()
                                ->revealable()
                                ->rules(['required_with:current_password', 'min:8', 'confirmed']),

                            TextInput::make('new_password_confirmation')
                                ->label('Confirm New Password')
                                ->password()
                                ->revealable()
                                ->rules(['required_with:new_password']),
                        ]),
                    ]),
            ])
            ->statePath('data')
            ->model(Auth::user());
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Profile')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $state = $this->form->getState();

        $user->update([
            'name'          => $state['name'],
            'phone'         => $state['phone'] ?? null,
            'national_id'   => $state['national_id'] ?? null,
            'nationality'   => $state['nationality'] ?? null,
            'date_of_birth' => $state['date_of_birth'] ?? null,
            'address'       => $state['address'] ?? null,
        ]);

        if (! empty($state['new_password'])) {
            $user->update([
                'password' => Hash::make($state['new_password']),
            ]);

            $this->data['current_password'] = null;
            $this->data['new_password'] = null;
            $this->data['new_password_confirmation'] = null;
        }

        Notification::make()
            ->title('Profile updated successfully')
            ->success()
            ->send();
    }
}
