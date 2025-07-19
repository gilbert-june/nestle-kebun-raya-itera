import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../environments/environment';

export interface About {
  id: number;
  identifier: string;
  title: string;
  description: string;
  image: string;
  created_at: string;
  updated_at: string;
}

export interface AboutResponse {
  success: boolean;
  data: About;
}

@Injectable({
  providedIn: 'root'
})
export class AboutService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) { }

  getAbout(): Observable<AboutResponse> {
    return this.http.get<AboutResponse>(`${this.apiUrl}/api/about`);
  }

  updateAbout(aboutData: Partial<About>, imageFile?: File): Observable<AboutResponse> {
    const formData = new FormData();
    
    formData.append('title', aboutData.title || '');
    formData.append('description', aboutData.description || '');
    
    if (imageFile) {
      formData.append('image', imageFile);
    }

    return this.http.post<AboutResponse>(`${this.apiUrl}/api/about?_method=PUT`, formData);
  }
} 