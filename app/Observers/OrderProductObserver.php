<?php

namespace App\Observers;

use App\Models\OrderProduct;
use App\Models\Product;

class OrderProductObserver
{
    /**
     * Handle the OrderProduct "created" event.
     */
    public function created(OrderProduct $orderProduct): void
    {
        // Mengurangi stock produk jika terdapat order baru
        $product = Product::find($orderProduct->product_id);
        $product->decrement('stock', $orderProduct->quantity);
    }

    /**
     * Handle the OrderProduct "updated" event.
     */
    public function updated(OrderProduct $orderProduct): void
    {
        $product = Product::find($orderProduct->product_id);
        $originalQty = $orderProduct->getOriginal('quantity');
        $newQty = $orderProduct->quantity;

        if($originalQty != $newQty){
            $product->increment('stock', $originalQty);
            $product->decrement('stock', $newQty);
        }
    }

    /**
     * Handle the OrderProduct "deleted" event.
     */
    public function deleted(OrderProduct $orderProduct): void
    {
        // Menambah stock produk jika terdapat order yang dihapus
        $product = Product::find($orderProduct->product_id);
        $product->increment('stock', $orderProduct->quantity);
    }
}