<?php

namespace App\Filament\Pages;

use App\Services\MailService;
use Filament\Actions\Action;
use Filament\Pages\Page;

class InfoMail extends Page
{
    protected string $view = 'filament.pages.info-mail';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope-open';

    protected static ?string $navigationLabel = 'Mail Information';

    protected static ?string $title = 'Mail Information';

    protected static ?int $navigationSort = 2;

    protected static string|\UnitEnum|null $navigationGroup = 'System Information';

    public ?array $mailInfo = null;

    public function mount(MailService $mailService): void
    {
        $this->mailInfo = $mailService->getMailInfo();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-m-arrow-path')
                ->action(function (MailService $mailService) {
                    $this->mailInfo = $mailService->getMailInfo();

                    $this->dispatch('$refresh');
                }),
        ];
    }

    public function getMailInfo(): array
    {
        return $this->mailInfo ?? [];
    }
}
