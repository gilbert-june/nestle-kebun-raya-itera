<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemperatureSensor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'value'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the latest temperature reading for a specific sensor
     */
    public static function getLatestReading($sensorName)
    {
        return self::where('name', $sensorName)
                   ->latest()
                   ->first();
    }

    /**
     * Get temperature readings for a specific sensor within a time range
     */
    public static function getReadingsForSensor($sensorName, $hours = 24)
    {
        return self::where('name', $sensorName)
                   ->where('created_at', '>=', now()->subHours($hours))
                   ->orderBy('created_at')
                   ->get();
    }

    /**
     * Get all unique sensor names
     */
    public static function getSensorNames()
    {
        return self::distinct()->pluck('name');
    }
}
