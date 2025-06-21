<?php

namespace App\Filament\Admin\Resources;

use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\Select::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'agent' => 'Agent',
                        'customer' => 'Customer',
                    ])
                    ->required(),
                \Filament\Forms\Components\Toggle::make('active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextInputColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->extraInputAttributes(['style' => 'border: none !important; outline: none !important; box-shadow: none !important; background: transparent !important;']),
                \Filament\Tables\Columns\TextInputColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->extraInputAttributes(['style' => 'border: none !important; outline: none !important; box-shadow: none !important; background: transparent !important;']),
                \Filament\Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                \Filament\Tables\Columns\ToggleColumn::make('active')
                    ->sortable()
                    ->toggleable(),
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
                \Filament\Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'agent' => 'Agent',
                        'customer' => 'Customer',
                    ]),
                \Filament\Tables\Filters\TernaryFilter::make('active'),
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
                \Filament\Actions\DeleteAction::make()->label(''),
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
            'index' => \App\Filament\Admin\Resources\UserResource\Pages\ListUsers::route('/'),
        ];
    }
}
