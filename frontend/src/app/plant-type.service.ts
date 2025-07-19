import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../environments/environment';

export interface PlantTypeImage {
  id: number;
  plant_type_id: number;
  image: string;
  image_url: string;
  is_thumbnail: boolean;
  created_at: string;
  updated_at: string;
}

export interface PlantType {
  id: number;
  name: string;
  description: string;
  created_at: string;
  updated_at: string;
  images: PlantTypeImage[];
}

export interface PlantTypeResponse {
  success: boolean;
  data: PlantType[];
  message?: string;
}

export interface SinglePlantTypeResponse {
  success: boolean;
  data: PlantType;
  message?: string;
}

@Injectable({
  providedIn: 'root'
})
export class PlantTypeService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) { }

  // Get all plant types
  getPlantTypes(): Observable<PlantTypeResponse> {
    return this.http.get<PlantTypeResponse>(`${this.apiUrl}/api/plant-types`);
  }

  // Get single plant type
  getPlantType(id: number): Observable<SinglePlantTypeResponse> {
    return this.http.get<SinglePlantTypeResponse>(`${this.apiUrl}/api/plant-types/${id}`);
  }

  // Create new plant type
  createPlantType(formData: FormData): Observable<SinglePlantTypeResponse> {
    return this.http.post<SinglePlantTypeResponse>(`${this.apiUrl}/api/plant-types`, formData);
  }

  // Update plant type
  updatePlantType(id: number, data: { name: string; description: string }): Observable<SinglePlantTypeResponse> {
    return this.http.post<SinglePlantTypeResponse>(`${this.apiUrl}/api/plant-types/${id}?_method=PUT`, data);
  }

  // Update plant type images
  updatePlantTypeImages(id: number, formData: FormData): Observable<SinglePlantTypeResponse> {
    return this.http.post<SinglePlantTypeResponse>(`${this.apiUrl}/api/plant-types/${id}/images?_method=PUT`, formData);
  }

  // Update plant type images with partial updates
  updatePlantTypeImagesPartial(id: number, data: {
    images?: File[];
    thumbnail_index?: number;
    images_to_delete?: number[];
    existing_images?: any[];
  }): Observable<SinglePlantTypeResponse> {
    const formData = new FormData();
    
    if (data.images && data.images.length > 0) {
      data.images.forEach((file, index) => {
        formData.append(`images[${index}]`, file);
      });
      if (data.thumbnail_index !== undefined) {
        formData.append('thumbnail_index', data.thumbnail_index.toString());
      }
    }
    
    if (data.images_to_delete && data.images_to_delete.length > 0) {
      formData.append('images_to_delete', JSON.stringify(data.images_to_delete));
    }
    
    if (data.existing_images && data.existing_images.length > 0) {
      formData.append('existing_images', JSON.stringify(data.existing_images));
    }
    
    return this.http.post<SinglePlantTypeResponse>(`${this.apiUrl}/api/plant-types/${id}/images/partial?_method=PUT`, formData);
  }

  // Delete plant type
  deletePlantType(id: number): Observable<{ success: boolean; message: string }> {
    return this.http.delete<{ success: boolean; message: string }>(`${this.apiUrl}/api/plant-types/${id}`);
  }

  // Get image URL
  getImageUrl(imagePath: string): string {
    return `${this.apiUrl}/api/storage/${imagePath}`;
  }
} 