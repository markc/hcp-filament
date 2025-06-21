<?php

namespace App\Filament\Admin\Resources\VmailResource\Pages;

use App\Filament\Admin\Resources\VmailResource;
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
