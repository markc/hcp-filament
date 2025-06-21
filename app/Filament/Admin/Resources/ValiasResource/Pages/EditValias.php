<?php

namespace App\Filament\Admin\Resources\ValiasResource\Pages;

use App\Filament\Admin\Resources\ValiasResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditValias extends EditRecord
{
    protected static string $resource = ValiasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
