<?php

namespace App\Http\Controllers;

use App\Exports\TemperatureSensorsExport;
use App\Exports\SoilMoistureSensorsExport;
use App\Exports\LightSensorsExport;
use App\Exports\TurbiditySensorsExport;
use App\Exports\AllSensorsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExportController extends Controller
{
    /**
     * Export all temperature sensors data to Excel
     */
    public function exportTemperatureSensors(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        $filename = 'temperature_sensors_' . date('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new TemperatureSensorsExport($startDate, $endDate), $filename);
    }

    /**
     * Export all soil moisture sensors data to Excel
     */
    public function exportSoilMoistureSensors(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        $filename = 'soil_moisture_sensors_' . date('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new SoilMoistureSensorsExport($startDate, $endDate), $filename);
    }

    /**
     * Export all light sensors data to Excel
     */
    public function exportLightSensors(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        $filename = 'light_sensors_' . date('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new LightSensorsExport($startDate, $endDate), $filename);
    }

    /**
     * Export all turbidity sensors data to Excel
     */
    public function exportTurbiditySensors(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        $filename = 'turbidity_sensors_' . date('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new TurbiditySensorsExport($startDate, $endDate), $filename);
    }

    /**
     * Export all sensors data to Excel with multiple sheets
     */
    public function exportAllSensors(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        $filename = 'all_sensors_data_' . date('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new AllSensorsExport($startDate, $endDate), $filename);
    }

    /**
     * Get export statistics for the export page
     */
    public function getExportStats(): JsonResponse
    {
        try {
            $stats = [
                'temperature_sensors' => [
                    'count' => \App\Models\TemperatureSensor::count(),
                    'latest_date' => \App\Models\TemperatureSensor::max('created_at'),
                    'sensor_names' => \App\Models\TemperatureSensor::distinct('name')->pluck('name')
                ],
                'soil_moisture_sensors' => [
                    'count' => \App\Models\SoilMoistureSensor::count(),
                    'latest_date' => \App\Models\SoilMoistureSensor::max('created_at'),
                    'sensor_names' => \App\Models\SoilMoistureSensor::distinct('name')->pluck('name')
                ],
                'light_sensors' => [
                    'count' => \App\Models\LightSensor::count(),
                    'latest_date' => \App\Models\LightSensor::max('created_at'),
                    'sensor_names' => \App\Models\LightSensor::distinct('name')->pluck('name')
                ],
                'turbidity_sensors' => [
                    'count' => \App\Models\TurbiditySensor::count(),
                    'latest_date' => \App\Models\TurbiditySensor::max('created_at'),
                    'sensor_names' => \App\Models\TurbiditySensor::distinct('name')->pluck('name')
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch export statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get paginated temperature sensors data with date filtering
     */
    public function getTemperatureSensorsData(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $sensorName = $request->get('sensor_name');

            $query = \App\Models\TemperatureSensor::query();

            if ($startDate) {
                $query->where('created_at', '>=', $startDate . ' 00:00:00');
            }

            if ($endDate) {
                $query->where('created_at', '<=', $endDate . ' 23:59:59');
            }

            if ($sensorName) {
                $query->where('name', 'like', '%' . $sensorName . '%');
            }

            $data = $query->orderBy('created_at', 'desc')
                         ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch temperature sensors data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get paginated soil moisture sensors data with date filtering
     */
    public function getSoilMoistureSensorsData(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $sensorName = $request->get('sensor_name');

            $query = \App\Models\SoilMoistureSensor::query();

            if ($startDate) {
                $query->where('created_at', '>=', $startDate . ' 00:00:00');
            }

            if ($endDate) {
                $query->where('created_at', '<=', $endDate . ' 23:59:59');
            }

            if ($sensorName) {
                $query->where('name', 'like', '%' . $sensorName . '%');
            }

            $data = $query->orderBy('created_at', 'desc')
                         ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch soil moisture sensors data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get paginated light sensors data with date filtering
     */
    public function getLightSensorsData(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $sensorName = $request->get('sensor_name');

            $query = \App\Models\LightSensor::query();

            if ($startDate) {
                $query->where('created_at', '>=', $startDate . ' 00:00:00');
            }

            if ($endDate) {
                $query->where('created_at', '<=', $endDate . ' 23:59:59');
            }

            if ($sensorName) {
                $query->where('name', 'like', '%' . $sensorName . '%');
            }

            $data = $query->orderBy('created_at', 'desc')
                         ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch light sensors data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get paginated turbidity sensors data with date filtering
     */
    public function getTurbiditySensorsData(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $sensorName = $request->get('sensor_name');

            $query = \App\Models\TurbiditySensor::query();

            if ($startDate) {
                $query->where('created_at', '>=', $startDate . ' 00:00:00');
            }

            if ($endDate) {
                $query->where('created_at', '<=', $endDate . ' 23:59:59');
            }

            if ($sensorName) {
                $query->where('name', 'like', '%' . $sensorName . '%');
            }

            $data = $query->orderBy('created_at', 'desc')
                         ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch turbidity sensors data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 