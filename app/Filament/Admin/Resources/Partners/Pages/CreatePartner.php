<?php

namespace App\Filament\Admin\Resources\Partners\Pages;

use App\Filament\Admin\Resources\Partners\PartnerResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreatePartner extends CreateRecord
{
    protected static string $resource = PartnerResource::class;

    /**
     * Strip the virtual portal_user_id field before inserting the Partner record.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->portalUserId = $data['portal_user_id'] ?? null;
        unset($data['portal_user_id']);
        return $data;
    }

    /**
     * After the Partner is saved, link the selected user to this Partner.
     */
    protected function afterCreate(): void
    {
        if (!empty($this->portalUserId)) {
            // Un-link any previously linked user for safety
            User::where('partner_id', $this->getRecord()->id)
                ->update(['partner_id' => null]);

            // Link the newly selected user
            User::where('id', $this->portalUserId)
                ->update(['partner_id' => $this->getRecord()->id]);
        }
    }
}
