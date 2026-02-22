<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'product_master_list';

    protected $fillable = [
        'product_id',
        'type',
        'brand',
        'model',
        'capacity',
        'quantity',
    ];
}
