<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'slug'];

    protected static function booted(): void
    {
        static::saving(function (Tag $tag) {
            if (!$tag->slug) {
                $tag->slug = Str::slug($tag->nom ?? '');
            }
        });
    }

    public function episodes(): BelongsToMany
    {
        return $this->belongsToMany(Episode::class, 'episode_tag');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
