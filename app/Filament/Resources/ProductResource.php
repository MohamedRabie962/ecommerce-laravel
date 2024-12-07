<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Nette\Utils\Image;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Use Grid to create a two-column layout for the form
                Forms\Components\Grid::make(3)->schema([
                    // First section for basic product info like name and slug
                    Forms\Components\Section::make('Products Information')->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, Set $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->disabled()
                            ->unique(Product::class, 'slug', ignoreRecord: true),  // Ensure it's unique


                        Forms\Components\MarkdownEditor::make('description')
                            ->columnSpanFull() // Makes the description span the full width of the column
                            ->fileAttachmentsDirectory('products'),
                    ])->columns(2),  // Set this section to occupy 2 columns


                    Forms\Components\Section::make('Image')->schema([
                        Forms\Components\FileUpload::make('image')
                        ->multiple()
                        ->directory('products')
                        ->maxFiles(5)
                        ->reorderable()
                    ])->columnSpan(2),
                    // Another section for product price and associations
                    Forms\Components\Section::make('Price and Associations')->schema([
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('INR'),
                    ]),

                    Forms\Components\Section::make('Associations')->schema([
                        select::make('category_id')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->relationship('category', 'name'),

                        select::make('brand_id')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->relationship('brand', 'name')
                    ]),

                    Forms\Components\Section::make('status')->schema([
                        Forms\Components\Toggle::make('in_stock')
                        ->required()
                        ->default(true),

                        Forms\Components\Toggle::make('in_active')
                        ->required()
                        ->default(true),

                        Forms\Components\Toggle::make('is_featured')
                        ->required(),

                        Forms\Components\Toggle::make('on_sale')
                            ->required()
                    ])

                ])->columns(1   ), // This will create a 3-column layout for all sections
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([ Tables\Columns\TextColumn::make('name')
              ->searchable(),

              Tables\Columns\TextColumn::make('category.name')
                ->sortable(),

              TextColumn::make('brand.name')
                  ->sortable(),

              Tables\Columns\TextColumn::make('price')
                ->money('INR')
                ->sortable(),

              Tables\Columns\IconColumn::make('is_featured')
                ->boolean(),

              IconColumn::make('on_sale')
                ->boolean(),

              Tables\Columns\IconColumn::make('in_stock')
                ->boolean(),

              IconColumn::make('is_active')
                ->boolean(),

              TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

              TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
                ])
            ->filters([/* Add your filters here */])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Add relations if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
