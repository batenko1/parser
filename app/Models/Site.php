<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $fillable = [
        'name',
        'link',
        'speed_x',
        'very_fast_value'
    ];
}
