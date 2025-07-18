<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SoilMoistureSensor;
use Carbon\Carbon;

class SoilMoistureSensorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sensorNames = ['Sensor Kelembaban Tanah 1'];
        
        // Set end time as now, and start time as 24 hours ago
        $endTime = Carbon::now();
        $startTime = $endTime->copy()->subHours(24);
        $intervalSeconds = 30; // 2 data per minute
        $totalData = (24 * 60 * 60) / $intervalSeconds; // 24 hours worth of data, every 30 seconds

        foreach ($sensorNames as $sensorName) {
            $currentTime = $startTime->copy();
            for ($i = 0; $i < $totalData; $i++) {
                // Generate realistic soil moisture values (0-100%)
                $baseMoisture = mt_rand(30, 70); // Base moisture level
                $variation = rand(-10, 10);
                $moisture = $baseMoisture + $variation + (sin($i * 0.3) * 5); // Add some sine wave variation
                $value = max(0, min(100, $moisture)); // Ensure value is between 0-100

                SoilMoistureSensor::create([
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