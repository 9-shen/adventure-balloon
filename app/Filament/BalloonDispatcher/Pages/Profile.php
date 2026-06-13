<?php

namespace App\Filament\BalloonDispatcher\Pages;

use App\Models\BalloonDispatcher as BalloonDispatcherModel;
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

    protected string $view = 'filament.balloon-dispatcher.pages.profile';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-user-circle';
    }

    public static function getNavigationLabel(): string { return 'My Profile'; }
    public function getTitle(): string { return 'My Profile'; }
    public static function getNavigationSort(): ?int { return 20; }

    public ?array $data = [];

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->form->fill([
            'name'                     => $user->name,
            'phone'                    => $user->phone,
            'email'                    => $user->email,
            'current_password'         => '',
            'new_password'             => '',
            'new_password_confirmation' => '',
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
                                ->label('Phone Number')
                                ->tel()
                                ->required()
                                ->maxLength(50)
                                ->placeholder('+212669611393 | Country Code | Number'),

                            TextInput::make('email')
                                ->label('Email Address')
                                ->email()
                                ->disabled()
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

        $user->name = $data['name'];
        $user->phone = $data['phone'] ?? null;

        // Sync back to BalloonDispatcher record
        if ($user->balloon_dispatcher_id) {
            BalloonDispatcherModel::where('id', $user->balloon_dispatcher_id)
                ->update([
                    'name' => $data['name'],
                    'phone' => $data['phone'] ?? null,
                ]);
        }

        if (! empty($data['new_password'])) {
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
