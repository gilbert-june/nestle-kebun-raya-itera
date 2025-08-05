# Sensor Data Seeding

This document explains how to seed sensor data for the Nestle Kebun Raya project.

## Overview

The sensor seeding system allows you to generate realistic sensor data for any month of the year. The data includes seasonal variations appropriate for Indonesia's tropical climate.

## Available Sensors

- **Temperature Sensor** (`Sensor Suhu`) - Temperature in Celsius
- **Soil Moisture Sensor** (`Sensor Kelembaban Tanah`) - Moisture percentage (0-100%)
- **Light Sensor** (`Sensor Cahaya`) - Light intensity in lux
- **Turbidity Sensor** (`Sensor Kekeruhan`) - Turbidity in NTU

## Usage

### Basic Command (Current Month)

To seed sensor data for the current month:

```bash
php artisan sensors:seed
```

### Specific Month

To seed sensor data for a specific month, use the `--month` option:

```bash
php artisan sensors:seed --month=6
```

The month parameter accepts values from 1-12:
- `1` = January
- `2` = February
- `3` = March
- `4` = April
- `5` = May
- `6` = June
- `7` = July
- `8` = August
- `9` = September
- `10` = October
- `11` = November
- `12` = December

## Examples

```bash
# Seed data for June
php artisan sensors:seed --month=6

# Seed data for December
php artisan sensors:seed --month=12

# Seed data for current month
php artisan sensors:seed
```

## Data Characteristics

### Seasonal Variations

The generated data includes realistic seasonal variations based on Indonesia's climate:

- **Dry Season (April-October)**: Higher light intensity, slightly warmer temperatures, lower soil moisture, lower turbidity
- **Rainy Season (November-March)**: Lower light intensity, slightly cooler temperatures, higher soil moisture, higher turbidity

### Data Frequency

- Data is generated every 30 seconds
- Covers the entire specified month (from 1st to last day)
- Includes realistic daily and hourly variations

### Value Ranges

- **Temperature**: 20-35°C with seasonal and daily variations
- **Soil Moisture**: 0-100% with higher values during rainy season
- **Light Intensity**: 0-1200 lux with day/night cycles and seasonal factors
- **Turbidity**: 0-100+ NTU with higher values during rainy season

## Error Handling

The command validates the month parameter:
- Must be a number between 1 and 12
- Returns an error message for invalid values

## Database Impact

- Each run generates approximately 86,400 data points per sensor (one per 30 seconds for a full month)
- Data is created with realistic timestamps
- Existing data is not cleared - new data is added

## Notes

- The system uses the current year for data generation
- All timestamps are in the application's configured timezone
- Data includes natural variations and noise to simulate real sensor readings 