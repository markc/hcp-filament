<?php

namespace App\Filament\Resources\Vmails;

use App\Filament\Resources\Vmails\Pages\CreateVmail;
use App\Filament\Resources\Vmails\Pages\EditVmail;
use App\Filament\Resources\Vmails\Pages\ListVmails;
use App\Models\Vhost;
use App\Models\Vmail;
use App\Services\MailService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class VmailResource extends Resource
{
    protected static ?string $model = Vmail::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Mailboxes';

    protected static ?string $modelLabel = 'Mailbox';

    protected static ?string $pluralModelLabel = 'Mailboxes';

    protected static ?int $navigationSort = 2;

    protected static string|\UnitEnum|null $navigationGroup = 'Mail Management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Mailbox Information')
                    ->schema([
                        TextInput::make('user')
                            ->label('Email Address')
                            ->required()
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->maxLength(191)
                            ->placeholder('user@example.com')
                            ->helperText('Full email address for this mailbox'),

                        TextInput::make('password')
                            ->label('Password')
                            ->required(fn (string $context): bool => $context === 'create')
                            ->password()
                            ->minLength(8)
                            ->helperText('Minimum 8 characters')
                            ->dehydrated(fn ($state) => filled($state))
                            ->visible(fn (string $context): bool => $context === 'create'),

                        Toggle::make('active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Enable or disable this mailbox'),
                    ])
                    ->columns(2),

                Section::make('System Settings')
                    ->schema([
                        TextInput::make('uid')
                            ->label('User ID')
                            ->numeric()
                            ->default(1000)
                            ->required(),

                        TextInput::make('gid')
                            ->label('Group ID')
                            ->numeric()
                            ->default(1000)
                            ->required(),

                        TextInput::make('home')
                            ->label('Home Directory')
                            ->maxLength(191)
                            ->helperText('Leave empty to auto-generate based on domain and user'),
                    ])
                    ->columns(3)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user')
                    ->label('Email Address')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-m-envelope')
                    ->weight('bold'),

                TextColumn::make('domain')
                    ->getStateUsing(fn (Vmail $record): string => $record->domain)
                    ->badge()
                    ->color('info')
                    ->searchable(),

                IconColumn::make('active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('home')
                    ->label('Home Directory')
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }

                        return $state;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->label('Status'),

                SelectFilter::make('domain')
                    ->options(function () {
                        return Vhost::pluck('domain', 'domain')->toArray();
                    })
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Action::make('change_password')
                    ->icon('heroicon-m-key')
                    ->color('warning')
                    ->form([
                        TextInput::make('new_password')
                            ->label('New Password')
                            ->required()
                            ->password()
                            ->minLength(8),
                    ])
                    ->action(function (Vmail $record, array $data, MailService $mailService): void {
                        $result = $mailService->changePassword($record->user, $data['new_password']);

                        if ($result['success']) {
                            Notification::make()
                                ->title('Password Changed')
                                ->body($result['message'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Error')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['active' => true])),

                    BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-m-x-circle')
                        ->color('warning')
                        ->action(fn (Collection $records) => $records->each->update(['active' => false])),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVmails::route('/'),
            'create' => CreateVmail::route('/create'),
            'edit' => EditVmail::route('/{record}/edit'),
        ];
    }
}
