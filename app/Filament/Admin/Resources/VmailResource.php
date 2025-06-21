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
                \Filament\Forms\Components\TextInput::make('clearpw')
                    ->label('Clear Password')
                    ->password()
                    ->revealable()
                    ->helperText('This will be stored as plain text for reference'),
                \Filament\Forms\Components\TextInput::make('uid')
                    ->label('UID')
                    ->numeric()
                    ->default(1000),
                \Filament\Forms\Components\TextInput::make('gid')
                    ->label('GID')
                    ->numeric()
                    ->default(1000),
                \Filament\Forms\Components\Toggle::make('active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextInputColumn::make('user')
                    ->label('Email Address')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->extraInputAttributes(['style' => 'border: none !important; outline: none !important; box-shadow: none !important; background: transparent !important;']),
                \Filament\Tables\Columns\TextInputColumn::make('clearpw')
                    ->label('Clear Password')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->extraInputAttributes(['style' => 'border: none !important; outline: none !important; box-shadow: none !important; background: transparent !important;']),
                \Filament\Tables\Columns\TextColumn::make('uid')
                    ->label('UID')
                    ->sortable()
                    ->toggleable(),
                \Filament\Tables\Columns\TextColumn::make('gid')
                    ->label('GID')
                    ->sortable()
                    ->toggleable(),
                \Filament\Tables\Columns\TextColumn::make('home')
                    ->label('Home')
                    ->limit(24)
                    ->tooltip(fn ($record) => $record->home)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                \Filament\Tables\Columns\ToggleColumn::make('active')
                    ->sortable()
                    ->toggleable(),
                \Filament\Tables\Columns\TextColumn::make('password')
                    ->label('Hashed Password')
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->password)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                \Filament\Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->reorderableColumns()
            ->deferColumnManager(false)
            ->recordAction(false)
            ->filters([
                \Filament\Tables\Filters\TernaryFilter::make('active'),
                \Filament\Tables\Filters\Filter::make('domain')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('domain')
                            ->placeholder('Filter by domain...'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when(
                                $data['domain'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $domain): \Illuminate\Database\Eloquent\Builder => $query->where('user', 'like', '%@'.$domain.'%'),
                            );
                    }),
                \Filament\Tables\Filters\Filter::make('clearpw')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('clearpw_search')
                            ->placeholder('Search clear password...'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when(
                                $data['clearpw_search'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $search): \Illuminate\Database\Eloquent\Builder => $query->where('clearpw', 'like', '%'.$search.'%'),
                            );
                    }),
                \Filament\Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from'),
                        \Filament\Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                EditAction::make()->label(''),
                DeleteAction::make()->label(''),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
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
        ];
    }
}
