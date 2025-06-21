<?php

namespace App\Filament\Resources\Valiases\Pages;

use App\Filament\Resources\Valiases\ValiasResource;
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
