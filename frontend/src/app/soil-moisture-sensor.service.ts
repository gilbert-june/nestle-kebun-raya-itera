import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../environments/environment';

export interface SoilMoistureReading {
  x: string; // timestamp
  y: number; // soil moisture value
}

export interface SensorData {
  name: string;
  latest_value: number | null;
  latest_timestamp: string | null;
  chart_data: SoilMoistureReading[];
}

export interface SoilMoistureSensorResponse {
  success: boolean;
  data: SensorData[];
  message?: string;
}

@Injectable({
  providedIn: 'root'
})
export class SoilMoistureSensorService {
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
   * Get all soil moisture sensors data
   */
  getSoilMoistureSensors(): Observable<SoilMoistureSensorResponse> {
    return this.http.get<SoilMoistureSensorResponse>(
      `${this.apiUrl}/api/soil-moisture-sensors`,
      this.getHttpOptions()
    );
  }

  /**
   * Get data for a specific sensor
   */
  getSensorData(sensorName: string): Observable<SoilMoistureSensorResponse> {
    return this.http.get<SoilMoistureSensorResponse>(
      `${this.apiUrl}/api/soil-moisture-sensors/${encodeURIComponent(sensorName)}`,
      this.getHttpOptions()
    );
  }

  /**
   * Get all sensor names
   */
  getSensorNames(): Observable<{ success: boolean; data: string[] }> {
    return this.http.get<{ success: boolean; data: string[] }>(
      `${this.apiUrl}/api/soil-moisture-sensors-names`,
      this.getHttpOptions()
    );
  }

  /**
   * Add a new soil moisture reading
   */
  addSoilMoistureReading(sensorName: string, value: number): Observable<any> {
    return this.http.post(
      `${this.apiUrl}/api/soil-moisture-sensors`,
      {
        name: sensorName,
        value: value
      },
      this.getHttpOptions()
    );
  }
} 