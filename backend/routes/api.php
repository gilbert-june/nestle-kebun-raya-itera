<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TemperatureSensorController;
use App\Http\Controllers\SoilMoistureSensorController;
use App\Http\Controllers\LightSensorController;
use App\Http\Controllers\TurbiditySensorController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\PlantTypeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Auth routes
Route::post('/logout', [AuthController::class, 'logout']);

// Get authenticated user
Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'user']);

// Temperature Sensor routes
Route::get('/temperature-sensors', [TemperatureSensorController::class, 'index']);
Route::get('/temperature-sensors/{sensorName}', [TemperatureSensorController::class, 'show']);
Route::post('/temperature-sensors', [TemperatureSensorController::class, 'store']);
Route::get('/temperature-sensors-names', [TemperatureSensorController::class, 'getSensorNames']);

// Soil Moisture Sensor routes
Route::get('/soil-moisture-sensors', [SoilMoistureSensorController::class, 'index']);
Route::get('/soil-moisture-sensors/{sensorName}', [SoilMoistureSensorController::class, 'show']);
Route::post('/soil-moisture-sensors', [SoilMoistureSensorController::class, 'store']);
Route::get('/soil-moisture-sensors-names', [SoilMoistureSensorController::class, 'getSensorNames']);

// Light Sensor routes
Route::get('/light-sensors', [LightSensorController::class, 'index']);
Route::get('/light-sensors/{sensorName}', [LightSensorController::class, 'show']);
Route::post('/light-sensors', [LightSensorController::class, 'store']);
Route::get('/light-sensors-names', [LightSensorController::class, 'getSensorNames']);

// Turbidity Sensor routes
Route::get('/turbidity-sensors', [TurbiditySensorController::class, 'index']);
Route::get('/turbidity-sensors/{sensorName}', [TurbiditySensorController::class, 'show']);
Route::post('/turbidity-sensors', [TurbiditySensorController::class, 'store']);
Route::get('/turbidity-sensors-names', [TurbiditySensorController::class, 'getSensorNames']);

Route::get('/alert', [AlertController::class, 'index']);
Route::post('/alert/update-alert', [AlertController::class, 'updateAlert']);

Route::get('/system', [SystemController::class, 'index']);
Route::put('/system/{id}/toggle-access', [SystemController::class, 'toggleActivate']);

// About routes
Route::get('/about', [AboutController::class, 'index']);
Route::put('/about', [AboutController::class, 'update']);

// Plant Type routes
Route::get('/plant-types', [PlantTypeController::class, 'index']);
Route::post('/plant-types', [PlantTypeController::class, 'store']);
Route::get('/plant-types/{id}', [PlantTypeController::class, 'show']);
Route::put('/plant-types/{id}', [PlantTypeController::class, 'update']);
Route::delete('/plant-types/{id}', [PlantTypeController::class, 'destroy']);
Route::put('/plant-types/{id}/images', [PlantTypeController::class, 'updateImages']);
Route::put('/plant-types/{id}/images/partial', [PlantTypeController::class, 'updateImagesPartial']);

// User Management routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::put('/users/{id}/toggle-access', [UserController::class, 'toggleAccess']);
});

// Export routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/export/stats', [ExportController::class, 'getExportStats']);
    Route::get('/export/temperature-sensors', [ExportController::class, 'exportTemperatureSensors']);
    Route::get('/export/soil-moisture-sensors', [ExportController::class, 'exportSoilMoistureSensors']);
    Route::get('/export/light-sensors', [ExportController::class, 'exportLightSensors']);
    Route::get('/export/turbidity-sensors', [ExportController::class, 'exportTurbiditySensors']);
    Route::get('/export/all-sensors', [ExportController::class, 'exportAllSensors']);
    
}); 
// Paginated data routes
Route::get('/export/temperature-sensors-data', [ExportController::class, 'getTemperatureSensorsData']);
Route::get('/export/soil-moisture-sensors-data', [ExportController::class, 'getSoilMoistureSensorsData']);
Route::get('/export/light-sensors-data', [ExportController::class, 'getLightSensorsData']);
Route::get('/export/turbidity-sensors-data', [ExportController::class, 'getTurbiditySensorsData']);