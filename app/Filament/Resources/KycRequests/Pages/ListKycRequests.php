<?php

namespace App\Filament\Resources\KycRequests\Pages;

use App\Filament\Resources\KycRequests\KycRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListKycRequests extends ListRecords
{
    protected static string $resource = KycRequestResource::class;
}
