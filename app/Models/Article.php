<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'title',
        'link',
        'site_id'
    ];

    public function stats()
    {
        return $this->hasMany(ArticleStat::class, 'article_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }
}
