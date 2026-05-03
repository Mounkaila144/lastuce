<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'slug', 'description', 'position'];
    protected $casts = ['position' => 'integer'];

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if (!$category->slug) {
                $category->slug = Str::slug($category->nom ?? '');
            }
        });
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
