import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../environments/environment';

export interface TemperatureReading {
  x: string; // timestamp
  y: number; // temperature value
}

export interface SensorData {
  name: string;
  latest_value: number | null;
  latest_timestamp: string | null;
  chart_data: TemperatureReading[];
}

export interface TemperatureSensorResponse {
  success: boolean;
  data: SensorData[];
  message?: string;
}

@Injectable({
  providedIn: 'root'
})
export class TemperatureSensorService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  private getHttpOptions() {
    const token = localStorage.getItem('auth_token');
    const headers = new HttpHeaders({
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    });

    if (token) {
      headers.set('Authorization', `Bearer ${token}`);
    }

    return {
      headers: headers,
      withCredentials: false // Changed to false for token-based auth
    };
  }

  /**
   * Get all temperature sensors data
   */
  getTemperatureSensors(): Observable<TemperatureSensorResponse> {
    return this.http.get<TemperatureSensorResponse>(
      `${this.apiUrl}/api/temperature-sensors`,
      this.getHttpOptions()
    );
  }

  /**
   * Get data for a specific sensor
   */
  getSensorData(sensorName: string): Observable<TemperatureSensorResponse> {
    return this.http.get<TemperatureSensorResponse>(
      `${this.apiUrl}/api/temperature-sensors/${encodeURIComponent(sensorName)}`,
      this.getHttpOptions()
    );
  }

  /**
   * Get all sensor names
   */
  getSensorNames(): Observable<{ success: boolean; data: string[] }> {
    return this.http.get<{ success: boolean; data: string[] }>(
      `${this.apiUrl}/api/temperature-sensors-names`,
      this.getHttpOptions()
    );
  }

  /**
   * Add a new temperature reading
   */
  addTemperatureReading(sensorName: string, value: number): Observable<any> {
    return this.http.post(
      `${this.apiUrl}/api/temperature-sensors`,
      {
        name: sensorName,
        value: value
      },
      this.getHttpOptions()
    );
  }
} 