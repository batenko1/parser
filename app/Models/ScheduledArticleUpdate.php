<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledArticleUpdate extends Model
{
    protected $fillable = ['article_id', 'run_at', 'processed'];
    protected $casts = [
        'run_at'    => 'datetime',
        'processed' => 'bool',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
