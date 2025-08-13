<?php

namespace Paymenter\Extensions\Others\NavigationManager\Admin\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ToggleColumn;
use App\Models\Role;
use Paymenter\Extensions\Others\NavigationManager\Models\NavigationItem;
use Paymenter\Extensions\Others\NavigationManager\Admin\Resources\NavigationItemResource\Pages;

class NavigationItemResource extends Resource
{
    protected static ?string $model = NavigationItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3';
    
    protected static ?string $navigationLabel = 'Navigation Items';
    
    protected static ?string $modelLabel = 'Navigation Item';

    protected static ?string $navigationGroup = 'Extensions';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Display Name')
                            ->helperText('The text that will be shown in the navigation'),

                        TextInput::make('icon')
                            ->maxLength(255)
                            ->label('Icon Class')
                            ->helperText('Icon class (e.g., ri-home) IMPORTANT IT ONLY WORKS ON THE DASHBOARD NAVIGATION')
                            ->placeholder('ri-home'),

                        Textarea::make('description')
                            ->maxLength(500)
                            ->rows(3)
                            ->label('Description')
                            ->helperText('Optional description for admin reference'),

                        Toggle::make('is_enabled')
                            ->label('Enabled')
                            ->default(true)
                            ->helperText('Whether this navigation item is active'),
                    ])
                    ->columns(2),

                Section::make('Link Configuration')
                    ->schema([
                        Select::make('link_type')
                            ->required()
                            ->options(NavigationItem::LINK_TYPES)
                            ->live()
                            ->label('Link Type')
                            ->helperText('Choose how this link should behave'),

                        TextInput::make('link_value')
                            ->required()
                            ->maxLength(500)
                            ->label(fn (Forms\Get $get) => match ($get('link_type')) {
                                'route' => 'Route Name',
                                'url' => 'External URL',
                                'custom' => 'Custom Path',
                                default => 'Link Value',
                            })
                            ->helperText(fn (Forms\Get $get) => match ($get('link_type')) {
                                'route' => 'Laravel route name (e.g., home, dashboard)',
                                'url' => 'Full URL including https://',
                                'custom' => 'Custom path starting with /',
                                default => 'Enter the link value',
                            })
                            ->placeholder(fn (Forms\Get $get) => match ($get('link_type')) {
                                'route' => 'home',
                                'url' => 'https://example.com',
                                'custom' => '/custom-page',
                                default => '',
                            })
                            ->rules(fn (Forms\Get $get) => match ($get('link_type')) {
                                'url' => ['url'],
                                'custom' => ['regex:/^\/.*$/'],
                                default => [],
                            }),

                        KeyValue::make('route_params')
                            ->label('Route Parameters')
                            ->keyLabel('Parameter')
                            ->valueLabel('Value')
                            ->visible(fn (Forms\Get $get) => $get('link_type') === 'route')
                            ->helperText('Parameters to pass to the route (e.g., slug => category-name)'),

                        Toggle::make('target_blank')
                            ->label('Open in New Window')
                            ->helperText('(REQIURES MODIFICATION ON THE THEME) Check this to open the link in a new tab/window (target="_blank")')
                            ->default(false),
                    ])
                    ->columns(1),

                Section::make('Position & Visibility')
                    ->schema([
                        Select::make('location')
                            ->required()
                            ->options(NavigationItem::LOCATIONS)
                            ->default('main')
                            ->label('Navigation Location')
                            ->helperText('Where this item should appear'),

                        Select::make('parent_id')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Parent Item')
                            ->helperText('Select a parent to create a dropdown item')
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                                Select::make('location')->options(NavigationItem::LOCATIONS)->required(),
                            ]),

                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->label('Sort Order')
                            ->helperText('Lower numbers appear first'),

                        Select::make('visibility')
                            ->required()
                            ->options(NavigationItem::VISIBILITY_OPTIONS)
                            ->default('public')
                            ->live()
                            ->label('Visibility')
                            ->helperText('Who can see this navigation item'),

                        Select::make('allowed_roles')
                            ->multiple()
                            ->options(Role::pluck('name', 'id'))
                            ->visible(fn (Forms\Get $get) => $get('visibility') === 'role')
                            ->label('Allowed Roles')
                            ->helperText('Select which roles can see this item'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Name'),

                TextColumn::make('link_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'route' => 'success',
                        'url' => 'warning',
                        'custom' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => NavigationItem::LINK_TYPES[$state] ?? $state)
                    ->label('Type'),

                TextColumn::make('link_value')
                    ->searchable()
                    ->limit(30)
                    ->label('Link'),

                IconColumn::make('target_blank')
                    ->boolean()
                    ->label('New Window')
                    ->tooltip('Opens in new window')
                    ->falseIcon('heroicon-o-arrow-top-right-on-square')
                    ->trueIcon('heroicon-s-arrow-top-right-on-square')
                    ->trueColor('success'),

                TextColumn::make('location')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'main' => 'primary',
                        'account_dropdown' => 'success',
                        'dashboard' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => NavigationItem::LOCATIONS[$state] ?? $state)
                    ->label('Location'),

                TextColumn::make('visibility')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'public' => 'success',
                        'logged_in' => 'warning',
                        'guest' => 'info',
                        'role' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => NavigationItem::VISIBILITY_OPTIONS[$state] ?? $state)
                    ->label('Visibility'),

                TextColumn::make('parent.name')
                    ->label('Parent')
                    ->placeholder('—'),

                TextColumn::make('sort_order')
                    ->sortable()
                    ->label('Order'),

                ToggleColumn::make('is_enabled')
                    ->label('Enabled'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('location')
                    ->options(NavigationItem::LOCATIONS),
                Tables\Filters\SelectFilter::make('visibility')
                    ->options(NavigationItem::VISIBILITY_OPTIONS),
                Tables\Filters\TernaryFilter::make('is_enabled')
                    ->label('Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('location')
            ->reorderable('sort_order')
            ->groups([
                Tables\Grouping\Group::make('location')
                    ->label('Location')
                    ->collapsible(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNavigationItems::route('/'),
            'create' => Pages\CreateNavigationItem::route('/create'),
            'edit' => Pages\EditNavigationItem::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
