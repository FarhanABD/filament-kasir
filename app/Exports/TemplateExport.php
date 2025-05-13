<?php

namespace App\Exports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class TemplateExport implements WithMultipleSheets
{
  public function sheets(): array
  {
    return [
        new CategoriesExport(),
        new ProductExport(),
    ];
  }
}

class ProductExport implements FromCollection, WithHeadings, WithTitle {
    public function collection(){
        return collect([]);
    }
    // Membuat heading di sheet excel
    public function headings(): array
    {
        return [
            'name',
            'category_id',
            'stock',
            'price',
            'is_active',
            'barcode',
            'image',
        ];
    }
// Membuat judul di sheet excel
    public function title(): string
    {
        return 'Product';
    }
}

class CategoriesExport implements FromCollection, WithHeadings, WithTitle {
      /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Category::select('id', 'name')->get();
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
        ];
    }
    public function title(): string
    {
        return 'Category';
    }
}