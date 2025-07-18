<?php

namespace App\Exports;

use App\Models\TemperatureSensor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TemperatureSensorsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    private $startDate;
    private $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $query = TemperatureSensor::query();

        if ($this->startDate) {
            $query->where('created_at', '>=', $this->startDate . ' 00:00:00');
        }

        if ($this->endDate) {
            $query->where('created_at', '<=', $this->endDate . ' 23:59:59');
        }

        return $query->orderBy('name')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Sensor Name',
            'Temperature (°C)',
            'Timestamp',
            'Date',
            'Time'
        ];
    }

    public function map($sensor): array
    {
        return [
            $sensor->name,
            $sensor->value,
            $sensor->created_at->format('Y-m-d H:i:s'),
            $sensor->created_at->format('Y-m-d'),
            $sensor->created_at->format('H:i:s')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E3F2FD']
                ]
            ]
        ];
    }
} 