<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Product;
use Faker\Core\Number;
use Filament\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use PHPUnit\Metadata\Group;


class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(1) // Adjust the number for columns if needed
                ->schema([
                    Forms\Components\Section::make('Order Information')
                        ->schema([
                            Forms\Components\Select::make('user_id')
                                ->label('Customer')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Forms\Components\Select::make('payment_method')
                            ->options([
                                'Stripe' => 'Stripe',
                                'cod' => 'cash on delivery',
                            ])
                            ->required(),
                            Forms\Components\Select::make('payment_status')
                            ->options([
                                'pending' => 'Pending',
                                'Paid' => 'Paid',
                                'failed' => 'Failed',
                            ])
                            ->default('pending')
                            ->required(),

                            Forms\Components\ToggleButtons::make('status')
                                ->inline()
                                ->default('new')
                                ->required()
                            ->options([
                                'new' => 'New',
                                'processing' => 'Processing',
                                'canceled' => 'Canceled',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                            ])
                            ->colors([
                                'new' => 'info',
                                'processing' => 'warning',
                                'canceled' => 'danger',
                                'shipped' => 'success',
                                'delivered' => 'success',
                            ])
                            ->icons([
                                'new' => 'heroicon-m-sparkles',
                                'processing' => 'heroicon-m-arrow-path',
                                'canceled' => 'heroicon-m-x-circle',
                                'shipped' => 'heroicon-m-truck',
                                'delivered' => 'heroicon-m-check-badge',
                            ]),
                            Forms\Components\Select::make('currency')
                            ->options([
                                'usd' => 'USD',
                                'eur' => 'EUR',
                                'inr' => 'INR',
                                'gdp' => 'GDP'
                            ])->default('eur')
                            ->required(),

                            Forms\Components\Select::make('shipping_method')
                            ->options([
                                'fedex' => 'Fedex',
                                'ups'=> 'UPS',
                                'dhl' => 'DHL',
                                'usps' => 'USPS',
                            ])->default('fedex')
                            ->required(),

                    Forms\Components\Textarea::make('notes')
                    ->columnSpanFull()
                        ])->columns(2),


                    Forms\Components\Section::make('Order Items')->schema([
                        Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->schema([

                            Select::make('product_id')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->required()
                            ->preload()
                            ->distinct()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->columnSpan(4)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn($state , Set $set) =>$set('unit_amount' , Product::Find($state)?->price??0))
                            ->afterStateUpdated(fn($state , Set $set) =>$set('total_amount' , Product::Find($state)?->price??0)),

                            Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(1)
                            ->columnSpan(2)
                            ->afterStateUpdated(fn($state , Set $set , Get $get)=>$set('total_amount' , $state*$get('unit_amount'))),


                            TextInput::make('unit_amount')
                            ->numeric()
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(3),

                            TextInput::make('total_amount')
                            ->numeric()
                            ->required()
                            ->dehydrated()
                            ->columnSpan(3),

                        ])->columns(12),
                        Forms\Components\Placeholder::make('grand_total_placeholder')
                        ->label('Grand Total')
                            ->content(function (Get $get, Set $set) {
                                $total = 0;

                                if (!$repeaters = $get('items')) {
                                    $set('grand_total', $total);
                                    return number_format($total, 2) . ' INR';
                                }

                                foreach ($repeaters as $key => $repeater) {
                                    $total += $get("items.{$key}.total_amount");
                                }

                                $set('grand_total', $total);
                                return number_format($total, 2) . ' INR'; // Formats total with 2 decimal places
                            }),

                        Forms\Components\Hidden::make('grand_total')
                        ->default(0)
                    ])
                ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([ Tables\Columns\TextColumn::make('user.name')  // Correct reference to 'user.name'
                ->label('Customer')
                ->searchable(),

                Tables\Columns\TextColumn::make('grand_total')
                 ->numeric()
                 ->sortable()
                 ->searchable(),

                Tables\Columns\TextColumn::make('payment_method')
                ->searchable()
                ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                ->searchable()
                ->sortable(),

                Tables\Columns\TextColumn::make('currency')
                ->sortable()
                ->searchable(),

                Tables\Columns\TextColumn::make('shipping_method')
                ->searchable()
                ->sortable(),


                Tables\Columns\SelectColumn::make('status')
                  ->options([
                      'new' => 'New',
                      'processing' => 'Processing',
                      'canceled' => 'Canceled',
                      'shipped' => 'Shipped',
                      'delivered' => 'Delivered',
                  ])
                  ->searchable()
                  ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->sortable()
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                ->sortable()
                ->dateTime()
                ->toggleable(isToggledHiddenByDefault: true),


            ])
            ->filters([
                //
            ])
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
            //
        ];
    }

    public static function getNavigationBadge(): ?string{
        return static ::getmodel()::count();
    }





    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
