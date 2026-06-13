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

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $record->update($data);

        return $record;
    }

    protected function afterSave(): void
    {
        $user = $this->record;
        $user->refresh();

        if ($user->hasRole('guide')) {
            $guide = null;
            if ($user->guide_id) {
                $guide = \App\Models\Guide::find($user->guide_id);
            }
            if (!$guide) {
                $guide = \App\Models\Guide::where('email', $user->email)->first();
            }

            if (!$guide) {
                $count = \App\Models\Guide::withTrashed()->count() + 1;
                $guideRef = 'GD-' . str_pad($count, 3, '0', STR_PAD_LEFT);

                $guide = \App\Models\Guide::create([
                    'partner_id' => $user->partner_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'guide_reference' => $guideRef,
                    'is_active' => $user->is_active,
                ]);
            } else {
                $guide->update([
                    'partner_id' => $user->partner_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'is_active' => $user->is_active,
                ]);
            }

            if ($user->guide_id !== $guide->id) {
                $user->guide_id = $guide->id;
                $user->saveQuietly();
            }
        } else {
            if ($user->guide_id !== null) {
                $user->guide_id = null;
                $user->saveQuietly();
            }
        }
    }
}
