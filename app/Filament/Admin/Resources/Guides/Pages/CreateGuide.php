<?php

namespace App\Filament\Admin\Resources\Guides\Pages;

use App\Filament\Admin\Resources\Guides\GuideResource;
use App\Models\User;
use App\Notifications\GuideAccountCreatedNotification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CreateGuide extends CreateRecord
{
    protected static string $resource = GuideResource::class;


}
