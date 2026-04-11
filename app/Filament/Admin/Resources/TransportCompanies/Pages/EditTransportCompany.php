<?php

namespace App\Filament\Admin\Resources\TransportCompanies\Pages;

use App\Filament\Admin\Resources\TransportCompanies\TransportCompanyResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditTransportCompany extends EditRecord
{
    protected static string $resource = TransportCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
        $linkedUser = User::where('transport_company_id', $this->getRecord()->id)->first();
        $data['portal_user_id'] = $linkedUser?->id;
        return $data;
    }

    /**
     * Strip the virtual portal_user_id field before updating the TransportCompany record.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->portalUserId = $data['portal_user_id'] ?? null;
        unset($data['portal_user_id']);
        return $data;
    }

    /**
     * After TransportCompany is saved, update the user linkage.
     */
    protected function afterSave(): void
    {
        $companyId = $this->getRecord()->id;

        // First: un-link ALL users currently linked to this company
        User::where('transport_company_id', $companyId)
            ->update(['transport_company_id' => null]);

        // Then: link the newly selected user (if any)
        if (!empty($this->portalUserId)) {
            User::where('id', $this->portalUserId)
                ->update(['transport_company_id' => $companyId]);
        }
    }
}
