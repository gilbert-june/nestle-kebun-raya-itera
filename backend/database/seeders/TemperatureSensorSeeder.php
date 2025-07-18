<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TemperatureSensor;
use Carbon\Carbon;

class TemperatureSensorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sensorNames = ['Sensor Suhu 1'];
        
        // Set end time as now, and start time as 24 hours ago
        $endTime = Carbon::now();
        $startTime = $endTime->copy()->subHours(24);
        $intervalSeconds = 30; // 2 data per minute
        $totalData = (24 * 60 * 60) / $intervalSeconds; // 24 hours worth of data, every 15 seconds

        foreach ($sensorNames as $sensorName) {
            $currentTime = $startTime->copy();
            for ($i = 0; $i < $totalData; $i++) {
                $baseTemp = mt_rand(20, 35);
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
}
