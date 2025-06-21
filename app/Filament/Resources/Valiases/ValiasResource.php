<?php

namespace App\Filament\Resources\Valiases;

use App\Filament\Resources\Valiases\Pages\CreateValias;
use App\Filament\Resources\Valiases\Pages\EditValias;
use App\Filament\Resources\Valiases\Pages\ListValiases;
use App\Models\Valias;
use App\Models\Vhost;
use App\Services\AliasService;
use Filament\Forms\Components\Textarea;
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

class ValiasResource extends Resource
{
    protected static ?string $model = Valias::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-at-symbol';

    protected static ?string $navigationLabel = 'Email Aliases';

    protected static ?string $modelLabel = 'Email Alias';

    protected static ?string $pluralModelLabel = 'Email Aliases';

    protected static ?int $navigationSort = 3;

    protected static string|\UnitEnum|null $navigationGroup = 'Mail Management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Alias Configuration')
                    ->schema([
                        TextInput::make('source')
                            ->label('Source Address')
                            ->required()
                            ->maxLength(191)
                            ->placeholder('user@example.com or @example.com')
                            ->helperText('Email address or catchall (@domain.com) that receives mail'),

                        Textarea::make('target')
                            ->label('Target Addresses')
                            ->required()
                            ->rows(3)
                            ->placeholder('user1@example.com, user2@example.com')
                            ->helperText('Comma-separated list of destination email addresses'),

                        Toggle::make('active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Enable or disable this alias'),
                    ])
                    ->columns(2),

                Section::make('Advanced Information')
                    ->schema([
                        TextInput::make('domain')
                            ->label('Domain')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Extracted from source address'),

                        TextInput::make('targets_count')
                            ->label('Number of Targets')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Calculated from target addresses'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source')
                    ->label('Source')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-m-at-symbol')
                    ->weight('bold'),

                TextColumn::make('target')
                    ->label('Targets')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    })
                    ->formatStateUsing(fn (string $state): string => str_replace(',', ', ', $state)),

                TextColumn::make('domain')
                    ->getStateUsing(fn (Valias $record): string => $record->domain)
                    ->badge()
                    ->color('info')
                    ->searchable(),

                TextColumn::make('targets_count')
                    ->label('Target Count')
                    ->getStateUsing(fn (Valias $record): int => count($record->targets))
                    ->badge()
                    ->color('success'),

                IconColumn::make('is_catchall')
                    ->label('Catchall')
                    ->getStateUsing(fn (Valias $record): bool => $record->is_catchall)
                    ->boolean()
                    ->trueIcon('heroicon-o-inbox')
                    ->falseIcon('heroicon-o-envelope')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                IconColumn::make('active')
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

                SelectFilter::make('type')
                    ->options([
                        'regular' => 'Regular Aliases',
                        'catchall' => 'Catchall Aliases',
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value'] === 'catchall') {
                            return $query->where('source', 'like', '@%');
                        } elseif ($data['value'] === 'regular') {
                            return $query->where('source', 'not like', '@%');
                        }

                        return $query;
                    }),
            ])
            ->actions([
                Action::make('validate')
                    ->icon('heroicon-m-shield-check')
                    ->color('info')
                    ->action(function (Valias $record, AliasService $aliasService): void {
                        $errors = $aliasService->validateAlias($record->source, $record->target, $record->id);

                        if (empty($errors)) {
                            Notification::make()
                                ->title('Validation Passed')
                                ->body('This alias configuration is valid.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Validation Failed')
                                ->body('Issues found: '.implode(', ', $errors))
                                ->warning()
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
            'index' => ListValiases::route('/'),
            'create' => CreateValias::route('/create'),
            'edit' => EditValias::route('/{record}/edit'),
        ];
    }
}
