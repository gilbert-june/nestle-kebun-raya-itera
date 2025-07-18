import { Injectable } from '@angular/core';
import { CanActivate, Router } from '@angular/router';
import { AuthService } from './auth.service';
import { map, take } from 'rxjs/operators';

@Injectable({
  providedIn: 'root'
})
export class AuthGuard implements CanActivate {
  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  canActivate() {
    return this.authService.checkAuth().pipe(
      take(1),
      map(isAuthenticated => {
        if (isAuthenticated) {
          return true; // User is authenticated, allow access
        } else {
          this.router.navigate(['/']); // Redirect to landing page instead of login
          return false; // User is not authenticated, redirect to landing page
        }
      })
    );
  }
} 