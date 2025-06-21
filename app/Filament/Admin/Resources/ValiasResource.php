<?php

namespace App\Filament\Admin\Resources;

use App\Models\Valias;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ValiasResource extends Resource
{
    protected static ?string $model = Valias::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-at-symbol';

    protected static ?string $navigationLabel = 'Email Aliases';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Forms\Components\TextInput::make('source')
                    ->label('Alias Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Use @domain.com for catchall aliases'),
                \Filament\Forms\Components\Textarea::make('target')
                    ->label('Target Emails')
                    ->required()
                    ->rows(3)
                    ->helperText('Separate multiple targets with commas'),
                \Filament\Forms\Components\Toggle::make('active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('source')
                    ->label('Alias')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('target')
                    ->label('Target(s)')
                    ->limit(50)
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('domain')
                    ->label('Domain')
                    ->getStateUsing(fn ($record) => $record->getDomainAttribute())
                    ->sortable(),
                \Filament\Tables\Columns\IconColumn::make('is_catchall')
                    ->label('Catchall')
                    ->boolean()
                    ->getStateUsing(fn ($record) => str_starts_with($record->source, '@')),
                \Filament\Tables\Columns\ToggleColumn::make('active'),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\TernaryFilter::make('active'),
                \Filament\Tables\Filters\Filter::make('catchall')
                    ->label('Catchall Only')
                    ->query(fn ($query) => $query->where('source', 'like', '@%')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => \App\Filament\Admin\Resources\ValiasResource\Pages\ListValiases::route('/'),
            'create' => \App\Filament\Admin\Resources\ValiasResource\Pages\CreateValias::route('/create'),
            'edit' => \App\Filament\Admin\Resources\ValiasResource\Pages\EditValias::route('/{record}/edit'),
        ];
    }
}
