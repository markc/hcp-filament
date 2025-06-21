<?php

namespace App\Filament\Resources\Vhosts\Pages;

use App\Filament\Resources\Vhosts\VhostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVhosts extends ListRecords
{
    protected static string $resource = VhostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
