<?php
namespace App\Livewire;
use Filament\Forms;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms\Set;
use Livewire\Component;
use Filament\Forms\Form;
use App\Models\OrderProduct;
use App\Models\PaymentMethod;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;

class Pos extends Component implements HasForms
{
    use InteractsWithForms;
    public $search = '';
    public $name_customer = '';
    public $gender = '';
    public $payment_method_id = 0;
    public $paymentMethods;
    public $order_items = [];
    public $total_price;
    public $email;
    public $selectedPaymentMethod;
    public $paid_amount;
    public $change_amount;
    protected $listeners = ['scanResult' => 'handleScanResult'];    

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
                // Paid amount - hanya muncul jika is_cash = true
                Forms\Components\TextInput::make('paid_amount')
                ->label('Jumlah Uang')
                ->numeric()
                ->dehydrated(false)
                ->visible(fn () => $this->selectedPaymentMethod == true)
                ->reactive()
                ->afterStateUpdated(function ($state) {
                    $this->paid_amount = $state;
                }),

                // Tombol kecil di bawah paid_amount
                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('hitung_kembalian')
                        ->label('Hitung Kembalian')
                        ->color('primary')
                        ->action('hitungKembalian')
                ])->visible(fn () => $this->selectedPaymentMethod == true),
    
                // Change amount - hanya muncul jika is_cash = true
                Forms\Components\TextInput::make('change_amount')
                ->label('Kembalian')
                ->numeric()
                ->dehydrated(false)
                ->readOnly()
                ->visible(fn () => $this->selectedPaymentMethod == true),
    
                Forms\Components\TextInput::make('total_price')
                    ->default(fn () => number_format($this->total_price, 0, ',', '.'))
                    ->numeric()
                    ->readOnly(),
    
                Forms\Components\Select::make('payment_method_id')
                    ->label('Metode Pembayaran')
                    ->options($this->paymentMethods->pluck('name', 'id'))
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedPaymentMethod = optional($this->paymentMethods->find($state))->is_cash;
                    }),
            ])
        ]);
    }
    public function mount()
{
    if(session()->has('orderItems')){
        $this->order_items = session('orderItems');
    }

    $this->paymentMethods = PaymentMethod::all();

    // Set default payment method jika ada
    if ($this->paymentMethods->isNotEmpty()) {
        $this->selectedPaymentMethod = $this->paymentMethods->first()->is_cash;
    }

    $this->form->fill([]);
}
    // menambahkan product yg dipilih kedalam keranjang
    public function addToOrder($productId){
        $product = Product::find($productId);
        // validasi stok
        if ($product){
            if($product->stock <= 0){
                Notification::make()
                ->title('Stok Barang Habis')
                ->danger()
                ->send();
                return;
            }

            // validasi apakah sudah ada item yang sama di dalam order_items
            $existingItemKey = null;
            foreach($this->order_items as $key => $item){
                if($item['product_id'] == $productId){
                    $existingItemKey = $key;
                    break; 
                }
            }

            // Jika sudah ada item yang sama di dalam order_items maka qty +1
            if($existingItemKey !== null){
                $this->order_items[$existingItemKey]['quantity']++;
            } else {
                $this->order_items[] = [
                    'product_id' => $productId,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image_url' => $product->image_url,
                    'quantity' => 1
                ];
            }

            session()->put('orderItems', $this->order_items);
            Notification::make()
            ->title('Produk Ditambahkan Ke Keranjang')
            ->success()
            ->send();
        }
    }
   // function untuk menambahkan qty
public function increaseQty($product_id){
    $product = Product::find($product_id);
    
    if (!$product) {
        Notification::make()
            ->title('Produk Tidak Ditemukan')
            ->danger()
            ->send();
        return;
    }

    foreach ($this->order_items as $key => $item) {
        if ($item['product_id'] == $product_id) {
            // Ambil jumlah saat ini
            $currentQty = $item['quantity'];
            
            if ($currentQty + 1 <= $product->stock) {
                $this->order_items[$key]['quantity']++;
            } else {
                Notification::make()
                    ->title('Stok barang tidak mencukupi. Stok tersedia: ' . $product->stock)
                    ->danger()
                    ->send();
            }
            break;
        }
    }
    session()->put('orderItems', $this->order_items);
}

// function untuk mengurangi qty
public function decreaseQty($product_id){
    foreach ($this->order_items as $key => $item) {
        if ($item['product_id'] == $product_id) {
            if ($item['quantity'] > 1) {
                $this->order_items[$key]['quantity']--;
            } else {
                // Hapus item jika quantity jadi 0
                unset($this->order_items[$key]);
                // Reset array index supaya konsisten
                $this->order_items = array_values($this->order_items);
            }
            break;
        }
    }

    session()->put('orderItems', $this->order_items);
}

    public function calculateTotal(){
        $total = 0;
        foreach($this->order_items as $item){
            $total += $item['price'] * $item['quantity'];   
        }
        $this->total_price = $total;
        return $total;
    }

    public function hitungKembalian()
    {
        $paid = floatval($this->paid_amount);
        $total = floatval($this->total_price);
        $this->change_amount = $paid - $total;
        // Update form value secara manual
        $this->form->fill([
            'change_amount' => $this->change_amount,
        ]);
        return $this->change_amount;
    }


    public function loadOrderItems($orderItems){
        $this->order_items = $orderItems;
        session()->put('orderItems', $this->order_items);
    }

    public function checkout(){
        $this->validate([
            'name_customer' => 'nullable',
            'gender' => 'nullable|in:cowo,cewek',
            'payment_method_id' => 'required',
        ]);
    
        $paymentMethod = $this->paymentMethods->find($this->payment_method_id);
    
        // Hitung total harga
        $total = $this->calculateTotal();
    
        // Validasi jika is_cash dan uang kurang
        if ($paymentMethod && $paymentMethod->is_cash && $this->paid_amount < $total) {
            Notification::make()
                ->title('Uang Anda Kurang')
                ->danger()
                ->send();
            return;
        }
    
        $order = Order::create([
            // 'name' => $this->name_customer,
            'gender' => $this->gender,
            'total_price' => $total,
            'change_amount' => $this->hitungKembalian(),
            'paid_amount' => $this->paid_amount,
            'payment_method_id' => $this->payment_method_id,
        ]);
    
        foreach($this->order_items as $item){
            OrderProduct::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
            ]);
        }
        $this->order_items = [];
        session()->forget('orderItems');
        redirect()->to('admin/orders');        
    }

    public function handleScanResult($decodeText){
        $product = Product::where('barcode', $decodeText)->first();
        if ($product) {
            $this->addToOrder($product->id);
        } else {
            Notification::make()
                ->title('Produk Tidak Ditemukan', $decodeText)
                ->danger()
                ->send();
        }
    }
    
    
}