<?php

namespace App\Filament\Admin\Resources\TransportCompanies\Pages;

use App\Filament\Admin\Resources\TransportCompanies\TransportCompanyResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateTransportCompany extends CreateRecord
{
    protected static string $resource = TransportCompanyResource::class;

    /**
     * Strip the virtual portal_user_id field before inserting the TransportCompany record.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->portalUserId = $data['portal_user_id'] ?? null;
        unset($data['portal_user_id']);
        return $data;
    }

    /**
     * After the TransportCompany is saved, link the selected user.
     */
    protected function afterCreate(): void
    {
        if (!empty($this->portalUserId)) {
            // Un-link any previously linked user for safety
            User::where('transport_company_id', $this->getRecord()->id)
                ->update(['transport_company_id' => null]);

            // Link the newly selected user
            User::where('id', $this->portalUserId)
                ->update(['transport_company_id' => $this->getRecord()->id]);
        }
    }
}
