<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SoilMoistureSensor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SoilMoistureSensorSeeder extends Seeder
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
        $sensorNames = ['Sensor Kelembaban Tanah'];
        
        // Get month from setMonth method or default to current month
        $month = $this->month ?? Carbon::now()->month;
        $year = Carbon::now()->year;
        
        // Set start and end time for the specified month
        $startTime = Carbon::create($year, $month, 1, 0, 0, 0);
        $endTime = $startTime->copy()->addDays(10);
        $intervalSeconds = 720; // 1 data per 12 minutes (360 seconds)
        $totalData = ($startTime->diffInSeconds($endTime)) / $intervalSeconds;

        foreach ($sensorNames as $sensorName) {
            for ($i = 0; $i < $totalData; $i++) {
                $currentTime = $startTime->copy()->addSeconds($i * $intervalSeconds);
                
                if ($currentTime->greaterThan($endTime)) {
                    break;
                }
                
                $hour = $currentTime->hour;
                $dayOfYear = $currentTime->dayOfYear;
                
                // Generate realistic soil moisture values with seasonal variation
                $seasonalMoisture = $this->getSeasonalMoisture($month, $dayOfYear);
                $hourlyVariation = $this->getHourlyMoistureVariation($hour);
                $baseMoisture = $seasonalMoisture + $hourlyVariation;
                
                $variation = rand(-10, 10);
                $moisture = $baseMoisture + $variation + (sin($i * 0.3) * 5); // Add some sine wave variation
                $value = max(0, min(100, $moisture)); // Ensure value is between 0-100

                DB::table('soil_moisture_sensors')->insert([
                    'name' => $sensorName,
                    'value' => round($value, 2),
                    'created_at' => $currentTime,
                    'updated_at' => $currentTime
                ]);
            }
        }
    }
    
    /**
     * Get seasonal soil moisture based on month and day of year
     */
    private function getSeasonalMoisture($month, $dayOfYear): float
    {
        // Indonesia has wet and dry seasons affecting soil moisture
        
        // Base moisture level
        $baseMoisture = 50;
        
        // Seasonal variation
        $seasonalVariation = 0;
        
        // Higher moisture in rainy season (November-March)
        if ($month >= 11 || $month <= 3) {
            $seasonalVariation = 15;
        } else {
            // Lower moisture in dry season (April-October)
            $seasonalVariation = -10;
        }
        
        // Add some daily variation
        $dailyVariation = 5 * sin(2 * pi() * $dayOfYear / 365);
        
        return $baseMoisture + $seasonalVariation + $dailyVariation;
    }
    
    /**
     * Get hourly moisture variation
     */
    private function getHourlyMoistureVariation($hour): float
    {
        // Moisture can vary throughout the day
        // Slightly higher in early morning due to dew
        if ($hour >= 5 && $hour <= 8) {
            return rand(2, 5);
        } else {
            return rand(-2, 2);
        }
    }
} 