<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Filament\Widgets\ChartWidget;

class OmsetChart extends ChartWidget
{
    protected static ?string $heading = 'Chart';
    protected static ?int $sort = 1;
    protected static string $color = 'info';
    public ?string $filter = 'today';


    protected function getData(): array
    {
        $data = Trend::model(Order::class)
        ->between(
            start: now()->startOfYear(),
            end: now()->endOfYear(),
        )
        ->perMonth()
        ->sum('total_price');

    return [
        'datasets' => [
            [
                'label' => 'Omset Per Bulan',
                'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
            ],
        ],
        'labels' => $data->map(fn (TrendValue $value) => $value->date),
    ];
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Hari ini',
            'week' => 'Minggu Lalu',
            'month' => 'Bulan Lalu',
            'year' => 'Tahun Ini',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}