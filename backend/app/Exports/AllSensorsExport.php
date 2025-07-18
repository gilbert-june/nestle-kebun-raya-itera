<?php

namespace App\Exports;

use App\Models\TemperatureSensor;
use App\Models\SoilMoistureSensor;
use App\Models\LightSensor;
use App\Models\TurbiditySensor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AllSensorsExport implements WithMultipleSheets
{
    private $startDate;
    private $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function sheets(): array
    {
        return [
            'Temperature Sensors' => new TemperatureSensorsExport($this->startDate, $this->endDate),
            'Soil Moisture Sensors' => new SoilMoistureSensorsExport($this->startDate, $this->endDate),
            'Light Sensors' => new LightSensorsExport($this->startDate, $this->endDate),
            'Turbidity Sensors' => new TurbiditySensorsExport($this->startDate, $this->endDate),
        ];
    }
} 