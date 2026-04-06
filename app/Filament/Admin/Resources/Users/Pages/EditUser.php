<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            RestoreAction::make(),
            DeleteAction::make()
                ->hidden(fn () => $this->record?->id === Auth::id())
                ->hidden(fn () => $this->record?->hasRole('super_admin') && User::role('super_admin')->count() <= 1),
            ForceDeleteAction::make()
                ->hidden(fn () => $this->record?->id === Auth::id()),
        ];
    }
}
