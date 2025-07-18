<?php

namespace App\Http\Controllers;

use App\Models\SoilMoistureSensor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SoilMoistureSensorController extends Controller
{
    /**
     * Get all soil moisture sensors data for dashboard
     */
    public function index(): JsonResponse
    {
        try {
            // Get all unique sensor names
            $sensorNames = SoilMoistureSensor::getSensorNames();
            
            $sensorsData = [];
            
            foreach ($sensorNames as $sensorName) {
                // Get latest reading for each sensor
                $latestReading = SoilMoistureSensor::getLatestReading($sensorName);
                
                // Get readings for the last 30 minutes for chart
                $readings = SoilMoistureSensor::getReadingsForSensor($sensorName, 0.5);
                
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
                'message' => 'Failed to fetch soil moisture sensor data',
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
            $readings = SoilMoistureSensor::getReadingsForSensor($sensorName, 0.5);
            
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
     * Store a new soil moisture reading
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'value' => 'required|numeric|between:0,100'
            ]);

            $allowedNames = ['Sensor Kelembaban Tanah 1'];
            if(!in_array($request->name, $allowedNames)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid sensor name'
                ], 400);
            }
            
            $sensor = SoilMoistureSensor::create([
                'name' => $request->name,
                'value' => $request->value
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Soil moisture reading stored successfully',
                'data' => $sensor
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to store soil moisture reading',
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
            $sensorNames = SoilMoistureSensor::getSensorNames();
            
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
} 