<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserActions;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            UserActions::adjustBalance()->after(fn () => $this->refreshFormData(['balance'])),
            UserActions::toggleBan()->after(fn () => $this->refreshFormData(['is_banned'])),
            DeleteAction::make(),
        ];
    }
}
