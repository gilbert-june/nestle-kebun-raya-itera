<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorHistoryExcelFile extends Model
{
    use HasFactory;

    protected $table = 'sensor_histories_excel_files';
    protected $fillable = [
        'sensor_name',
        'file_path',
        'date',
    ];
} 