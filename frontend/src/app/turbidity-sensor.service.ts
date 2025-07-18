import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../environments/environment';

export interface TurbidityReading {
  x: string; // timestamp
  y: number; // turbidity value
}

export interface SensorData {
  name: string;
  latest_value: number | null;
  latest_timestamp: string | null;
  chart_data: TurbidityReading[];
}

export interface TurbiditySensorResponse {
  success: boolean;
  data: SensorData[];
  message?: string;
}

@Injectable({
  providedIn: 'root'
})
export class TurbiditySensorService {
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
   * Get all turbidity sensors data
   */
  getTurbiditySensors(): Observable<TurbiditySensorResponse> {
    return this.http.get<TurbiditySensorResponse>(
      `${this.apiUrl}/api/turbidity-sensors`,
      this.getHttpOptions()
    );
  }

  /**
   * Get data for a specific sensor
   */
  getSensorData(sensorName: string): Observable<TurbiditySensorResponse> {
    return this.http.get<TurbiditySensorResponse>(
      `${this.apiUrl}/api/turbidity-sensors/${encodeURIComponent(sensorName)}`,
      this.getHttpOptions()
    );
  }

  /**
   * Get all sensor names
   */
  getSensorNames(): Observable<{ success: boolean; data: string[] }> {
    return this.http.get<{ success: boolean; data: string[] }>(
      `${this.apiUrl}/api/turbidity-sensors-names`,
      this.getHttpOptions()
    );
  }

  /**
   * Add a new turbidity reading
   */
  addTurbidityReading(sensorName: string, value: number): Observable<any> {
    return this.http.post(
      `${this.apiUrl}/api/turbidity-sensors`,
      {
        name: sensorName,
        value: value
      },
      this.getHttpOptions()
    );
  }
} 