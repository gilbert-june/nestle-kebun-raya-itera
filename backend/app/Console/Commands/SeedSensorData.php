<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\TemperatureSensorSeeder;
use Database\Seeders\SoilMoistureSensorSeeder;
use Database\Seeders\LightSensorSeeder;
use Database\Seeders\TurbiditySensorSeeder;

class SeedSensorData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sensors:seed {--month= : Month number (1-12) for which to generate data. If not provided, uses current month}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed all sensor data (Temperature, Soil Moisture, Light, Turbidity) for a specific month';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $month = $this->option('month');
        
        if ($month) {
            // Validate month input
            if (!is_numeric($month) || $month < 1 || $month > 12) {
                $this->error('Month must be a number between 1 and 12');
                return 1;
            }
            
            $monthNames = [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
            ];
            
            $this->info("Starting to seed sensor data for {$monthNames[$month]}...");
        } else {
            $this->info('Starting to seed sensor data for current month...');
        }

        $seeders = [
            TemperatureSensorSeeder::class,
            SoilMoistureSensorSeeder::class,
            LightSensorSeeder::class,
            TurbiditySensorSeeder::class,
        ];

        foreach ($seeders as $seeder) {
            $this->info("Running {$seeder}...");
            
            // Create seeder instance
            $seederInstance = new $seeder();
            
            // Set the month parameter if provided
            if ($month) {
                $seederInstance->setMonth($month);
            }
            
            $seederInstance->run();
            $this->info("Completed {$seeder}");
        }

        $this->info('All sensor data seeded successfully!');
    }
}
