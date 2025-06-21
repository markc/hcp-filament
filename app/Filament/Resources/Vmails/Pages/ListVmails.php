<?php

namespace App\Filament\Resources\Vmails\Pages;

use App\Filament\Resources\Vmails\VmailResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVmails extends ListRecords
{
    protected static string $resource = VmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
