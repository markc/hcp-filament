<?php

namespace App\Filament\Admin\Resources\ValiasResource\Pages;

use App\Filament\Admin\Resources\ValiasResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListValiases extends ListRecords
{
    protected static string $resource = ValiasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
