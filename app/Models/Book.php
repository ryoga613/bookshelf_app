<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
        'isbn',
        'published_at',
        'image_url',
        'description',
    ];

    public function reviews() : HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favorites() : HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function genres() : BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    

}
