<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;
    protected $fillable = ['gender', 'ultah','payment_method_id', 'total_price','name', 'email', 'phone','paid_amount','change_amount','note'];
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function orderProducts(): HasMany{
        return $this->hasMany(OrderProduct::class, 'order_id');
    }
}