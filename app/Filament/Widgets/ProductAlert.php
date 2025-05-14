<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ProductAlert extends BaseWidget
{
    protected static ?int $sort = 3;
    protected static ?string $heading = 'Stok hampir habis';
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()->where('stock', '<', 10)->orderBy('stock', 'asc')
            )
            ->columns([
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                    Tables\Columns\TextColumn::make('stock')
                    ->label('Stok')
                    ->color(static function ($state): string {
                        if ($state < 5) {
                            return 'danger';
                        } elseif ($state < 10) {
                            return 'warning';
                        } else {
                            return 'success';
                        }
                    })
                    ->sortable()
                    ->numeric(),
            ])
            ->defaultPaginationPageOption(5);
    }
}