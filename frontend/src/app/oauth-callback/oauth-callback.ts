import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { AuthService } from '../auth.service';

@Component({
  selector: 'app-oauth-callback',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="flex items-center justify-center min-h-screen bg-gray-50">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
        <p class="text-gray-600">Processing authentication...</p>
      </div>
    </div>
  `,
  styles: []
})
export class OAuthCallbackComponent implements OnInit {
  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    console.log('OAuth callback component initialized');
    
    // Check for token in URL (from Google OAuth redirect)
    const urlParams = new URLSearchParams(window.location.search);
    const encodedToken = urlParams.get('token');
    const token = encodedToken ? decodeURIComponent(encodedToken) : null;
    
    console.log('Token from URL:', token ? 'Found' : 'Not found');
    
    if (token) {
      console.log('Processing token...');
      // Handle the token from OAuth redirect and wait for it to complete
      this.authService.handleAuthToken(token).subscribe({
        next: (isAuthenticated) => {
          console.log('Auth result:', isAuthenticated);
          
          // Clean up the URL
          window.history.replaceState({}, document.title, window.location.pathname);
          
          if (!isAuthenticated) {
            console.log('Auth failed, redirecting to landing');
            this.router.navigate(['/']);
            return;
          }
          
          console.log('Auth successful, redirecting to dashboard');
          this.router.navigate(['/dashboard']);
        },
        error: (error) => {
          console.error('Auth error:', error);
          this.router.navigate(['/']);
        }
      });
    } else {
      console.log('No token found, redirecting to landing');
      this.router.navigate(['/']);
    }
  }
} 