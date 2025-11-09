import { Component, OnInit, OnDestroy, HostListener } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClientModule } from '@angular/common/http';
import { Router } from '@angular/router';
import { AuthService, User } from '../auth.service';
import { AboutService, About } from '../about.service';
import { PlantTypeService, PlantType } from '../plant-type.service';
import { Observable, catchError, of, map, interval, Subscription } from 'rxjs';
import { LayoutComponent } from '../shared/layout/layout';
import { environment } from '../../environments/environment';
import { PlantTypeImage } from '../plant-type.service';
import { TemperatureSensorService, SensorData } from '../temperature-sensor.service';
import { SoilMoistureSensorService, SensorData as SoilMoistureSensorData } from '../soil-moisture-sensor.service';
import { LightSensorService, SensorData as LightSensorData } from '../light-sensor.service';
import { TurbiditySensorService, SensorData as TurbiditySensorData } from '../turbidity-sensor.service';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import advancedFormat from 'dayjs/plugin/advancedFormat';
import { NgApexchartsModule } from 'ng-apexcharts';
import { PaginationComponent } from '../shared/pagination/pagination';

dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.extend(advancedFormat);
// Table data interfaces
interface SensorTableData {
  id: number;
  name: string;
  value: number;
  created_at: string;
  updated_at: string;
}

interface PaginatedData {
  current_page: number;
  data: SensorTableData[];
  first_page_url: string;
  from: number;
  last_page: number;
  last_page_url: string;
  links: any[];
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number;
  total: number;
}

@Component({
  selector: 'app-landing',
  standalone: true,
  imports: [CommonModule, HttpClientModule, LayoutComponent, NgApexchartsModule, PaginationComponent],
  templateUrl: './landing.html',
  styleUrl: './landing.css'
})
export class LandingComponent implements OnInit, OnDestroy {
  mobileMenuOpen = false;
  user$: Observable<User | null>;
  about$: Observable<About | null>;
  plantTypes$: Observable<PlantType[]>;
  plantTypes: PlantType[] = [];
  environment = environment;
  currentPlantTypeIndex = 0;
  isLoadingPlantTypes = true;
  
  // Modal properties
  isImageModalOpen = false;
  currentImageIndex = 0;
  currentPlantTypeImages: PlantTypeImage[] = [];

  error = '';

  // Auto-refresh subscription
  private dataRefreshSubscription?: Subscription;

  // Temperature sensor data
  sensorsData: SensorData[] = [];
  // Soil moisture sensor data
  soilMoistureSensorsData: SoilMoistureSensorData[] = [];
  // Light sensor data
  lightSensorsData: LightSensorData[] = [];
  // Turbidity sensor data
  turbiditySensorsData: TurbiditySensorData[] = [];


  // Table data
  temperatureTableData: PaginatedData | null = null;
  soilMoistureTableData: PaginatedData | null = null;
  lightTableData: PaginatedData | null = null;
  turbidityTableData: PaginatedData | null = null;

  // Loading states for tables
  temperatureTableLoading = false;
  soilMoistureTableLoading = false;
  lightTableLoading = false;
  turbidityTableLoading = false;

  // Chart color schemes for different sensors
  private chartColors = [
    ['#22c55e', '#4ade80', '#16a34a'], // Green shades
    ['#3b82f6', '#60a5fa', '#1d4ed8'], // Blue shades
    ['#f59e0b', '#fbbf24', '#d97706'], // Orange shades
    ['#ef4444', '#f87171', '#dc2626'], // Red shades
    ['#8b5cf6', '#a78bfa', '#7c3aed'], // Purple shades
    ['#06b6d4', '#22d3ee', '#0891b2']  // Cyan shades
  ];

  constructor(
    private router: Router,
    private authService: AuthService,
    private aboutService: AboutService,
    private plantTypeService: PlantTypeService,
    private temperatureSensorService: TemperatureSensorService,
    private soilMoistureSensorService: SoilMoistureSensorService,
    private lightSensorService: LightSensorService,
    private turbiditySensorService: TurbiditySensorService
  ) {
    this.user$ = this.authService.currentUser$;
    this.about$ = this.aboutService.getAbout().pipe(
      map(response => response.data),
      catchError(error => {
        console.error('Error fetching about data:', error);
        return of(null);
      })
    );
    this.plantTypes$ = this.plantTypeService.getPlantTypes().pipe(
      map(response => response.data),
      catchError(error => {
        console.error('Error fetching plant types data:', error);
        return of([]);
      })
    );
  }

  ngOnInit(): void {
    // Check authentication status when component initializes
    this.authService.checkAuth().subscribe();
    
    // Subscribe to plant types and store them locally
    this.plantTypes$.subscribe(plantTypes => {
      this.plantTypes = plantTypes;
      this.isLoadingPlantTypes = false;
    });

    // Load all sensor data after user is loaded
    this.loadTemperatureSensors();
    this.loadSoilMoistureSensors();
    this.loadLightSensors();
    this.loadTurbiditySensors();

    // Load table data
    this.loadTemperatureTableData();
    this.loadSoilMoistureTableData();
    this.loadLightTableData();
    this.loadTurbidityTableData();

    // Start auto-refresh for graph data every 3 seconds
    this.startDataRefresh();
  }

  ngOnDestroy(): void {
    // Clean up subscription when component is destroyed
    if (this.dataRefreshSubscription) {
      this.dataRefreshSubscription.unsubscribe();
    }
  }

  startDataRefresh(): void {
    // Unsubscribe from existing subscription if any
    if (this.dataRefreshSubscription) {
      this.dataRefreshSubscription.unsubscribe();
    }
    // Append new graph data and refresh table data every 3 seconds (3000ms)
    this.dataRefreshSubscription = interval(3000).subscribe(() => {
      // Append new data to charts
      this.appendNewTemperatureData();
      this.appendNewSoilMoistureData();
      this.appendNewLightData();
      this.appendNewTurbidityData();
      
      // Refresh table data (preserve current page)
      const tempPage = this.temperatureTableData?.current_page || 1;
      const soilPage = this.soilMoistureTableData?.current_page || 1;
      const lightPage = this.lightTableData?.current_page || 1;
      const turbidityPage = this.turbidityTableData?.current_page || 1;
      
      this.loadTemperatureTableData(tempPage);
      this.loadSoilMoistureTableData(soilPage);
      this.loadLightTableData(lightPage);
      this.loadTurbidityTableData(turbidityPage);
    });
  }

  // Helper method to get the last timestamp from chart_data
  private getLastTimestamp(chartData: any[]): string | null {
    if (!chartData || chartData.length === 0) return null;
    return chartData[chartData.length - 1]?.x || null;
  }

  // Append new temperature sensor data
  appendNewTemperatureData(): void {
    this.temperatureSensorService.getTemperatureSensors().subscribe({
      next: (response) => {
        if (response.success && response.data) {
          response.data.forEach((newSensor) => {
            const existingSensor = this.sensorsData.find(s => s.name === newSensor.name);
            
            if (existingSensor) {
              // Get the last timestamp we have
              const lastTimestamp = this.getLastTimestamp(existingSensor.chart_data);
              
              // Find new data points (those with timestamps after our last one)
              const newDataPoints = newSensor.chart_data.filter(point => {
                if (!lastTimestamp) return true;
                return new Date(point.x) > new Date(lastTimestamp);
              });

              // Append new data points
              if (newDataPoints.length > 0) {
                existingSensor.chart_data.push(...newDataPoints);
                // Update latest value and timestamp
                existingSensor.latest_value = newSensor.latest_value;
                existingSensor.latest_timestamp = newSensor.latest_timestamp;
              }
            } else {
              // New sensor, add it completely
              this.sensorsData.push(newSensor);
            }
          });
        }
      },
      error: (error) => {
        console.error('Error appending temperature sensor data:', error);
      }
    });
  }

  // Append new soil moisture sensor data
  appendNewSoilMoistureData(): void {
    this.soilMoistureSensorService.getSoilMoistureSensors().subscribe({
      next: (response) => {
        if (response.success && response.data) {
          response.data.forEach((newSensor) => {
            const existingSensor = this.soilMoistureSensorsData.find(s => s.name === newSensor.name);
            
            if (existingSensor) {
              const lastTimestamp = this.getLastTimestamp(existingSensor.chart_data);
              
              const newDataPoints = newSensor.chart_data.filter(point => {
                if (!lastTimestamp) return true;
                return new Date(point.x) > new Date(lastTimestamp);
              });

              if (newDataPoints.length > 0) {
                existingSensor.chart_data.push(...newDataPoints);
                existingSensor.latest_value = newSensor.latest_value;
                existingSensor.latest_timestamp = newSensor.latest_timestamp;
              }
            } else {
              this.soilMoistureSensorsData.push(newSensor);
            }
          });
        }
      },
      error: (error) => {
        console.error('Error appending soil moisture sensor data:', error);
      }
    });
  }

  // Append new light sensor data
  appendNewLightData(): void {
    this.lightSensorService.getLightSensors().subscribe({
      next: (response) => {
        if (response.success && response.data) {
          response.data.forEach((newSensor) => {
            const existingSensor = this.lightSensorsData.find(s => s.name === newSensor.name);
            
            if (existingSensor) {
              const lastTimestamp = this.getLastTimestamp(existingSensor.chart_data);
              
              const newDataPoints = newSensor.chart_data.filter(point => {
                if (!lastTimestamp) return true;
                return new Date(point.x) > new Date(lastTimestamp);
              });

              if (newDataPoints.length > 0) {
                existingSensor.chart_data.push(...newDataPoints);
                existingSensor.latest_value = newSensor.latest_value;
                existingSensor.latest_timestamp = newSensor.latest_timestamp;
              }
            } else {
              this.lightSensorsData.push(newSensor);
            }
          });
        }
      },
      error: (error) => {
        console.error('Error appending light sensor data:', error);
      }
    });
  }

  // Append new turbidity sensor data
  appendNewTurbidityData(): void {
    this.turbiditySensorService.getTurbiditySensors().subscribe({
      next: (response) => {
        if (response.success && response.data) {
          response.data.forEach((newSensor) => {
            const existingSensor = this.turbiditySensorsData.find(s => s.name === newSensor.name);
            
            if (existingSensor) {
              const lastTimestamp = this.getLastTimestamp(existingSensor.chart_data);
              
              const newDataPoints = newSensor.chart_data.filter(point => {
                if (!lastTimestamp) return true;
                return new Date(point.x) > new Date(lastTimestamp);
              });

              if (newDataPoints.length > 0) {
                existingSensor.chart_data.push(...newDataPoints);
                existingSensor.latest_value = newSensor.latest_value;
                existingSensor.latest_timestamp = newSensor.latest_timestamp;
              }
            } else {
              this.turbiditySensorsData.push(newSensor);
            }
          });
        }
      },
      error: (error) => {
        console.error('Error appending turbidity sensor data:', error);
      }
    });
  }

  loginWithGoogle(): void {
    this.authService.loginWithGoogle();
  }

  logout(): void {
    this.authService.logout().subscribe();
  }

  goToDashboard(): void {
    this.router.navigate(['/dashboard']);
  }

  goToManageAccount(): void {
    this.router.navigate(['/manage-account']);
  }

  scrollToFeatures(): void {
    const element = document.getElementById('features');
    element?.scrollIntoView({ behavior: 'smooth' });
  }

  scrollToAbout(): void {
    document.getElementById('about')?.scrollIntoView({ behavior: 'smooth' });
  }

  toggleMobileMenu(): void {
    this.mobileMenuOpen = !this.mobileMenuOpen;
  }

  goToContact(): void {
    this.router.navigate(['/contact']);
  }

  // Carousel navigation methods
  nextPlantType(): void {
    if (this.plantTypes.length > 0) {
      this.currentPlantTypeIndex = (this.currentPlantTypeIndex + 1) % this.plantTypes.length;
    }
  }

  previousPlantType(): void {
    if (this.plantTypes.length > 0) {
      this.currentPlantTypeIndex = this.currentPlantTypeIndex === 0 
        ? this.plantTypes.length - 1 
        : this.currentPlantTypeIndex - 1;
    }
  }

  goToPlantType(index: number): void {
    if (index >= 0 && index < this.plantTypes.length) {
      this.currentPlantTypeIndex = index;
    }
  }

  // Modal methods
  openImageModal(plantTypeIndex: number, imageIndex: number = 0): void {
    if (this.plantTypes[plantTypeIndex] && this.plantTypes[plantTypeIndex].images) {
      this.currentPlantTypeImages = this.plantTypes[plantTypeIndex].images;
      this.currentImageIndex = imageIndex;
      this.isImageModalOpen = true;
      // Prevent body scroll when modal is open
      document.body.style.overflow = 'hidden';
    }
  }

  closeImageModal(): void {
    this.isImageModalOpen = false;
    this.currentImageIndex = 0;
    this.currentPlantTypeImages = [];
    // Restore body scroll
    document.body.style.overflow = 'auto';
  }

  nextImage(): void {
    if (this.currentPlantTypeImages.length > 0) {
      this.currentImageIndex = (this.currentImageIndex + 1) % this.currentPlantTypeImages.length;
    }
  }

  previousImage(): void {
    if (this.currentPlantTypeImages.length > 0) {
      this.currentImageIndex = this.currentImageIndex === 0 
        ? this.currentPlantTypeImages.length - 1 
        : this.currentImageIndex - 1;
    }
  }

  goToImage(index: number): void {
    if (index >= 0 && index < this.currentPlantTypeImages.length) {
      this.currentImageIndex = index;
    }
  }



  loadTemperatureSensors(): void {
    this.temperatureSensorService.getTemperatureSensors().subscribe({
      next: (response) => {
        if (response.success) {
          this.sensorsData = response.data;

          const data = this.sensorsData[0].chart_data;
          // console.log last 10 data and 10 first data
          console.log("first 10 data", data.slice(0, 10));
          console.log("last 10 data", data.slice(-10));
        } else {
          this.error = 'Failed to load temperature sensor data';
        }
      },
      error: (error) => {
        console.error('Error loading temperature sensors:', error);
        this.error = 'Failed to load temperature sensor data';
      }
    });
  }

  loadSoilMoistureSensors(): void {
    this.soilMoistureSensorService.getSoilMoistureSensors().subscribe({
      next: (response) => {
        if (response.success) {
          this.soilMoistureSensorsData = response.data;
        } else {
          this.error = 'Failed to load soil moisture sensor data';
        }
      },
      error: (error) => {
        console.error('Error loading soil moisture sensors:', error);
        this.error = 'Failed to load soil moisture sensor data';
      }
    });
  }

  loadLightSensors(): void {
    this.lightSensorService.getLightSensors().subscribe({
      next: (response) => {
        if (response.success) {
          this.lightSensorsData = response.data;
        } else {
          this.error = 'Failed to load light sensor data';
        }
      },
      error: (error) => {
        console.error('Error loading light sensors:', error);
        this.error = 'Failed to load light sensor data';
      }
    });
  }

  loadTurbiditySensors(): void {
    this.turbiditySensorService.getTurbiditySensors().subscribe({
      next: (response) => {
        if (response.success) {
          this.turbiditySensorsData = response.data;
        } else {
          this.error = 'Failed to load turbidity sensor data';
        }
      },
      error: (error) => {
        console.error('Error loading turbidity sensors:', error);
        this.error = 'Failed to load turbidity sensor data';
      }
    });
  }

  // Chart configuration methods for individual sensors
  getChartSeries(sensor: SensorData): any[] {
    return [{
      name: sensor.name,
      data: sensor.chart_data.map(point => ({
        x: point.x, // keep as string
        y: point.y
      }))
    }];
  }

  getChartOptions(sensor: SensorData): any {
    return {
      type: 'line',
      height: 300,
      toolbar: {
        show: true,
        tools: {
          download: true,
          selection: true,
          zoom: true,
          zoomin: true,
          zoomout: true,
          pan: true,
          reset: true
        }
      },
      animations: {
        enabled: true,
        easing: 'easeinout',
        speed: 800
      }
    };
  }

  getChartColors(sensorIndex: number): string[] {
    const colorIndex = sensorIndex % this.chartColors.length;
    return this.chartColors[colorIndex];
  }

  getChartStroke(): any {
    return {
      curve: 'smooth',
      width: 3
    };
  }

  getChartFill(): any {
    return {
      type: 'gradient',
      gradient: {
        shadeIntensity: 1,
        stops: [0, 90, 100]
      }
    };
  }

  getChartMarkers(sensorIndex: number): any {
    const colorIndex = sensorIndex % this.chartColors.length;
    return {
      size: 4,
      colors: [this.chartColors[colorIndex][0]],
      strokeColors: '#fff',
      strokeWidth: 2,
      hover: {
        size: 6
      }
    };
  }

  getChartXAxis(): any {
    return {
      type: 'datetime',
      labels: {
        formatter: function (value: string) {
          // value is a timestamp in ms
          return dayjs.tz(Number(value), 'Asia/Jakarta').format('HH.mm');
        },
        style: {
          colors: '#666',
          fontSize: '12px'
        }
      },
      title: {
        text: 'Time',
        style: {
          color: '#333',
          fontSize: '14px',
          fontWeight: 600
        }
      }
    };
  }

  getChartYAxis(): any {
    return {
      title: {
        text: 'Temperature (°C)',
        style: {
          color: '#333',
          fontSize: '14px',
          fontWeight: 600
        }
      },
      labels: {
        style: {
          colors: '#666',
          fontSize: '12px'
        }
      }
    };
  }

  getChartLegend(): any {
    return {
      show: false // Hide legend since each chart is for one sensor
    };
  }

  getChartTooltip(): any {
    return {
      x: {
        format: 'dd MMM yyyy HH:mm'
      },
      y: {
        formatter: function (value: number) {
          return value.toFixed(2) + ' °C';
        }
      }
    };
  }

  getChartGrid(): any {
    return {
      borderColor: '#e0e0e0',
      strokeDashArray: 5
    };
  }

  getChartTheme(): any {
    return {
      mode: 'light'
    };
  }

  // Chart helpers for each sensor type
  getSoilMoistureChartSeries(sensor: SoilMoistureSensorData): any[] {
    return [{
      name: sensor.name,
      data: sensor.chart_data.map(point => ({
        x: dayjs.tz(point.x, 'Asia/Jakarta').valueOf(),
        y: point.y
      }))
    }];
  }
  getLightChartSeries(sensor: LightSensorData): any[] {
    return [{
      name: sensor.name,
      data: sensor.chart_data.map(point => ({
        x: dayjs.tz(point.x, 'Asia/Jakarta').valueOf(),
        y: point.y
      }))
    }];
  }
  getTurbidityChartSeries(sensor: TurbiditySensorData): any[] {
    return [{
      name: sensor.name,
      data: sensor.chart_data.map(point => ({
        x: dayjs.tz(point.x, 'Asia/Jakarta').valueOf(),
        y: point.y
      }))
    }];
  }

  // Chart options helpers for each sensor type (with correct y-axis label/unit)
  getSoilMoistureChartYAxis(): any {
    return {
      title: {
        text: 'Soil Moisture (%)',
        style: {
          color: '#333',
          fontSize: '14px',
          fontWeight: 600
        }
      },
      labels: {
        style: {
          colors: '#666',
          fontSize: '12px'
        }
      }
    };
  }
  getLightChartYAxis(): any {
    return {
      title: {
        text: 'Light Intensity (lux)',
        style: {
          color: '#333',
          fontSize: '14px',
          fontWeight: 600
        }
      },
      labels: {
        style: {
          colors: '#666',
          fontSize: '12px'
        }
      }
    };
  }
  getTurbidityChartYAxis(): any {
    return {
      title: {
        text: 'Turbidity (NTU)',
        style: {
          color: '#333',
          fontSize: '14px',
          fontWeight: 600
        }
      },
      labels: {
        style: {
          colors: '#666',
          fontSize: '12px'
        }
      }
    };
  }

  // Chart tooltip helpers for each sensor type
  getSoilMoistureChartTooltip(): any {
    return {
      x: { format: 'dd MMM yyyy HH:mm' },
      y: { formatter: (value: number) => value.toFixed(2) + ' %' }
    };
  }
  getLightChartTooltip(): any {
    return {
      x: { format: 'dd MMM yyyy HH:mm' },
      y: { formatter: (value: number) => value.toFixed(2) + ' lux' }
    };
  }
  getTurbidityChartTooltip(): any {
    return {
      x: { format: 'dd MMM yyyy HH:mm' },
      y: { formatter: (value: number) => value.toFixed(2) + ' NTU' }
    };
  }

  // Table data loading methods
  loadTemperatureTableData(page: number = 1): void {
    this.temperatureTableLoading = true;
    const params = new URLSearchParams();
    params.append('per_page', '5');
    if (page > 1) params.append('page', page.toString());

    this.authService.getWithAuth(`/api/export/temperature-sensors-data?${params.toString()}`).subscribe({
      next: (response: any) => {
        if (response.success) {
          this.temperatureTableData = response.data;
        } else {
          console.error('Failed to load temperature table data');
        }
        this.temperatureTableLoading = false;
      },
      error: (error: any) => {
        console.error('Error loading temperature table data:', error);
        this.temperatureTableLoading = false;
      }
    });
  }

  loadSoilMoistureTableData(page: number = 1): void {
    this.soilMoistureTableLoading = true;
    const params = new URLSearchParams();
    params.append('per_page', '5');
    if (page > 1) params.append('page', page.toString());

    this.authService.getWithAuth(`/api/export/soil-moisture-sensors-data?${params.toString()}`).subscribe({
      next: (response: any) => {
        if (response.success) {
          this.soilMoistureTableData = response.data;
        } else {
          console.error('Failed to load soil moisture table data');
        }
        this.soilMoistureTableLoading = false;
      },
      error: (error: any) => {
        console.error('Error loading soil moisture table data:', error);
        this.soilMoistureTableLoading = false;
      }
    });
  }

  loadLightTableData(page: number = 1): void {
    this.lightTableLoading = true;
    const params = new URLSearchParams();
    params.append('per_page', '5');
    if (page > 1) params.append('page', page.toString());

    this.authService.getWithAuth(`/api/export/light-sensors-data?${params.toString()}`).subscribe({
      next: (response: any) => {
        if (response.success) {
          this.lightTableData = response.data;
        } else {
          console.error('Failed to load light table data');
        }
        this.lightTableLoading = false;
      },
      error: (error: any) => {
        console.error('Error loading light table data:', error);
        this.lightTableLoading = false;
      }
    });
  }

  loadTurbidityTableData(page: number = 1): void {
    this.turbidityTableLoading = true;
    const params = new URLSearchParams();
    params.append('per_page', '5');
    if (page > 1) params.append('page', page.toString());

    this.authService.getWithAuth(`/api/export/turbidity-sensors-data?${params.toString()}`).subscribe({
      next: (response: any) => {
        if (response.success) {
          this.turbidityTableData = response.data;
        } else {
          console.error('Failed to load turbidity table data');
        }
        this.turbidityTableLoading = false;
      },
      error: (error: any) => {
        console.error('Error loading turbidity table data:', error);
        this.turbidityTableLoading = false;
      }
    });
  }

  // Pagination methods
  onTemperatureTablePageChange(page: number): void {
    this.loadTemperatureTableData(page);
  }

  onSoilMoistureTablePageChange(page: number): void {
    this.loadSoilMoistureTableData(page);
  }

  onLightTablePageChange(page: number): void {
    this.loadLightTableData(page);
  }

  onTurbidityTablePageChange(page: number): void {
    this.loadTurbidityTableData(page);
  }

  // Helper method for date formatting
  formatTableDate(dateString: string): string {
    const date = new Date(dateString);
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');
    const seconds = date.getSeconds().toString().padStart(2, '0');

    return `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
  }
}
