<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TemperatureSensor;
use Carbon\Carbon;

class TemperatureSensorSeeder extends Seeder
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
        $sensorNames = ['Sensor Suhu'];
        
        // Get month from setMonth method or default to current month
        $month = $this->month ?? Carbon::now()->month;
        $year = Carbon::now()->year;
        
        // Set start and end time for the specified month
        $startTime = Carbon::create($year, $month, 1, 0, 0, 0);
        $endTime = $startTime->copy()->endOfMonth();
        $intervalSeconds = 30; // 2 data per minute
        $totalData = ($endTime->diffInSeconds($startTime)) / $intervalSeconds;

        foreach ($sensorNames as $sensorName) {
            $currentTime = $startTime->copy();
            for ($i = 0; $i < $totalData; $i++) {
                $hour = $currentTime->hour;
                $dayOfYear = $currentTime->dayOfYear;
                
                // Generate realistic temperature values with seasonal variation
                $seasonalTemp = $this->getSeasonalTemperature($month, $dayOfYear);
                $hourlyVariation = $this->getHourlyTemperatureVariation($hour);
                $baseTemp = $seasonalTemp + $hourlyVariation;
                
                $variation = rand(-3, 3);
                $temperature = $baseTemp + $variation + (sin($i * 0.5) * 2); // Add some sine wave variation

                TemperatureSensor::create([
                    'name' => $sensorName,
                    'value' => round($temperature, 2),
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
     * Get seasonal temperature based on month and day of year
     */
    private function getSeasonalTemperature($month, $dayOfYear): float
    {
        // Indonesia has tropical climate with wet and dry seasons
        // Temperature range: 20-35°C throughout the year
        
        // Base temperature for Indonesia
        $baseTemp = 27.5;
        
        // Seasonal variation (less extreme near equator)
        $seasonalVariation = 0;
        
        // Slightly warmer in dry season (April-October)
        if ($month >= 4 && $month <= 10) {
            $seasonalVariation = 2;
        } else {
            // Slightly cooler in rainy season (November-March)
            $seasonalVariation = -1;
        }
        
        // Add some daily variation
        $dailyVariation = 1 * sin(2 * pi() * $dayOfYear / 365);
        
        return $baseTemp + $seasonalVariation + $dailyVariation;
    }
    
    /**
     * Get hourly temperature variation
     */
    private function getHourlyTemperatureVariation($hour): float
    {
        // Cooler at night, warmer during day
        if ($hour >= 6 && $hour <= 18) {
            // Daytime: warmer
            return rand(2, 5);
        } else {
            // Nighttime: cooler
            return rand(-3, 0);
        }
    }
}
