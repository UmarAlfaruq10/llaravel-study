<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class productGallery extends Model
{
    use HasFactory,
    SoftDeletes;

    protected $fillable = [
        'product_id',
        'url',
        'is_feature'
    ];
}
