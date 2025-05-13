<?php

namespace App\Filament\Resources\ProductResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use App\Imports\ProductImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ProductResource;
use Filament\Notifications\Notification;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Import')
            ->label('Import Products')
            ->color('danger')
            ->form([
              FileUpload::make('attachment')
              ->label('upload file')  
            ])
            ->action(function (array $data) {
                $file = storage_path('app/public/' . $data['attachment']); // perbaikan path
            
                try {
                    Excel::import(new ProductImport(), $file);
                    Notification::make()
                        ->title('Product berhasil di import')
                        ->success()
                        ->send();
                } catch (\Exception $e){
                    Notification::make()
                        ->title('Import gagal: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            })            
            ->icon('heroicon-s-arrow-up-tray'),
            Action::make('Download Template')
            ->url(route('download-template'))
            ->icon('heroicon-s-arrow-down-tray')
            ->color('success'),
            Actions\CreateAction::make(),
        ];
    }
}