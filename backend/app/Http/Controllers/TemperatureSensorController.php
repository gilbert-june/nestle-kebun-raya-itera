<?php

namespace App\Http\Controllers;

use App\Models\TemperatureSensor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TemperatureSensorController extends Controller
{
    /**
     * Get all temperature sensors data for dashboard
     */
    public function index(): JsonResponse
    {
        try {
            // Get all unique sensor names
            $sensorNames = TemperatureSensor::getSensorNames();
            
            $sensorsData = [];
            
            foreach ($sensorNames as $sensorName) {
                // Get latest reading for each sensor
                $latestReading = TemperatureSensor::getLatestReading($sensorName);
                
                // Get readings for the last 30 minutes for chart
                $readings = TemperatureSensor::getReadingsForSensor($sensorName, 0.5);
                
                $sensorsData[] = [
                    'name' => $sensorName,
                    'latest_value' => $latestReading ? (float) $latestReading->value : null,
                    'latest_timestamp' => $latestReading ? $latestReading->created_at : null,
                    'chart_data' => $readings->map(function ($reading) {
                        return [
                            'x' => $reading->created_at->format('Y-m-d H:i:s'),
                            'y' => (float) $reading->value
                        ];
                    })
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $sensorsData
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch temperature sensor data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get data for a specific sensor
     */
    public function show(string $sensorName): JsonResponse
    {
        try {
            $readings = TemperatureSensor::getReadingsForSensor($sensorName, 0.5);
            
            $chartData = $readings->map(function ($reading) {
                return [
                    'x' => $reading->created_at->format('Y-m-d H:i:s'),
                    'y' => (float) $reading->value
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'sensor_name' => $sensorName,
                    'readings' => $chartData
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sensor data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new temperature reading
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'value' => 'required|numeric|between:-50,100'
            ]);

            $allowedNames = ['Sensor Suhu 1'];
            if(!in_array($request->name, $allowedNames)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid sensor name'
                ], 400);
            }
            
            $sensor = TemperatureSensor::create([
                'name' => $request->name,
                'value' => $request->value
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Temperature reading stored successfully',
                'data' => $sensor
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to store temperature reading',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sensor names for dropdown/selection
     */
    public function getSensorNames(): JsonResponse
    {
        try {
            $sensorNames = TemperatureSensor::getSensorNames();
            
            return response()->json([
                'success' => true,
                'data' => $sensorNames
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sensor names',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Store multiple temperature readings in batch
     */
    public function storeBatch(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'readings' => 'required|array',
                'readings.*.name' => 'required|string|max:255',
                'readings.*.value' => 'required|numeric|between:-50,100'
            ]);
            
            $readings = $request->readings;
            $savedReadings = [];
            
            foreach ($readings as $reading) {
                $sensor = TemperatureSensor::create([
                    'name' => $reading['name'],
                    'value' => $reading['value']
                ]);
                
                $savedReadings[] = $sensor;
            }
            
            return response()->json([
                'success' => true,
                'message' => count($savedReadings) . ' temperature readings stored successfully',
                'data' => $savedReadings
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to store temperature readings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
