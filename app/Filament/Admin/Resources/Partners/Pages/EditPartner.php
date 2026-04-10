<?php

namespace App\Filament\Admin\Resources\Partners\Pages;

use App\Filament\Admin\Resources\Partners\PartnerResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPartner extends EditRecord
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            RestoreAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
        ];
    }

    /**
     * Inject the currently linked user ID into the form for display.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $linkedUser = User::where('partner_id', $this->getRecord()->id)->first();
        $data['portal_user_id'] = $linkedUser?->id;
        return $data;
    }

    /**
     * Strip the virtual portal_user_id field before updating the Partner record.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->portalUserId = $data['portal_user_id'] ?? null;
        unset($data['portal_user_id']);
        return $data;
    }

    /**
     * After Partner is saved, update the user linkage.
     */
    protected function afterSave(): void
    {
        $partnerId = $this->getRecord()->id;

        // First: un-link ALL users currently linked to this partner
        User::where('partner_id', $partnerId)->update(['partner_id' => null]);

        // Then: link the newly selected user (if any)
        if (!empty($this->portalUserId)) {
            User::where('id', $this->portalUserId)
                ->update(['partner_id' => $partnerId]);
        }
    }
}
