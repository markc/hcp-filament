<?php

namespace App\Filament\Resources\Vhosts\Pages;

use App\Filament\Resources\Vhosts\VhostResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVhost extends EditRecord
{
    protected static string $resource = VhostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
