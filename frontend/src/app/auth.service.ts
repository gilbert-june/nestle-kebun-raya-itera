import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, Observable } from 'rxjs';
import { map, catchError } from 'rxjs/operators';
import { Router } from '@angular/router';
import { environment } from '../environments/environment';

export interface User {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  google_id?: string;
  role?: string;
}

export interface AuthResponse {
  success: boolean;
  user?: User;
  message?: string;
  token?: string;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = environment.apiUrl;
  private currentUserSubject = new BehaviorSubject<User | null>(null);
  public currentUser$ = this.currentUserSubject.asObservable();
  private authChecked = false;

  constructor(
    private http: HttpClient,
    private router: Router
  ) {
    // Check auth status immediately on service construction
    this.checkAuthStatus().subscribe();
  }

  public get currentUserValue(): User | null {
    return this.currentUserSubject.value;
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
      withCredentials: false // Changed to false for token-based auth
    };
  }

  private checkAuthStatus(): Observable<boolean> {
    const token = localStorage.getItem('auth_token');
    
    console.log('🔍 Checking auth status, token exists:', !!token);
    
    // If no token exists, user is not authenticated
    if (!token) {
      console.log('❌ No token found, user is not authenticated');
      this.currentUserSubject.next(null);
      this.authChecked = true;
      return new Observable<boolean>(observer => {
        observer.next(false);
        observer.complete();
      });
    }

    const httpOptions = this.getHttpOptions();
    console.log('🔍 Making auth request with token');

    return this.http.get<AuthResponse>(`${this.apiUrl}/api/user`, httpOptions).pipe(
      map(response => {
        console.log('✅ Auth response:', response);
        if (response.success && response.user) {
          console.log('✅ User authenticated:', response.user.name);
          this.currentUserSubject.next(response.user);
          this.authChecked = true;
          return true;
        } else {
          console.log('❌ Auth failed, clearing token');
          this.currentUserSubject.next(null);
          this.authChecked = true;
          localStorage.removeItem('auth_token'); // Clear invalid token
          return false;
        }
      }),
      catchError(error => {
        console.error('❌ Auth error:', error);
        this.currentUserSubject.next(null);
        this.authChecked = true;
        localStorage.removeItem('auth_token'); // Clear invalid token
        return new Observable<boolean>(observer => {
          observer.next(false);
          observer.complete();
        });
      })
    );
  }

  loginWithGoogle(): void {
    window.location.href = `${this.apiUrl}/auth/google`;
  }

  logout(): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.apiUrl}/api/logout`, {}, this.getHttpOptions()).pipe(
      map(response => {
        if (response.success) {
          this.currentUserSubject.next(null);
          this.authChecked = false;
          localStorage.removeItem('auth_token');
          this.router.navigate(['/']); // Redirect to landing page instead of login
        }
        return response;
      }),
      catchError(error => {
        // Even if the request fails, clear the local state and redirect
        this.currentUserSubject.next(null);
        this.authChecked = false;
        localStorage.removeItem('auth_token');
        this.router.navigate(['/']); // Redirect to landing page instead of login
        return [{
          success: true,
          message: 'Logged out locally'
        }];
      })
    );
  }

  isAuthenticated(): boolean {
    return this.currentUserValue !== null;
  }

  // Method to check auth status when needed (called by components)
  checkAuth(): Observable<boolean> {
    if (!this.authChecked) {
      return this.checkAuthStatus();
    }
    return this.currentUser$.pipe(
      map(user => !!user)
    );
  }

  // Method to handle token from URL after OAuth redirect
  handleAuthToken(token: string): Observable<boolean> {
    localStorage.setItem('auth_token', token);
    this.authChecked = false;
    return this.checkAuthStatus();
  }

  // Method to refresh auth status (useful after redirect)
  refreshAuthStatus(): void {
    this.authChecked = false;
    this.checkAuthStatus().subscribe();
  }

  // Method to make authenticated HTTP requests
  getWithAuth(url: string, options: any = {}): Observable<any> {
    const httpOptions = {
      ...this.getHttpOptions(),
      ...options
    };
    return this.http.get(`${this.apiUrl}${url}`, httpOptions);
  }

  postWithAuth(url: string, data: any, options: any = {}): Observable<any> {
    const httpOptions = {
      ...this.getHttpOptions(),
      ...options
    };
    return this.http.post(`${this.apiUrl}${url}`, data, httpOptions);
  }

  putWithAuth(url: string, data: any, options: any = {}): Observable<any> {
    const httpOptions = {
      ...this.getHttpOptions(),
      ...options
    };
    return this.http.put(`${this.apiUrl}${url}`, data, httpOptions);
  }

  deleteWithAuth(url: string, options: any = {}): Observable<any> {
    const httpOptions = {
      ...this.getHttpOptions(),
      ...options
    };
    return this.http.delete(`${this.apiUrl}${url}`, httpOptions);
  }
} 