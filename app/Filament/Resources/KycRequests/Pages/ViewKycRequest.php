<?php

namespace App\Filament\Resources\KycRequests\Pages;

use App\Filament\Resources\KycRequests\KycRequestResource;
use Filament\Resources\Pages\ViewRecord;

class ViewKycRequest extends ViewRecord
{
    protected static string $resource = KycRequestResource::class;

    protected function getHeaderActions(): array
    {
        return KycRequestResource::reviewActions();
    }
}
