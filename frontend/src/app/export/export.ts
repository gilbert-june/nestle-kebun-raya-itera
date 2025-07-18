import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { AuthService, User } from '../auth.service';
import { LayoutComponent } from '../shared/layout/layout';
import { PaginationComponent } from '../shared/pagination/pagination';
import { FormsModule } from '@angular/forms';

interface SensorStats {
  count: number;
  latest_date: string | null;
  sensor_names: string[];
}

interface ExportStats {
  temperature_sensors: SensorStats;
  soil_moisture_sensors: SensorStats;
  light_sensors: SensorStats;
  turbidity_sensors: SensorStats;
}

interface SensorData {
  id: number;
  name: string;
  value: number;
  created_at: string;
  updated_at: string;
}

interface PaginatedData {
  current_page: number;
  data: SensorData[];
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

interface FilterOptions {
  start_date: string;
  end_date: string;
}

@Component({
  selector: 'app-export',
  standalone: true,
  imports: [CommonModule, LayoutComponent, PaginationComponent, FormsModule],
  templateUrl: './export.html',
  styleUrls: ['./export.css']
})
export class ExportComponent implements OnInit {
  user: User | null = null;
  loading = false;
  error = '';
  exportStats: ExportStats | null = null;
  statsLoading = false;

  // Table data
  temperatureData: PaginatedData | null = null;
  soilMoistureData: PaginatedData | null = null;
  lightData: PaginatedData | null = null;
  turbidityData: PaginatedData | null = null;

  // Loading states for tables
  temperatureLoading = false;
  soilMoistureLoading = false;
  lightLoading = false;
  turbidityLoading = false;

  // Filter options
  temperatureFilters: FilterOptions = {
    start_date: '',
    end_date: ''
  };

  soilMoistureFilters: FilterOptions = {
    start_date: '',
    end_date: ''
  };

  lightFilters: FilterOptions = {
    start_date: '',
    end_date: ''
  };

  turbidityFilters: FilterOptions = {
    start_date: '',
    end_date: ''
  };

  allDataFilters: FilterOptions = {
    start_date: '',
    end_date: ''
  };

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadUser();
    this.loadExportStats();
    this.loadAllTables();
  }

  loadUser(): void {
    this.loading = true;
    this.authService.currentUser$.subscribe({
      next: (user) => {
        this.user = user;
        this.loading = false;
      },
      error: (error: any) => {
        this.error = 'Failed to load user data';
        this.loading = false;
        console.error('Error loading user:', error);
      }
    });
  }

  loadExportStats(): void {
    this.statsLoading = true;
    this.authService.getWithAuth('/api/export/stats').subscribe({
      next: (response: any) => {
        if (response.success) {
          this.exportStats = response.data;
        } else {
          this.error = response.message || 'Failed to load export statistics';
        }
        this.statsLoading = false;
      },
      error: (error: any) => {
        this.error = 'Failed to load export statistics';
        this.statsLoading = false;
        console.error('Error loading export stats:', error);
      }
    });
  }

  exportTemperatureSensors(): void {
    const params = this.buildExportQueryParams(this.temperatureFilters);
    this.downloadFile(`/api/export/temperature-sensors?${params}`, 'temperature_sensors.xlsx');
  }

  exportSoilMoistureSensors(): void {
    const params = this.buildExportQueryParams(this.soilMoistureFilters);
    this.downloadFile(`/api/export/soil-moisture-sensors?${params}`, 'soil_moisture_sensors.xlsx');
  }

  exportLightSensors(): void {
    const params = this.buildExportQueryParams(this.lightFilters);
    this.downloadFile(`/api/export/light-sensors?${params}`, 'light_sensors.xlsx');
  }

  exportTurbiditySensors(): void {
    const params = this.buildExportQueryParams(this.turbidityFilters);
    this.downloadFile(`/api/export/turbidity-sensors?${params}`, 'turbidity_sensors.xlsx');
  }

  exportAllSensors(): void {
    const params = this.buildExportQueryParams(this.allDataFilters);
    this.downloadFile(`/api/export/all-sensors?${params}`, 'all_sensors_data.xlsx');
  }

  private downloadFile(url: string, filename: string): void {
    this.authService.getWithAuth(url, { responseType: 'blob' }).subscribe({
      next: (blob: Blob) => {
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
      },
      error: (error: any) => {
        console.error('Error downloading file:', error);
        this.error = 'Failed to download file';
      }
    });
  }

  navigateToDashboard(): void {
    this.router.navigate(['/dashboard']);
  }

  formatDate(dateString: string | null): string {
    if (!dateString) return 'No data';
    return new Date(dateString).toLocaleString();
  }

  loadAllTables(): void {
    this.loadTemperatureData();
    this.loadSoilMoistureData();
    this.loadLightData();
    this.loadTurbidityData();
  }

  // Load data methods
  loadTemperatureData(page: number = 1): void {
    this.temperatureLoading = true;
    const params = this.buildQueryParams(this.temperatureFilters, page);
    
    this.authService.getWithAuth(`/api/export/temperature-sensors-data?${params}`).subscribe({
      next: (response: any) => {
        if (response.success) {
          this.temperatureData = response.data;
        } else {
          this.error = response.message || 'Failed to load temperature data';
        }
        this.temperatureLoading = false;
      },
      error: (error: any) => {
        this.error = 'Failed to load temperature data';
        this.temperatureLoading = false;
        console.error('Error loading temperature data:', error);
      }
    });
  }

  loadSoilMoistureData(page: number = 1): void {
    this.soilMoistureLoading = true;
    const params = this.buildQueryParams(this.soilMoistureFilters, page);
    
    this.authService.getWithAuth(`/api/export/soil-moisture-sensors-data?${params}`).subscribe({
      next: (response: any) => {
        if (response.success) {
          this.soilMoistureData = response.data;
        } else {
          this.error = response.message || 'Failed to load soil moisture data';
        }
        this.soilMoistureLoading = false;
      },
      error: (error: any) => {
        this.error = 'Failed to load soil moisture data';
        this.soilMoistureLoading = false;
        console.error('Error loading soil moisture data:', error);
      }
    });
  }

  loadLightData(page: number = 1): void {
    this.lightLoading = true;
    const params = this.buildQueryParams(this.lightFilters, page);
    
    this.authService.getWithAuth(`/api/export/light-sensors-data?${params}`).subscribe({
      next: (response: any) => {
        if (response.success) {
          this.lightData = response.data;
        } else {
          this.error = response.message || 'Failed to load light data';
        }
        this.lightLoading = false;
      },
      error: (error: any) => {
        this.error = 'Failed to load light data';
        this.lightLoading = false;
        console.error('Error loading light data:', error);
      }
    });
  }

  loadTurbidityData(page: number = 1): void {
    this.turbidityLoading = true;
    const params = this.buildQueryParams(this.turbidityFilters, page);
    
    this.authService.getWithAuth(`/api/export/turbidity-sensors-data?${params}`).subscribe({
      next: (response: any) => {
        if (response.success) {
          this.turbidityData = response.data;
        } else {
          this.error = response.message || 'Failed to load turbidity data';
        }
        this.turbidityLoading = false;
      },
      error: (error: any) => {
        this.error = 'Failed to load turbidity data';
        this.turbidityLoading = false;
        console.error('Error loading turbidity data:', error);
      }
    });
  }

  // Filter methods
  applyTemperatureFilters(): void {
    this.loadTemperatureData(1);
  }

  applySoilMoistureFilters(): void {
    this.loadSoilMoistureData(1);
  }

  applyLightFilters(): void {
    this.loadLightData(1);
  }

  applyTurbidityFilters(): void {
    this.loadTurbidityData(1);
  }

  clearTemperatureFilters(): void {
    this.temperatureFilters = {
      start_date: '',
      end_date: ''
    };
    this.loadTemperatureData(1);
  }

  clearSoilMoistureFilters(): void {
    this.soilMoistureFilters = {
      start_date: '',
      end_date: ''
    };
    this.loadSoilMoistureData(1);
  }

  clearLightFilters(): void {
    this.lightFilters = {
      start_date: '',
      end_date: ''
    };
    this.loadLightData(1);
  }

  clearTurbidityFilters(): void {
    this.turbidityFilters = {
      start_date: '',
      end_date: ''
    };
    this.loadTurbidityData(1);
  }

  // Pagination methods
  onTemperaturePageChange(page: number): void {
    this.loadTemperatureData(page);
  }

  onSoilMoisturePageChange(page: number): void {
    this.loadSoilMoistureData(page);
  }

  onLightPageChange(page: number): void {
    this.loadLightData(page);
  }

  onTurbidityPageChange(page: number): void {
    this.loadTurbidityData(page);
  }

  // Helper methods
  private buildQueryParams(filters: FilterOptions, page: number): string {
    const params = new URLSearchParams();
    
    if (filters.start_date) params.append('start_date', filters.start_date);
    if (filters.end_date) params.append('end_date', filters.end_date);
    params.append('per_page', '5'); // Fixed limit of 10 items per page
    if (page > 1) params.append('page', page.toString());
    
    return params.toString();
  }

  private buildExportQueryParams(filters: FilterOptions): string {
    const params = new URLSearchParams();
    if (filters.start_date) params.append('start_date', filters.start_date);
    if (filters.end_date) params.append('end_date', filters.end_date);
    return params.toString();
  }

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