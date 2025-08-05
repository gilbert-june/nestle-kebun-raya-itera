<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LightSensor;
use Carbon\Carbon;

class LightSensorSeeder extends Seeder
{
    private $month = null;

    /**
     * Set the month for data generation
     */
    public function setMonth($month): void
    {
        $this->month = $month;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sensorNames = ['Sensor Cahaya'];
        
        // Get month from setMonth method or default to current month
        $month = $this->month ?? Carbon::now()->month;
        $year = Carbon::now()->year;
        
        // Set start and end time for the specified month
        $startTime = Carbon::create($year, $month, 1, 0, 0, 0);
        $endTime = $startTime->copy()->endOfMonth();
        $intervalSeconds = 120; // 1 data per 2 minutes
        $totalData = ($startTime->diffInSeconds($endTime)) / $intervalSeconds;

        foreach ($sensorNames as $sensorName) {
            $currentTime = $startTime->copy();
            for ($i = 0; $i < $totalData; $i++) {
                $hour = $currentTime->hour;
                $dayOfYear = $currentTime->dayOfYear;
                
                // Generate realistic light intensity values based on time of day and season
                if ($hour >= 6 && $hour <= 18) {
                    // Daytime: higher light intensity with seasonal variation
                    $seasonalFactor = $this->getSeasonalLightFactor($month, $dayOfYear);
                    $baseValue = rand(400, 1200) * $seasonalFactor; // 400-1200 lux during day
                } else {
                    // Nighttime: very low light intensity
                    $baseValue = rand(0, 50); // 0-50 lux at night
                }
                
                $variation = rand(-50, 50) + (sin($i * 0.1) * 30); // Add some variation with sine wave
                $value = max(0, $baseValue + $variation); // Ensure value is not negative

                LightSensor::create([
                    'name' => $sensorName,
                    'value' => round($value, 2),
                    'created_at' => $currentTime,
                    'updated_at' => $currentTime
                ]);

                $currentTime->addSeconds($intervalSeconds);
                if ($currentTime->greaterThan($endTime)) {
                    break;
                }
            }
        }
    }
    
    /**
     * Get seasonal light factor based on month and day of year
     */
    private function getSeasonalLightFactor($month, $dayOfYear): float
    {
        // Indonesia is near equator, so seasonal variation is less extreme
        // But we can still simulate some variation
        $baseFactor = 1.0;
        
        // Slightly higher light in dry season (April-October)
        if ($month >= 4 && $month <= 10) {
            $baseFactor = 1.1;
        } else {
            // Slightly lower light in rainy season (November-March)
            $baseFactor = 0.9;
        }
        
        // Add some daily variation
        $dailyVariation = 0.1 * sin(2 * pi() * $dayOfYear / 365);
        
        return $baseFactor + $dailyVariation;
    }
} 