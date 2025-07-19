<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TurbiditySensor;
use Carbon\Carbon;

class TurbiditySensorSeeder extends Seeder
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
        $sensorNames = ['Sensor Turbiditas 1'];
        
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
                
                // Generate realistic turbidity values with seasonal variation
                $seasonalTurbidity = $this->getSeasonalTurbidity($month, $dayOfYear);
                $hourlyVariation = $this->getHourlyTurbidityVariation($hour);
                $baseTurbidity = $seasonalTurbidity + $hourlyVariation;
                
                $variation = rand(-20, 20);
                $turbidity = $baseTurbidity + $variation + (sin($i * 0.2) * 10); // Add some sine wave variation
                $value = max(0, $turbidity); // Ensure value is not negative

                TurbiditySensor::create([
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
     * Get seasonal turbidity based on month and day of year
     */
    private function getSeasonalTurbidity($month, $dayOfYear): float
    {
        // Turbidity can vary with seasons due to rainfall and water flow
        
        // Base turbidity level
        $baseTurbidity = 50;
        
        // Seasonal variation
        $seasonalVariation = 0;
        
        // Higher turbidity in rainy season (November-March) due to runoff
        if ($month >= 11 || $month <= 3) {
            $seasonalVariation = 30;
        } else {
            // Lower turbidity in dry season (April-October)
            $seasonalVariation = -10;
        }
        
        // Add some daily variation
        $dailyVariation = 10 * sin(2 * pi() * $dayOfYear / 365);
        
        return $baseTurbidity + $seasonalVariation + $dailyVariation;
    }
    
    /**
     * Get hourly turbidity variation
     */
    private function getHourlyTurbidityVariation($hour): float
    {
        // Turbidity can vary throughout the day
        // Slightly higher during peak activity hours
        if ($hour >= 8 && $hour <= 18) {
            return rand(5, 15);
        } else {
            return rand(-5, 5);
        }
    }
} 