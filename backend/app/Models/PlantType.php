<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlantType extends Model
{
    use HasFactory;

    protected $table = 'plant_types';
    protected $fillable = [
        'name',
        'description',
    ];

    public function images()
    {
        return $this->hasMany(PlantTypeImage::class);
    }
} 