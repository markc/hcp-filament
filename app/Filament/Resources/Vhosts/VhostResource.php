<?php

namespace App\Filament\Resources\Vhosts;

use App\Filament\Resources\Vhosts\Pages\CreateVhost;
use App\Filament\Resources\Vhosts\Pages\EditVhost;
use App\Filament\Resources\Vhosts\Pages\ListVhosts;
use App\Models\Vhost;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VhostResource extends Resource
{
    protected static ?string $model = Vhost::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Virtual Hosts';

    protected static ?string $modelLabel = 'Virtual Host';

    protected static ?string $pluralModelLabel = 'Virtual Hosts';

    protected static ?int $navigationSort = 1;

    protected static string|\UnitEnum|null $navigationGroup = 'Mail Management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Domain Information')
                    ->schema([
                        TextInput::make('domain')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(191)
                            ->placeholder('example.com')
                            ->helperText('Enter the domain name without www or protocol')
                            ->rules(['regex:/^[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9]?\\.[a-zA-Z]{2,}$/']),

                        Toggle::make('status')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Enable or disable this virtual host'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('domain')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-m-globe-alt')
                    ->weight('bold'),

                TextColumn::make('mailbox_count')
                    ->label('Mailboxes')
                    ->badge()
                    ->color('info')
                    ->getStateUsing(fn (Vhost $record): int => $record->vmails()->count()),

                TextColumn::make('alias_count')
                    ->label('Aliases')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(fn (Vhost $record): int => $record->valias()->count()),

                IconColumn::make('status')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

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
                SelectFilter::make('status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->label('Status'),
            ])
            ->actions([
                Action::make('view_mailboxes')
                    ->icon('heroicon-m-envelope')
                    ->color('info')
                    ->url(fn (Vhost $record): string => '/admin/vmails?tableFilters[domain][value]='.$record->domain)
                    ->openUrlInNewTab(false),

                Action::make('view_aliases')
                    ->icon('heroicon-m-at-symbol')
                    ->color('success')
                    ->url(fn (Vhost $record): string => '/admin/valiases?tableFilters[domain][value]='.$record->domain)
                    ->openUrlInNewTab(false),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
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
            'index' => ListVhosts::route('/'),
            'create' => CreateVhost::route('/create'),
            'edit' => EditVhost::route('/{record}/edit'),
        ];
    }
}
