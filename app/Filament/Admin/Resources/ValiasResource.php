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
                \Filament\Tables\Columns\TextInputColumn::make('source')
                    ->label('Alias')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->extraInputAttributes(['style' => 'border: none !important; outline: none !important; box-shadow: none !important; background: transparent !important;']),
                \Filament\Tables\Columns\TextInputColumn::make('target')
                    ->label('Target(s)')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->extraInputAttributes(['style' => 'border: none !important; outline: none !important; box-shadow: none !important; background: transparent !important;']),
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
                \Filament\Tables\Filters\TernaryFilter::make('active'),
                \Filament\Tables\Filters\Filter::make('target')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('target_email')
                            ->placeholder('Filter by target email...'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when(
                                $data['target_email'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $email): \Illuminate\Database\Eloquent\Builder => $query->where('target', 'like', '%'.$email.'%'),
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
            'index' => \App\Filament\Admin\Resources\ValiasResource\Pages\ListValiases::route('/'),
        ];
    }
}
