<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'merchantId',
        'orderId',
        'orderAmount',
        'channelType',
        'notifyUrl',
        'sign',
        'returnUrl',
        'transaction_refno',
        'status',
        'token',
        'qr_data'
    ];
}
