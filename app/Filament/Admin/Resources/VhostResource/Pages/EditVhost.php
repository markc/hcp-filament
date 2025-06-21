<?php

namespace App\Filament\Admin\Resources\VhostResource\Pages;

use App\Filament\Admin\Resources\VhostResource;
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
