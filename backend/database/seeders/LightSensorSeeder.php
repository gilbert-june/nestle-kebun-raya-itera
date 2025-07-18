<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LightSensor;
use Carbon\Carbon;

class LightSensorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sensorNames = ['Sensor Cahaya 1'];
        
        // Set end time as now, and start time as 24 hours ago
        $endTime = Carbon::now();
        $startTime = $endTime->copy()->subHours(24);
        $intervalSeconds = 30; // 2 data per minute
        $totalData = (24 * 60 * 60) / $intervalSeconds; // 24 hours worth of data, every 30 seconds

        foreach ($sensorNames as $sensorName) {
            $currentTime = $startTime->copy();
            for ($i = 0; $i < $totalData; $i++) {
                $hour = $currentTime->hour;
                
                // Generate realistic light intensity values based on time of day
                if ($hour >= 6 && $hour <= 18) {
                    // Daytime: higher light intensity
                    $baseValue = rand(500, 1000); // 500-1000 lux during day
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
} 