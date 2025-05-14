<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\TrendValue;
use Flowframe\Trend\Trend;
use Illuminate\Support\Carbon;

class ExpenseChart extends ChartWidget
{
    protected static ?string $heading = 'Total Pengeluaran';
    protected static ?int $sort = 2;
    public ?string $filter = 'month';

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

        $query =   $data = Trend::model(Expense::class)
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

        $data = $data->sum('amount');

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
               'label' => 'Pengeluaran ' . $this->getFilters()[$activeFilter],
                'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                'borderColor' => 'rgb(255, 99, 132)', // Merah
                'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
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