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
    protected $signature = 'sensors:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed all sensor data (Temperature, Soil Moisture, Light, Turbidity)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to seed sensor data...');

        $seeders = [
            TemperatureSensorSeeder::class,
            SoilMoistureSensorSeeder::class,
            LightSensorSeeder::class,
            TurbiditySensorSeeder::class,
        ];

        foreach ($seeders as $seeder) {
            $this->info("Running {$seeder}...");
            $this->call('db:seed', ['--class' => $seeder]);
            $this->info("Completed {$seeder}");
        }

        $this->info('All sensor data seeded successfully!');
    }
}
