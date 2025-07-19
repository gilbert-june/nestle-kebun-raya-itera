<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlantTypeImage extends Model
{
    use HasFactory;

    protected $table = 'plant_type_images';
    protected $fillable = [
        'plant_type_id',
        'image',
        'is_thumbnail',
    ];

    protected $appends = [
        'image_url',
    ];

    public function plantType()
    {
        return $this->belongsTo(PlantType::class);
    }

    public function getImageUrlAttribute()
    {
        return url('storage/' . $this->image);
    }
} 