import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Router } from '@angular/router';
import { environment } from '../../environments/environment';
import { LayoutComponent } from '../shared/layout/layout';
import { PaginationComponent } from '../shared/pagination/pagination';
import { AuthService, User } from '../auth.service';

export interface ManageUser {
  id: number;
  name: string;
  email: string;
  can_access: boolean;
  avatar?: string;
  google_id?: string;
}

export interface UsersResponse {
  data: ManageUser[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

@Component({
  selector: 'app-manage-account',
  standalone: true,
  imports: [CommonModule, FormsModule, LayoutComponent, PaginationComponent],
  templateUrl: './manage-account.html',
  styleUrls: ['./manage-account.css']
})
export class ManageAccountComponent implements OnInit {
  users: ManageUser[] = [];
  currentPage = 1;
  totalPages = 1;
  totalUsers = 0;
  perPage = 10;
  loading = false;
  error = '';
  currentUser: User | null = null;

  private apiUrl = environment.apiUrl;

  constructor(
    private http: HttpClient,
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    // Cek status autentikasi terlebih dahulu
    this.authService.checkAuth().subscribe(isAuthenticated => {
      if (!isAuthenticated) {
        console.log('Pengguna belum terautentikasi, mengarahkan ke halaman utama');
        this.router.navigate(['/']); // Arahkan ke halaman utama
        return;
      }
      
      console.log('Pengguna terautentikasi, memuat data pengguna');
      this.authService.currentUser$.subscribe(user => {
        this.currentUser = user;
      });
      this.loadUsers();
    });
  }

  loadUsers(): void {
    this.loading = true;
    this.error = '';

    const token = localStorage.getItem('auth_token');
    console.log('Memuat data pengguna, token ada:', !!token);

    const url = `${this.apiUrl}/api/users?page=${this.currentPage}&per_page=${this.perPage}`;
    const httpOptions = this.getHttpOptions();
    console.log('Opsi HTTP untuk pengguna:', httpOptions);
    
    this.http.get<UsersResponse>(url, httpOptions).subscribe({
      next: (response) => {
        console.log('Respon pengguna:', response);
        this.users = response.data;
        this.currentPage = response.current_page;
        this.totalPages = response.last_page;
        this.totalUsers = response.total;
        this.loading = false;
        
        console.log('Data paginasi:', {
          currentPage: this.currentPage,
          totalPages: this.totalPages,
          totalUsers: this.totalUsers,
          perPage: this.perPage
        });
      },
      error: (error) => {
        console.error('Gagal memuat data pengguna:', error);
        this.error = 'Gagal memuat data pengguna. Silakan coba lagi.';
        this.loading = false;
      }
    });
  }

  toggleAccess(user: User): void {
    const url = `${this.apiUrl}/api/users/${user.id}/toggle-access?_method=PUT`;
    
    this.http.post<{ success: boolean; message: string }>(url, {}, this.getHttpOptions()).subscribe({
      next: (response) => {
        if (response.success) {
          // Refresh halaman saat ini untuk mendapatkan data terbaru
          this.loadUsers();
        } else {
          this.error = response.message || 'Gagal memperbarui akses.';
        }
      },
      error: (error) => {
        this.error = 'Gagal memperbarui akses. Silakan coba lagi.';
        console.error('Gagal memperbarui akses:', error);
      }
    });
  }

  onPageChange(page: number): void {
    this.currentPage = page;
    this.loadUsers();
  }

  private getHttpOptions() {
    const token = localStorage.getItem('auth_token');
    let headers = new HttpHeaders({
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    });

    if (token) {
      headers = headers.set('Authorization', `Bearer ${token}`);
    }

    return {
      headers: headers,
      withCredentials: false // Diubah ke false untuk autentikasi berbasis token
    };
  }
} 