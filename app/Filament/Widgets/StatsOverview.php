<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Product;
use App\Models\Order;
use App\Models\Expense;
class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $product_count = Product::count();
        $order_count = Order::count();
        $omset = Order::sum('total_price');
        $expense = Expense::sum('amount');
        return [
            Stat::make('Produk', $product_count),
            Stat::make('Orders', $order_count),
            Stat::make('Omset', 'Rp'.number_format($omset, 0, ',', '.')),
            Stat::make('Pengeluaran', 'Rp'.number_format($expense, 0, ',', '.')),
        ];
    }
}