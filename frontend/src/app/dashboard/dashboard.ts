import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { AuthService, User } from '../auth.service';
import { TemperatureSensorService, SensorData } from '../temperature-sensor.service';
import { SoilMoistureSensorService, SensorData as SoilMoistureSensorData } from '../soil-moisture-sensor.service';
import { LightSensorService, SensorData as LightSensorData } from '../light-sensor.service';
import { TurbiditySensorService, SensorData as TurbiditySensorData } from '../turbidity-sensor.service';
import { NgApexchartsModule } from 'ng-apexcharts';
import { LayoutComponent } from '../shared/layout/layout';
import { PaginationComponent } from '../shared/pagination/pagination';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import advancedFormat from 'dayjs/plugin/advancedFormat';

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
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, NgApexchartsModule, LayoutComponent, PaginationComponent],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css'
})
export class DashboardComponent implements OnInit {
  user: User | null = null;
  loading = true;
  error = '';

  // System status
  system: { id: number, is_active: boolean } | null = null;
  systemLoading = false;

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
    private authService: AuthService,
    private router: Router,
    private temperatureSensorService: TemperatureSensorService,
    private soilMoistureSensorService: SoilMoistureSensorService,
    private lightSensorService: LightSensorService,
    private turbiditySensorService: TurbiditySensorService
  ) { }

  ngOnInit(): void {
    // Check authentication status
    this.authService.checkAuth().subscribe(isAuthenticated => {
      if (!isAuthenticated) {
        this.router.navigate(['/']); // Redirect to landing page instead of login
        return;
      }

      // Get user data from the observable
      this.authService.currentUser$.subscribe(user => {
        this.user = user;
        console.log("Rhis User", this.user);
        this.loading = false;

        // Load system status
        this.getSystem();

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
      });
    });
  }

  getSystem(): void {
    this.systemLoading = true;
    this.authService.getWithAuth('/api/system').subscribe({
      next: (response: any) => {
        if (response.success) {
          this.system = response.data;
        } else {
          console.error('Failed to load system status');
        }
        this.systemLoading = false;
      },
      error: (error: any) => {
        console.error('Error loading system status:', error);
        this.systemLoading = false;
      }
    });
  }

  toggleSystemActivation(): void {
    if (!this.system) return;

    this.systemLoading = true;
    this.authService.putWithAuth(`/api/system/${this.system.id}/toggle-access`, {}).subscribe({
      next: (response: any) => {
        if (response.success) {
          this.system = response.data;
        } else {
          console.error('Failed to toggle system activation');
        }
        this.systemLoading = false;
      },
      error: (error: any) => {
        console.error('Error toggling system activation:', error);
        this.systemLoading = false;
      }
    });
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

  logout(): void {
    this.loading = true;
    this.authService.logout().subscribe({
      next: (response) => {
        console.log('Logged out successfully');
        this.loading = false;
      },
      error: (error) => {
        console.error('Logout error:', error);
        this.error = 'Logout failed. Please try again.';
        this.loading = false;
      }
    });
  }
}
