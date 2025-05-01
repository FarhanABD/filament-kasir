<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class POSPages extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.p-o-s-pages';
    protected static ?int $navigationSort = 105;
}