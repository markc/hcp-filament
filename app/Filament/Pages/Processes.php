<?php

namespace App\Filament\Pages;

use App\Services\SystemService;
use Filament\Actions\Action;
use Filament\Pages\Page;

class Processes extends Page
{
    protected string $view = 'filament.pages.processes';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Processes';

    protected static ?string $title = 'System Processes';

    protected static ?int $navigationSort = 3;

    protected static string|\UnitEnum|null $navigationGroup = 'System Information';

    public ?array $processInfo = null;

    public function mount(SystemService $systemService): void
    {
        $this->processInfo = $systemService->getProcessInfo();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-m-arrow-path')
                ->action(function (SystemService $systemService) {
                    $this->processInfo = $systemService->getProcessInfo();

                    $this->dispatch('$refresh');
                }),
        ];
    }

    public function getProcessInfo(): array
    {
        return $this->processInfo ?? [];
    }
}
