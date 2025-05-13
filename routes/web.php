<?php

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TemplateExport;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/download-template', function () {
    return Excel::download(new TemplateExport(), 'template.xlsx');
})->name('download-template');