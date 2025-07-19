import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { PlantTypeService, PlantType, PlantTypeImage } from '../plant-type.service';
import { environment } from '../../environments/environment';
import { AuthService } from '../auth.service';
import { LayoutComponent } from '../shared/layout/layout';
import { Observable } from 'rxjs';

@Component({
  selector: 'app-plant-type-management',
  standalone: true,
  imports: [CommonModule, FormsModule, ReactiveFormsModule, LayoutComponent],
  templateUrl: './plant-type-management.html',
  styleUrls: ['./plant-type-management.css']
})
export class PlantTypeManagementComponent implements OnInit {
  plantTypes: PlantType[] = [];
  plantTypeForm: FormGroup;
  selectedFiles: File[] = [];
  thumbnailIndex: number = 0;
  isEditing: boolean = false;
  editingId: number | null = null;
  loading: boolean = false;
  error: string = '';
  success: string = '';
  user$: Observable<any>;
  existingImages: PlantTypeImage[] = [];
  imagesToDelete: number[] = [];
  showForm: boolean = false;

  constructor(
    private plantTypeService: PlantTypeService,
    private fb: FormBuilder,
    private http: HttpClient,
    private authService: AuthService
  ) {
    this.user$ = this.authService.currentUser$;
    this.plantTypeForm = this.fb.group({
      name: ['', [Validators.required, Validators.maxLength(255)]],
      description: ['', [Validators.required]]
    });
  }

  ngOnInit(): void {
    this.loadPlantTypes();
  }

  loadPlantTypes(): void {
    this.loading = true;
    this.plantTypeService.getPlantTypes().subscribe({
      next: (response) => {
        this.plantTypes = response.data;
        this.loading = false;
      },
      error: (error) => {
        this.error = 'Failed to load plant types';
        this.loading = false;
        console.error('Error loading plant types:', error);
      }
    });
  }

  onFileSelected(event: any): void {
    const files = event.target.files;
    if (files) {
      this.selectedFiles = Array.from(files);
      // Reset thumbnail index if it's out of bounds
      if (this.thumbnailIndex >= this.selectedFiles.length) {
        this.thumbnailIndex = 0;
      }
    }
  }

  removeFile(index: number): void {
    this.selectedFiles.splice(index, 1);
    if (this.thumbnailIndex >= this.selectedFiles.length) {
      this.thumbnailIndex = Math.max(0, this.selectedFiles.length - 1);
    }
  }

  setThumbnail(index: number): void {
    this.thumbnailIndex = index;
  }

  onSubmit(): void {
    if (this.plantTypeForm.valid) {
      this.loading = true;
      this.error = '';
      this.success = '';

      if (this.isEditing && this.editingId) {
        // Check if all images will be deleted
        if (this.getTotalImageCount() === 0) {
          this.error = 'At least one image must remain for the plant type';
          this.loading = false;
          return;
        }

        // Update existing plant type
        this.plantTypeService.updatePlantType(this.editingId, {
          name: this.plantTypeForm.get('name')?.value,
          description: this.plantTypeForm.get('description')?.value
        }).subscribe({
          next: (response) => {
            // Check if there are any image changes (new files, deletions, or thumbnail changes)
            const hasImageChanges = this.selectedFiles.length > 0 || 
                                  this.imagesToDelete.length > 0 || 
                                  this.existingImages.some(img => img.is_thumbnail !== 
                                    this.plantTypes.find(pt => pt.id === this.editingId)?.images.find(i => i.id === img.id)?.is_thumbnail);

            if (hasImageChanges) {
              // Use partial update for images
              this.plantTypeService.updatePlantTypeImagesPartial(this.editingId!, {
                images: this.selectedFiles.length > 0 ? this.selectedFiles : undefined,
                thumbnail_index: this.getThumbnailIndexForNewImages(),
                images_to_delete: this.imagesToDelete.length > 0 ? this.imagesToDelete : undefined,
                existing_images: this.existingImages.length > 0 ? this.existingImages : undefined
              }).subscribe({
                next: (imageResponse) => {
                  this.success = 'Plant type and images updated successfully';
                  this.hideForm();
                  this.loadPlantTypes();
                  this.loading = false;
                },
                error: (error) => {
                  this.error = 'Failed to update plant type images';
                  this.loading = false;
                  console.error('Error updating plant type images:', error);
                }
              });
                          } else {
                // No image changes, just update the plant type info
                this.success = 'Plant type updated successfully';
                this.hideForm();
                this.loadPlantTypes();
                this.loading = false;
              }
          },
          error: (error) => {
            this.error = 'Failed to update plant type';
            this.loading = false;
            console.error('Error updating plant type:', error);
          }
        });
      } else {
        // Create new plant type - requires images
        if (this.selectedFiles.length === 0) {
          this.error = 'Please select at least one image for new plant types';
          this.loading = false;
          return;
        }

        const formData = new FormData();
        formData.append('name', this.plantTypeForm.get('name')?.value);
        formData.append('description', this.plantTypeForm.get('description')?.value);
        formData.append('thumbnail_index', this.thumbnailIndex.toString());

        this.selectedFiles.forEach((file, index) => {
          formData.append(`images[${index}]`, file);
        });

        this.plantTypeService.createPlantType(formData).subscribe({
          next: (response) => {
            this.success = 'Plant type created successfully';
            this.hideForm();
            this.loadPlantTypes();
            this.loading = false;
          },
          error: (error) => {
            this.error = 'Failed to create plant type';
            this.loading = false;
            console.error('Error creating plant type:', error);
          }
        });
      }
    } else {
      this.error = 'Please fill all required fields';
    }
  }

  editPlantType(plantType: PlantType): void {
    this.showForm = true;
    this.isEditing = true;
    this.editingId = plantType.id;
    this.plantTypeForm.patchValue({
      name: plantType.name,
      description: plantType.description
    });
    this.selectedFiles = [];
    this.thumbnailIndex = 0;
    this.existingImages = [...plantType.images];
    this.imagesToDelete = [];
  }

  deletePlantType(id: number): void {
    if (confirm('Are you sure you want to delete this plant type?')) {
      this.loading = true;
      this.plantTypeService.deletePlantType(id).subscribe({
        next: (response) => {
          this.success = 'Plant type deleted successfully';
          this.loadPlantTypes();
          this.loading = false;
        },
        error: (error) => {
          this.error = 'Failed to delete plant type';
          this.loading = false;
          console.error('Error deleting plant type:', error);
        }
      });
    }
  }

  resetForm(): void {
    this.plantTypeForm.reset();
    this.selectedFiles = [];
    this.thumbnailIndex = 0;
    this.isEditing = false;
    this.editingId = null;
    this.error = '';
    this.existingImages = [];
    this.imagesToDelete = [];
    this.showForm = false;
  }

  resetFormData(): void {
    this.plantTypeForm.reset();
    this.selectedFiles = [];
    this.thumbnailIndex = 0;
    this.isEditing = false;
    this.editingId = null;
    this.error = '';
    this.existingImages = [];
    this.imagesToDelete = [];
  }

  getImageUrl(imagePath: string): string {
    return this.plantTypeService.getImageUrl(imagePath);
  }

  getThumbnailImage(plantType: PlantType): string {
    const thumbnail = plantType.images.find(img => img.is_thumbnail);
    if (thumbnail) {
      return thumbnail.image_url;
    }
    // Fallback to first image if no thumbnail
    return plantType.images.length > 0 ? plantType.images[0].image_url : '';
  }

  getFilePreview(file: File): string {
    return URL.createObjectURL(file);
  }

  // Handle existing image operations
  deleteExistingImage(imageId: number): void {
    if (confirm('Are you sure you want to delete this image?')) {
      this.imagesToDelete.push(imageId);
      this.existingImages = this.existingImages.filter(img => img.id !== imageId);
    }
  }

  setExistingImageAsThumbnail(imageId: number): void {
    this.existingImages = this.existingImages.map(img => ({
      ...img,
      is_thumbnail: img.id === imageId
    }));
  }

  getTotalImageCount(): number {
    return this.existingImages.length + this.selectedFiles.length;
  }

  getThumbnailIndexForNewImages(): number {
    // If there are existing images and one is thumbnail, don't set new images as thumbnail
    const hasExistingThumbnail = this.existingImages.some(img => img.is_thumbnail);
    return hasExistingThumbnail ? -1 : this.thumbnailIndex;
  }

  showCreateForm(): void {
    this.showForm = true;
    this.isEditing = false;
    this.resetFormData();
  }

  hideForm(): void {
    this.showForm = false;
    this.resetForm();
  }
} 