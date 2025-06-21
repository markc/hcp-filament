<?php

namespace App\Filament\Resources\Valiases\Pages;

use App\Filament\Resources\Valiases\ValiasResource;
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
