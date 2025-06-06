<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Flowframe\Trend\Trend;
use Illuminate\Support\Carbon;
use Flowframe\Trend\TrendValue;
use Filament\Widgets\ChartWidget;

class OmsetChart extends ChartWidget
{
    protected static ?string $heading = 'Total Omset';
    protected static ?int $sort = 1;
    public ?string $filter = 'today';

    protected function getData(): array
    {
        $activeFilter = $this->filter;
        $dateRange = match ($activeFilter) {
            'today' => [
                'start' =>now()->startOfDay(), 
                'end' =>now()->endOfDay(), 
                'period' => 'perHour',
            ],   
            'week' => [
                'start' =>now()->startOfWeek(), 
                'end' =>now()->endOfWeek(), 
                'period' => 'perDay',
            ],   
            'month' => [
                'start' =>now()->startOfMonth(), 
                'end' =>now()->endOfMonth(), 
                'period' => 'perDay',
            ],   
            'year' => [
                'start' =>now()->startOfYear(), 
                'end' =>now()->endOfYear(), 
                'period' => 'perMonth',
            ],   
        };

        $query =   $data = Trend::model(Order::class)
        ->between(
            start: $dateRange['start'],
            end: $dateRange['end'],
        );

        if ($dateRange['period'] === 'perDay') {
            $data = $query->perDay();
        } elseif ($dateRange['period'] === 'perMonth') {
            $data = $query->perMonth();
        } elseif ($dateRange['period'] === 'perYear') {
            $data = $query->perYear();
        }elseif ($dateRange['period'] === 'perHour') {
            $data = $query->perHour();
        }

        $data = $data->sum('total_price');

    $labels = $data->map(function(TrendValue $value) use ($dateRange) {
       $date = Carbon::parse($value->date);
       if($dateRange['period'] === 'perHour') {
           return $date->format('H:i');
       } else if ($dateRange['period'] === 'perDay') {
           return $date->format('d M');
       } return $date->format('M Y');
    });

    return [
        'datasets' => [
            [
               'label' => 'Omset ' . $this->getFilters()[$activeFilter],
                'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                'borderColor' => 'rgb(75, 192, 192)', // Hijau
                'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                'fill' => true,
            ],
        ],
        'labels' => $labels,
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