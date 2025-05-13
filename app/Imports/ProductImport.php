<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductImport implements ToModel, WithHeadingRow, WithMultipleSheets, SkipsEmptyRows, WithValidation
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    public function sheets(): array
    {
        return [
          0 => $this  
        ];
    }
    public function model(array $row)
    {
        return new Product([
            'name' => $row['name'],
            'slug' => Product::generateUniqueSlug($row['name']),
            'category_id' => $row['category_id'],
            'stcok' => $row['stock'],
            'price' => $row['price'],
            'is_active' => $row['is_active'],
            'barcode' => $row['barcode'],
            'image' => $row['image'],
        ]);
    }
    public function rules(): array {
        return [
            '*name' => 'required|string',
            '*category_id' => 'required|exists:categories,id',
            '*stock' => 'required|integer|min:0',
            '*price' => 'required',
            '*barcode' => 'required|unique:products,barcode',
        ];
    }

    public function customValidationMessages()
    {
        return [
            '*name' => 'Kolom name harus diisi',
            '*category_id' => 'Kolom category harus diisi',
            '*stock' => 'Kolom stock harus diisi angka',
            '*price' => 'Kolom price harus diisi',
            '*barcode' => 'Kolom barcode harus diisi',
        ];
    }
}