<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class About extends Model
{
    use HasFactory;

    protected $table = 'about';
    protected $fillable = [
        'identifier',
        'title',
        'description',
        'image',
    ];
    
    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute()
    {
        return url('storage/' . $this->image);
    }
} 