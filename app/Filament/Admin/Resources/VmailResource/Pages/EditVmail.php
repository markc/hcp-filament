<?php

namespace App\Filament\Admin\Resources\VmailResource\Pages;

use App\Filament\Admin\Resources\VmailResource;
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
