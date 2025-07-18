<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TurbiditySensor;
use Carbon\Carbon;

class TurbiditySensorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sensorNames = ['Sensor Turbiditas 1'];
        
        // Set end time as now, and start time as 24 hours ago
        $endTime = Carbon::now();
        $startTime = $endTime->copy()->subHours(24);
        $intervalSeconds = 30; // 2 data per minute
        $totalData = (24 * 60 * 60) / $intervalSeconds; // 24 hours worth of data, every 30 seconds

        foreach ($sensorNames as $sensorName) {
            $currentTime = $startTime->copy();
            for ($i = 0; $i < $totalData; $i++) {
                // Generate realistic turbidity values (0-4000 NTU)
                $baseTurbidity = mt_rand(10, 100); // Base turbidity level
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
} 