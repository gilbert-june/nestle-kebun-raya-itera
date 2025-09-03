<?php

namespace App\Http\Controllers;

use App\Exports\TemperatureSensorsExport;
use App\Exports\SoilMoistureSensorsExport;
use App\Exports\LightSensorsExport;
use App\Exports\TurbiditySensorsExport;
use App\Exports\AllSensorsExport;
use App\Models\SensorHistoryExcelFile;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    /**
     * Export all temperature sensors data to Excel
     */
    public function exportTemperatureSensors(Request $request)
    {
        $startDay = $request->get('start_day');
        $endDay = $request->get('end_day');
        
        // Convert day to full date for current month/year
        $currentYear = date('Y');
        $currentMonth = date('m');
        $startDate = $startDay ? sprintf('%s-%s-%02d', $currentYear, $currentMonth, $startDay) : null;
        $endDate = $endDay ? sprintf('%s-%s-%02d', $currentYear, $currentMonth, $endDay) : null;
        
        $filename = 'temperature_sensors_' . date('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new TemperatureSensorsExport($startDate, $endDate), $filename);
    }

    /**
     * Export all soil moisture sensors data to Excel
     */
    public function exportSoilMoistureSensors(Request $request)
    {
        $startDay = $request->get('start_day');
        $endDay = $request->get('end_day');
        
        // Convert day to full date for current month/year
        $currentYear = date('Y');
        $currentMonth = date('m');
        $startDate = $startDay ? sprintf('%s-%s-%02d', $currentYear, $currentMonth, $startDay) : null;
        $endDate = $endDay ? sprintf('%s-%s-%02d', $currentYear, $currentMonth, $endDay) : null;
        
        $filename = 'soil_moisture_sensors_' . date('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new SoilMoistureSensorsExport($startDate, $endDate), $filename);
    }

    /**
     * Export all light sensors data to Excel
     */
    public function exportLightSensors(Request $request)
    {
        $startDay = $request->get('start_day');
        $endDay = $request->get('end_day');
        
        // Convert day to full date for current month/year
        $currentYear = date('Y');
        $currentMonth = date('m');
        $startDate = $startDay ? sprintf('%s-%s-%02d', $currentYear, $currentMonth, $startDay) : null;
        $endDate = $endDay ? sprintf('%s-%s-%02d', $currentYear, $currentMonth, $endDay) : null;
        
        $filename = 'light_sensors_' . date('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new LightSensorsExport($startDate, $endDate), $filename);
    }

    /**
     * Export all turbidity sensors data to Excel
     */
    public function exportTurbiditySensors(Request $request)
    {
        $startDay = $request->get('start_day');
        $endDay = $request->get('end_day');
        
        // Convert day to full date for current month/year
        $currentYear = date('Y');
        $currentMonth = date('m');
        $startDate = $startDay ? sprintf('%s-%s-%02d', $currentYear, $currentMonth, $startDay) : null;
        $endDate = $endDay ? sprintf('%s-%s-%02d', $currentYear, $currentMonth, $endDay) : null;
        
        $filename = 'turbidity_sensors_' . date('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new TurbiditySensorsExport($startDate, $endDate), $filename);
    }

    /**
     * Export all sensors data to Excel with multiple sheets
     */
    public function exportAllSensors(Request $request)
    {
        $startDay = $request->get('start_day');
        $endDay = $request->get('end_day');
        
        // Convert day to full date for current month/year
        $currentYear = date('Y');
        $currentMonth = date('m');
        $startDate = $startDay ? sprintf('%s-%s-%02d', $currentYear, $currentMonth, $startDay) : null;
        $endDate = $endDay ? sprintf('%s-%s-%02d', $currentYear, $currentMonth, $endDay) : null;
        
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
            $startDay = $request->get('start_day');
            $endDay = $request->get('end_day');
            $sensorName = $request->get('sensor_name');

            $query = \App\Models\TemperatureSensor::query();

            // Get current month and year
            $currentYear = date('Y');
            $currentMonth = date('m');

            if ($startDay) {
                $startDate = sprintf('%s-%s-%02d', $currentYear, $currentMonth, $startDay);
                $query->where('created_at', '>=', $startDate . ' 00:00:00');
            }

            if ($endDay) {
                $endDate = sprintf('%s-%s-%02d', $currentYear, $currentMonth, $endDay);
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
            $startDay = $request->get('start_day');
            $endDay = $request->get('end_day');
            $sensorName = $request->get('sensor_name');

            $query = \App\Models\SoilMoistureSensor::query();

            // Get current month and year
            $currentYear = date('Y');
            $currentMonth = date('m');

            if ($startDay) {
                $startDate = sprintf('%s-%s-%02d', $currentYear, $currentMonth, $startDay);
                $query->where('created_at', '>=', $startDate . ' 00:00:00');
            }

            if ($endDay) {
                $endDate = sprintf('%s-%s-%02d', $currentYear, $currentMonth, $endDay);
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
            $startDay = $request->get('start_day');
            $endDay = $request->get('end_day');
            $sensorName = $request->get('sensor_name');

            $query = \App\Models\LightSensor::query();

            // Get current month and year
            $currentYear = date('Y');
            $currentMonth = date('m');

            if ($startDay) {
                $startDate = sprintf('%s-%s-%02d', $currentYear, $currentMonth, $startDay);
                $query->where('created_at', '>=', $startDate . ' 00:00:00');
            }

            if ($endDay) {
                $endDate = sprintf('%s-%s-%02d', $currentYear, $currentMonth, $endDay);
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
            $startDay = $request->get('start_day');
            $endDay = $request->get('end_day');
            $sensorName = $request->get('sensor_name');

            $query = \App\Models\TurbiditySensor::query();

            // Get current month and year
            $currentYear = date('Y');
            $currentMonth = date('m');

            if ($startDay) {
                $startDate = sprintf('%s-%s-%02d', $currentYear, $currentMonth, $startDay);
                $query->where('created_at', '>=', $startDate . ' 00:00:00');
            }

            if ($endDay) {
                $endDate = sprintf('%s-%s-%02d', $currentYear, $currentMonth, $endDay);
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

    /**
     * Get paginated exported files data
     */
    public function getExportedFiles(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);
            $sensorName = $request->get('sensor_name');
            $date = $request->get('date');

            $query = SensorHistoryExcelFile::query();

            if ($sensorName) {
                $query->where('sensor_name', 'like', '%' . $sensorName . '%');
            }

            if ($date) {
                $query->where('date', $date);
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
                'message' => 'Failed to fetch exported files data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download exported file
     */
    public function downloadExportedFile($id)
    {
        try {
            $file = SensorHistoryExcelFile::findOrFail($id);

            if (!Storage::exists($file->file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            // Increment download count
            $file->increment('download_count');

            return Storage::download($file->file_path);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete exported file
     */
    public function deleteExportedFile($id): JsonResponse
    {
        try {
            $file = SensorHistoryExcelFile::findOrFail($id);

            if (Storage::exists($file->file_path)) {
                Storage::delete($file->file_path);
            }

            $file->delete();

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 