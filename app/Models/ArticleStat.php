<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleStat extends Model
{
    protected $fillable = [
        'article_id',
        'views'
    ];
}
