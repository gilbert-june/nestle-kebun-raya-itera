<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class SensorHistoryExcelFile extends Model
{
    use HasFactory;

    protected $table = 'sensor_histories_excel_files';
    protected $fillable = [
        'sensor_name',
        'file_path',
        'date',
        'file_size',
        'download_count',
    ];

    protected $casts = [
        'date' => 'string',
        'file_size' => 'integer',
        'download_count' => 'integer',
    ];

    /**
     * Get the file size in human readable format
     */
    public function getFileSizeAttribute($value)
    {
        if (!$value) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $value;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Get the formatted date
     */
    public function getFormattedDateAttribute()
    {
        if (!$this->date) {
            return 'N/A';
        }

        return Carbon::createFromFormat('Y-m', $this->date)->format('F Y');
    }

    /**
     * Get the download URL
     */
    public function getDownloadUrlAttribute()
    {
        return route('export.download-file', $this->id);
    }

    /**
     * Check if file exists
     */
    public function fileExists()
    {
        return Storage::exists($this->file_path);
    }
} 