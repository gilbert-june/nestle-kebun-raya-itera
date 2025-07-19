import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClientModule } from '@angular/common/http';
import { Router } from '@angular/router';
import { AuthService, User } from '../auth.service';
import { AboutService, About } from '../about.service';
import { Observable, catchError, of, map } from 'rxjs';
import { LayoutComponent } from '../shared/layout/layout';
import { environment } from '../../environments/environment';

@Component({
  selector: 'app-settings',
  standalone: true,
  imports: [CommonModule, FormsModule, HttpClientModule, LayoutComponent],
  templateUrl: './settings.html',
  styleUrl: './settings.css'
})
export class SettingsComponent implements OnInit {
  environment = environment;
  user: User | null = null;
  loading = true;
  error = '';
  
  aboutData: About = {
    id: 0,
    identifier: 'DEFAULT',
    title: '',
    description: '',
    image: '',
    created_at: '',
    updated_at: ''
  };
  selectedImageFile: File | null = null;
  imagePreview: string | null = null;
  isSubmitting = false;
  submitSuccess = false;
  submitError = '';

  constructor(
    private authService: AuthService,
    private aboutService: AboutService,
    private router: Router
  ) {}

  ngOnInit(): void {
    // Check authentication status
    this.authService.checkAuth().subscribe(isAuthenticated => {
      this.authService.currentUser$.subscribe(user => {
        this.user = user;
        this.loading = false;
        
        // Check if user has permission to access settings
        if (user && (user.role === 'ADMIN' || user.role === 'PENGELOLA')) {
          this.loadAboutData();
        } else {
          this.error = 'Anda tidak memiliki akses ke halaman ini';
        }
      });
    });
  }

  loadAboutData(): void {
    this.aboutService.getAbout().pipe(
      map(response => response.data),
      catchError(error => {
        console.error('Error fetching about data:', error);
        this.error = 'Gagal memuat data';
        return of(null);
      })
    ).subscribe(data => {
      console.log('About data loaded:', data);
      if (data) {
        this.aboutData = { ...data };
        // Set image preview if image exists
        if (data.image) {
          this.imagePreview = `${environment.apiUrl}/storage/${data.image}`;
          console.log('Image preview set to:', this.imagePreview);
          console.log('Full image URL:', this.imagePreview);
        } else {
          console.log('No image in data');
        }
      }
      // If data is null, keep the default values
    });
  }

  onImageSelected(event: any): void {
    const file = event.target.files[0];
    console.log('File selected:', file);
    if (file) {
      // Validate file type
      if (!file.type.match(/image\/(jpeg|png|jpg|gif)/)) {
        this.submitError = 'File harus berupa gambar (JPEG, PNG, JPG, GIF)';
        return;
      }

      // Validate file size (2MB max)
      if (file.size > 2 * 1024 * 1024) {
        this.submitError = 'Ukuran file maksimal 2MB';
        return;
      }

      this.selectedImageFile = file;
      this.submitError = '';

      // Create preview
      const reader = new FileReader();
      reader.onload = (e: any) => {
        this.imagePreview = e.target.result;
        console.log('Image preview created:', this.imagePreview);
      };
      reader.readAsDataURL(file);
    }
  }

  removeImage(): void {
    this.selectedImageFile = null;
    this.imagePreview = null;
  }



  onSubmit(): void {
    if (this.isSubmitting || !this.aboutData) return;

    // Basic validation
    if (!this.aboutData.title || !this.aboutData.description) {
      this.submitError = 'Mohon lengkapi judul dan deskripsi';
      return;
    }

    this.isSubmitting = true;
    this.submitError = '';

    const updateData = {
      title: this.aboutData.title,
      description: this.aboutData.description,
      image: this.aboutData.image || ''
    };

    this.aboutService.updateAbout(updateData, this.selectedImageFile || undefined).pipe(
      catchError(error => {
        console.error('Error updating about data:', error);
        this.submitError = 'Gagal menyimpan perubahan';
        this.isSubmitting = false;
        return of(null);
      })
    ).subscribe(response => {
      if (response) {
        this.isSubmitting = false;
        this.submitSuccess = true;
        
        // Reset form after successful submission
        this.selectedImageFile = null;
        
        // Reset success message after 3 seconds
        setTimeout(() => {
          this.submitSuccess = false;
        }, 3000);
      }
    });
  }

  goToDashboard(): void {
    this.router.navigate(['/dashboard']);
  }

  loginWithGoogle(): void {
    this.authService.loginWithGoogle();
  }

  onImageError(event: Event): void {
    const target = event.target as HTMLImageElement;
    if (target) {
      target.style.display = 'none';
    }
    console.error('Image failed to load:', event);
  }

  onImageLoad(event: Event): void {
    console.log('Image loaded successfully:', event);
  }
} 