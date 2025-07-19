<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Exports\AllSensorsExport;
use App\Exports\TemperatureSensorsExport;
use App\Exports\SoilMoistureSensorsExport;
use App\Exports\LightSensorsExport;
use App\Exports\TurbiditySensorsExport;
use App\Models\SensorHistoryExcelFile;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class MonthlySensorExport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sensors:export-monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export all sensor data to Excel files monthly and store file information';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting monthly sensor data export...');

        try {
            // Get the previous month's date range
            $previousMonth = Carbon::now()->subMonth();
            $startDate = $previousMonth->startOfMonth()->format('Y-m-d');
            $endDate = $previousMonth->endOfMonth()->format('Y-m-d');
            $monthYear = $previousMonth->format('Y-m');

            $this->info("Exporting data for {$startDate} to {$endDate}");

            // Create storage directory if it doesn't exist
            $storagePath = 'private/exports/monthly';
            if (!Storage::exists($storagePath)) {
                Storage::makeDirectory($storagePath);
            }

            // Export all sensors data
            $allSensorsFilename = "all_sensors_{$monthYear}.xlsx";
            $allSensorsPath = "{$storagePath}/{$allSensorsFilename}";
            
            $this->info("Exporting all sensors data to {$allSensorsFilename}...");
            Excel::store(new AllSensorsExport($startDate, $endDate), $allSensorsPath);

            // Store file information in database
            SensorHistoryExcelFile::create([
                'sensor_name' => 'All Sensors',
                'file_path' => $allSensorsPath,
                'date' => $monthYear,
                'file_size' => Storage::size($allSensorsPath),
                'download_count' => 0,
            ]);

            // Export individual sensor types
            $sensorTypes = [
                'Temperature' => new TemperatureSensorsExport($startDate, $endDate),
                'Soil Moisture' => new SoilMoistureSensorsExport($startDate, $endDate),
                'Light' => new LightSensorsExport($startDate, $endDate),
                'Turbidity' => new TurbiditySensorsExport($startDate, $endDate),
            ];

            foreach ($sensorTypes as $sensorType => $exportClass) {
                $filename = strtolower(str_replace(' ', '_', $sensorType)) . "_{$monthYear}.xlsx";
                $filePath = "{$storagePath}/{$filename}";
                
                $this->info("Exporting {$sensorType} sensors data to {$filename}...");
                Excel::store($exportClass, $filePath);

                // Store file information in database
                SensorHistoryExcelFile::create([
                    'sensor_name' => $sensorType,
                    'file_path' => $filePath,
                    'date' => $monthYear,
                    'file_size' => Storage::size($filePath),
                    'download_count' => 0,
                ]);
            }

            $this->info('Monthly sensor data export completed successfully!');
            $this->info("Files saved in: storage/app/{$storagePath}");

        } catch (\Exception $e) {
            $this->error('Failed to export sensor data: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
} 