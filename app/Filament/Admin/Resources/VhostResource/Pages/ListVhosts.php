<?php

namespace App\Filament\Admin\Resources\VhostResource\Pages;

use App\Filament\Admin\Resources\VhostResource;
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
