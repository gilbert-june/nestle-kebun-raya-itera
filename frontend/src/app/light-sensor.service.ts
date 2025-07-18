import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../environments/environment';

export interface LightReading {
  x: string; // timestamp
  y: number; // light intensity value
}

export interface SensorData {
  name: string;
  latest_value: number | null;
  latest_timestamp: string | null;
  chart_data: LightReading[];
}

export interface LightSensorResponse {
  success: boolean;
  data: SensorData[];
  message?: string;
}

@Injectable({
  providedIn: 'root'
})
export class LightSensorService {
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
   * Get all light sensors data
   */
  getLightSensors(): Observable<LightSensorResponse> {
    return this.http.get<LightSensorResponse>(
      `${this.apiUrl}/api/light-sensors`,
      this.getHttpOptions()
    );
  }

  /**
   * Get data for a specific sensor
   */
  getSensorData(sensorName: string): Observable<LightSensorResponse> {
    return this.http.get<LightSensorResponse>(
      `${this.apiUrl}/api/light-sensors/${encodeURIComponent(sensorName)}`,
      this.getHttpOptions()
    );
  }

  /**
   * Get all sensor names
   */
  getSensorNames(): Observable<{ success: boolean; data: string[] }> {
    return this.http.get<{ success: boolean; data: string[] }>(
      `${this.apiUrl}/api/light-sensors-names`,
      this.getHttpOptions()
    );
  }

  /**
   * Add a new light reading
   */
  addLightReading(sensorName: string, value: number): Observable<any> {
    return this.http.post(
      `${this.apiUrl}/api/light-sensors`,
      {
        name: sensorName,
        value: value
      },
      this.getHttpOptions()
    );
  }
} 