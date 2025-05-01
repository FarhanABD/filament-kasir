<?php

namespace App\Livewire;

use App\Models\PaymentMethod;
use App\Models\Product;
use Livewire\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;

use Filament\Forms\Form;
use Filament\Forms;
use Filament\Forms\Set;

class Pos extends Component implements HasForms
{
    use InteractsWithForms;
    public $search = '';
    public $nameCustomer = '';
    public $paymentMethods;
    // function untuk render halaman
    public function render()
    {
        return view('livewire.pos', [
            'products' => Product::where('stock', '>', 0)
                ->when($this->search, fn($query) =>
                    $query->where('name', 'like', '%' . $this->search . '%'))
                ->paginate(12),
        ]);
    }
    
    public function form(Form $form): Form{
        return $form 
        ->schema([
           Forms\Components\Section::make('Form Checkout') 
           ->schema([
            //    Form Name customer
               Forms\Components\TextInput::make('nameCustomer')
               ->required()
               ->maxLength(255)
               ->default(fn ()=> $this->nameCustomer),
            //    Form Gender
               Forms\Components\Select::make('gender')
               ->nullable()
               ->options([
                   'cowo' => 'Laki-laki',  
                   'cewek' => 'Perempuan',  
               ]),
            //    Form Total price
               Forms\Components\TextInput::make('total_price'),
            //    Form Payement Method
               Forms\Components\Select::make('payment_method_id')
               ->label('Metode Pembayaran')
               ->options($this->paymentMethods->pluck('name', 'id'))
               ->required(),
           ])
        ]);
    }

    public function mount()
    {
        // Isi dulu property paymentMethods
        $this->paymentMethods = PaymentMethod::all();
    
        // Baru isi form jika perlu
        $this->form->fill([
            'payment_method' => $this->paymentMethods,
        ]);
    }
    
}