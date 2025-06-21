<?php

namespace App\Filament\Resources\Vmails\Pages;

use App\Filament\Resources\Vmails\VmailResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVmail extends EditRecord
{
    protected static string $resource = VmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
