<?php

namespace App\Filament\Pages;

use App\Services\SystemService;
use Filament\Actions\Action;
use Filament\Pages\Page;

class InfoSys extends Page
{
    protected string $view = 'filament.pages.info-sys';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationLabel = 'System Information';

    protected static ?string $title = 'System Information';

    protected static ?int $navigationSort = 1;

    protected static string|\UnitEnum|null $navigationGroup = 'System Information';

    public ?array $systemInfo = null;

    public function mount(SystemService $systemService): void
    {
        $this->systemInfo = $systemService->getSystemInfo();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-m-arrow-path')
                ->action(function (SystemService $systemService) {
                    $this->systemInfo = $systemService->getSystemInfo();

                    $this->dispatch('$refresh');
                }),
        ];
    }

    public function getSystemInfo(): array
    {
        return $this->systemInfo ?? [];
    }
}
