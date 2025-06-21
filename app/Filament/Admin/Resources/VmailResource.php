<?php

namespace App\Filament\Admin\Resources;

use App\Models\Vmail;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class VmailResource extends Resource
{
    protected static ?string $model = Vmail::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Mailboxes';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Forms\Components\TextInput::make('user')
                    ->label('Email Address')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                \Filament\Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(255)
                    ->hiddenOn('edit'),
                \Filament\Forms\Components\TextInput::make('quota')
                    ->numeric()
                    ->default(1024)
                    ->suffix('MB'),
                \Filament\Forms\Components\Toggle::make('active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('user')
                    ->label('Email Address')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('domain')
                    ->label('Domain')
                    ->getStateUsing(fn ($record) => explode('@', $record->user)[1] ?? '')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('quota')
                    ->suffix(' MB')
                    ->sortable(),
                \Filament\Tables\Columns\ToggleColumn::make('active'),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\TernaryFilter::make('active'),
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
            'index' => \App\Filament\Admin\Resources\VmailResource\Pages\ListVmails::route('/'),
            'create' => \App\Filament\Admin\Resources\VmailResource\Pages\CreateVmail::route('/create'),
            'edit' => \App\Filament\Admin\Resources\VmailResource\Pages\EditVmail::route('/{record}/edit'),
        ];
    }
}
