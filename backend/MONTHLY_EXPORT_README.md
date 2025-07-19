# Monthly Sensor Data Export System

This system automatically exports all sensor data to Excel files on the first day of each month at 2:00 AM.

## Features

- **Automated Monthly Export**: Runs automatically on the 1st day of each month at 2:00 AM
- **Multiple File Types**: Exports individual sensor types and combined data
- **File Management**: Track, download, and delete exported files
- **Download Tracking**: Counts how many times each file has been downloaded
- **File Size Tracking**: Automatically calculates and stores file sizes

## Files Created

For each month, the system creates 5 Excel files:

1. `all_sensors_YYYY-MM.xlsx` - Combined data from all sensor types
2. `temperature_YYYY-MM.xlsx` - Temperature sensor data only
3. `soil_moisture_YYYY-MM.xlsx` - Soil moisture sensor data only
4. `light_YYYY-MM.xlsx` - Light sensor data only
5. `turbidity_YYYY-MM.xlsx` - Turbidity sensor data only

## Storage Location

Files are stored in: `storage/app/private/exports/monthly/`

## Database Schema

The `sensor_histories_excel_files` table stores:

- `id` - Primary key
- `sensor_name` - Name of the sensor type or "All Sensors"
- `file_path` - Path to the stored file
- `date` - Month/Year in YYYY-MM format
- `file_size` - File size in bytes
- `download_count` - Number of times the file has been downloaded
- `created_at` - When the file was created
- `updated_at` - When the record was last updated

## Console Commands

### Manual Export
```bash
php artisan sensors:export-monthly
```

This command can be run manually to export data for the previous month.

### Scheduled Job
The job is automatically scheduled to run monthly:
```php
Schedule::command('sensors:export-monthly')
    ->monthlyOn(1, '02:00')
    ->description('Export all sensor data to Excel files monthly');
```

## API Endpoints

### Get Exported Files
```
GET /api/export/files
```
Returns paginated list of exported files with optional filtering.

### Download File
```
GET /api/export/files/{id}/download
```
Downloads a specific exported file and increments the download count.

### Delete File
```
DELETE /api/export/files/{id}
```
Deletes a specific exported file from storage and database.

## Frontend Integration

The export page now includes a "File Ekspor Tersimpan" (Saved Export Files) section that displays:

- File list with pagination
- Filter by sensor name and date
- Download and delete actions
- File size and download count information

## Configuration

### Storage Configuration
Files are stored using Laravel's storage system. Make sure the storage directory is writable:

```bash
chmod -R 775 storage/
```

### Schedule Configuration
The job is configured in `routes/console.php` and will run automatically if you have a cron job set up:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Troubleshooting

### Check Scheduled Jobs
```bash
php artisan schedule:list
```

### Test Manual Export
```bash
php artisan sensors:export-monthly
```

### Check File Storage
```bash
ls -la storage/app/private/exports/monthly/
```

### Check Database Records
```bash
php artisan tinker
>>> App\Models\SensorHistoryExcelFile::all();
```

## Security

- All API endpoints require authentication
- Files are stored in the private storage directory
- Download tracking helps monitor file usage
- Files can be deleted by authenticated users only 