<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\OrderProduct;
use App\Models\PaymentMethod;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\OrderResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\OrderResource\RelationManagers;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-s-shopping-cart';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('Info Utama')
                    -> schema([
                        Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                        Forms\Components\Select::make('gender')
                        ->options([
                          'cowo' => 'Laki-laki',  
                          'cewek' => 'Perempuan',  
                        ])
                        ->nullable(),
                    ])
                ]),

                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('Info Tambahan')
                    -> schema([
                        Forms\Components\TextInput::make('email')
                        ->email()
                        ->nullable()
                        ->maxLength(255),
                        Forms\Components\DatePicker::make('ultah')
                        ->nullable(),
                        Forms\Components\TextInput::make('phone')
                        ->nullable()
                        ->tel()
                        ->maxLength(255)
                        ->default(null),
                        ])
                    ]),
                    // Repeater 
                    Forms\Components\Section::make('Produk Dipesan')->schema([
                        self::getItemRepeater()
                    ]),

                    Forms\Components\Group::make()->schema([
                        Forms\Components\Section::make('Total')
                        -> schema([
                            Forms\Components\TextInput::make('total_price')
                            ->label('Total Harga')
                            ->required()
                            ->readOnly()
                            ->numeric(),
                        Forms\Components\Textarea::make('note')
                            ->label('Catatan')
                            ->columnSpanFull(),
                            ])
                        ]), 
                    Forms\Components\Group::make()->schema([
                        Forms\Components\Section::make('Pembayaran')
                        -> schema([
                            Forms\Components\Select::make('payment_method_id')
                            ->label('Metode Pembayaran')
                            ->relationship('paymentMethod', 'name')
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                              $paymentMethod = PaymentMethod::find($state); 
                              $set('is_cash', $paymentMethod?-> is_cash ?? false);

                              if (!$paymentMethod?->is_cash) {
                                $set('paid_amount', $get('total_price'));
                                $set('change_amount', 0);
                              }
                            })
                            ->afterStateHydrated(function (Forms\Set $set, $state, Forms\Get $get) {
                                $paymentMethod = PaymentMethod::find($state);
                            
                                if ($paymentMethod?->is_cash) {
                                    $set('paid_amount', $get('total_price'));
                                    $set('change_amount', 0);
                                }
                            
                                $set('is_cash', $paymentMethod?->is_cash ?? false);
                            }),
                            
                            Forms\Components\Toggle::make('is_cash')
                            ->dehydrated(),
                        Forms\Components\TextInput::make('paid_amount')
                        ->label('Jumlah Dibayar')
                            ->numeric()
                            ->reactive()
                            ->readOnly(fn (Forms\Get $get ) => $get('is_cash') == false)
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                // function untuk menghitung kembalian
                                self::updateExchangePaid($get, $set);
                            })
                            ->default(null),
                        Forms\Components\TextInput::make('change_amount')
                            ->numeric()
                            ->readOnly()
                            ->label('Kembalian')
                            ->default(null),
                            ])
                        ]), 
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('total_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('change_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getItemRepeater(): Repeater{
        return Repeater::make('orderProducts')
        ->relationship()
        ->live()
        ->columns([
          'md' =>10,  
        ])
        ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
            self::updateTotalPrice($get, $set);
        })
        ->schema([
           Forms\Components\Select::make('product_id')
           ->label('Produk')
           ->required()
           ->options(\App\Models\Product::query()->where('stock','>', 1)->pluck('name', 'id')->toArray())
           ->columnSpan([
            'md'=>5
           ])
           ->afterStateHydrated(function (Forms\Set $set, $state, Forms\Get $get) {
             $product = Product::find($state);  
             $set('unit_price', $product->price ?? 0);
             $set('stock', $product->stock ?? 0);  
           })
           ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
               $product = Product::find($state);
               $set('unit_price',$product->price ?? 0);
               $set('stock',$product->stock ?? 0);
               $quantity = $get('quantity') ?? 1;
               $stock = $get('stock');
               self::updateTotalPrice($get, $set);
           })
           ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
           Forms\Components\TextInput::make('quantity')
           ->numeric()
           ->default(1)
           ->minValue(1)
           ->required()
           ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
             $stock = $get('stock');
             if($state > $stock){
                $set('quantity', $stock); 
                Notification::make()
                ->title('Stok tidak mencukupi')
                ->warning()
                ->send();
             } 
             self::updateTotalPrice($get, $set);
           })
           ->columnSpan([
            'md'=>1
           ]),
           Forms\Components\TextInput::make('stock')
           ->numeric()
           ->required()
           ->columnSpan([
            'md'=>1
           ]),
           Forms\Components\TextInput::make('unit_price')
           ->label('Harga Produk Sekarang')
           ->required()
           ->numeric()
           ->readonly()
           ->columnSpan([
            'md'=>2,
           ])
        ]);
    }

    // function update total price berdasarkan qty 
    protected static function updateTotalPrice(Forms\Get $get, Forms\Set $set) : void{
        $selectedProduct = collect($get('orderProducts'))->filter(fn($item) => !empty($item['product_id'])  && !empty($item['quantity']));

        $prices = Product::find($selectedProduct->pluck('product_id'))->pluck('price','id');
        $total = $selectedProduct->reduce(function($total, $product) use ($prices){
            return $total + ($prices[$product['product_id']] * $product['quantity']);
        }, 0);

        $set('total_price', $total);
    }

    protected static function updateExchangePaid (Forms\Get $get, Forms\Set $set) : void{
        $paidAmount = (int) $get('paid_amount') ?? 0;
        $totalPrice = (int) $get('total_price') ?? 0;
        $exchange = $paidAmount - $totalPrice;
        $set('change_amount', $exchange);
    }
}