<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Services\UserService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
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

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    protected static ?int $navigationSort = 1;

    protected static string|\UnitEnum|null $navigationGroup = 'User Management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        Group::make([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Display Name'),

                            TextInput::make('email')
                                ->email()
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),
                        ])->columns(2),

                        Group::make([
                            Select::make('role')
                                ->options(User::getRoles())
                                ->required()
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    // Set defaults based on role
                                    if ($state === User::ROLE_CUSTOMER) {
                                        $set('customer_type', User::CUSTOMER_TYPE_INDIVIDUAL);
                                    }
                                }),

                            Toggle::make('active')
                                ->default(true)
                                ->helperText('Enable or disable user access'),
                        ])->columns(2),

                        TextInput::make('password')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->dehydrated(fn ($state) => filled($state))
                            ->helperText('Minimum 8 characters. Leave empty to keep current password.'),
                    ]),

                Section::make('Personal Information')
                    ->schema([
                        Group::make([
                            TextInput::make('first_name')
                                ->maxLength(255),

                            TextInput::make('last_name')
                                ->maxLength(255),
                        ])->columns(2),

                        Group::make([
                            TextInput::make('phone')
                                ->tel()
                                ->maxLength(255),

                            TextInput::make('company')
                                ->maxLength(255),
                        ])->columns(2),

                        Textarea::make('address')
                            ->rows(3)
                            ->columnSpanFull(),

                        Group::make([
                            TextInput::make('city')
                                ->maxLength(255),

                            TextInput::make('state')
                                ->maxLength(255),

                            TextInput::make('postal_code')
                                ->maxLength(255),

                            TextInput::make('country')
                                ->maxLength(255),
                        ])->columns(4),
                    ])
                    ->columns(2),

                Section::make('Role-Specific Information')
                    ->schema([
                        // Admin/Agent fields
                        TextInput::make('department')
                            ->maxLength(255)
                            ->visible(fn (callable $get) => in_array($get('role'), [User::ROLE_ADMIN, User::ROLE_AGENT])),

                        // Customer fields
                        Group::make([
                            Select::make('customer_type')
                                ->options(User::getCustomerTypes())
                                ->native(false)
                                ->visible(fn (callable $get) => $get('role') === User::ROLE_CUSTOMER),

                            TextInput::make('account_balance')
                                ->numeric()
                                ->prefix('$')
                                ->step(0.01)
                                ->visible(fn (callable $get) => $get('role') === User::ROLE_CUSTOMER),
                        ])->columns(2),

                        DatePicker::make('subscription_expires')
                            ->label('Subscription Expires')
                            ->visible(fn (callable $get) => $get('role') === User::ROLE_CUSTOMER)
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->rows(3)
                            ->placeholder('Internal notes about this user')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('role')
                    ->badge()
                    ->color(fn (User $record): string => $record->role_color)
                    ->formatStateUsing(fn (string $state): string => User::getRoles()[$state] ?? $state),

                TextColumn::make('full_name')
                    ->label('Full Name')
                    ->searchable(['first_name', 'last_name'])
                    ->toggleable(),

                TextColumn::make('company')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('subscription_expires')
                    ->label('Subscription')
                    ->date()
                    ->visible(fn (): bool => auth()->user()->isAdmin())
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : ($state && $state->diffInDays() <= 30 ? 'warning' : 'success'))
                    ->toggleable(),

                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options(User::getRoles())
                    ->multiple(),

                SelectFilter::make('active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),

                SelectFilter::make('customer_type')
                    ->options(User::getCustomerTypes())
                    ->visible(fn (): bool => auth()->user()->isAdmin()),
            ])
            ->actions([
                Action::make('toggle_status')
                    ->label(fn (User $record): string => $record->active ? 'Deactivate' : 'Activate')
                    ->icon(fn (User $record): string => $record->active ? 'heroicon-m-x-circle' : 'heroicon-m-check-circle')
                    ->color(fn (User $record): string => $record->active ? 'warning' : 'success')
                    ->action(function (User $record, UserService $userService): void {
                        $result = $userService->toggleUserStatus($record);

                        if ($result['success']) {
                            Notification::make()
                                ->title('Status Updated')
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
                    })
                    ->requiresConfirmation(),

                Action::make('reset_password')
                    ->icon('heroicon-m-key')
                    ->color('warning')
                    ->action(function (User $record, UserService $userService): void {
                        $result = $userService->resetPassword($record);

                        if ($result['success']) {
                            Notification::make()
                                ->title('Password Reset')
                                ->body("New password: {$result['password']}")
                                ->success()
                                ->persistent()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Error')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),

                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()->isAdmin()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['active' => true]))
                        ->requiresConfirmation(),

                    BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-m-x-circle')
                        ->color('warning')
                        ->action(fn (Collection $records) => $records->each->update(['active' => false]))
                        ->requiresConfirmation(),

                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()->isAdmin()),
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
