<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'title',
        'link',
        'site_id',
        'meta_title',
        'meta_description',
        'text',
        'speed_x',
        'is_very_fast',
    ];

    public function stats()
    {
        return $this->hasMany(ArticleStat::class, 'article_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function getFormattedViewsAttribute(): string
    {
        $views = $this->stats->last()?->views ?? 0;

        if ($views >= 1000000) {
            return round($views / 1000000, 1) . 'M';
        }

        if ($views >= 1000) {
            return round($views / 1000, 1) . 'k';
        }

        return (string) $views;
    }
}
