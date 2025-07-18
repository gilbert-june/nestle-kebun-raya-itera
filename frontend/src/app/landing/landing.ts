import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { AuthService, User } from '../auth.service';
import { Observable } from 'rxjs';
import { LayoutComponent } from '../shared/layout/layout';

@Component({
  selector: 'app-landing',
  standalone: true,
  imports: [CommonModule, LayoutComponent],
  templateUrl: './landing.html',
  styleUrl: './landing.css'
})
export class LandingComponent implements OnInit {
  mobileMenuOpen = false;
  user$: Observable<User | null>;

  constructor(
    private router: Router,
    private authService: AuthService
  ) {
    this.user$ = this.authService.currentUser$;
  }

  ngOnInit(): void {
    // Check authentication status when component initializes
    this.authService.checkAuth().subscribe();
  }

  loginWithGoogle(): void {
    this.authService.loginWithGoogle();
  }

  logout(): void {
    this.authService.logout().subscribe();
  }

  goToDashboard(): void {
    this.router.navigate(['/dashboard']);
  }

  goToManageAccount(): void {
    this.router.navigate(['/manage-account']);
  }

  scrollToFeatures(): void {
    const element = document.getElementById('features');
    element?.scrollIntoView({ behavior: 'smooth' });
  }

  scrollToAbout(): void {
    document.getElementById('about')?.scrollIntoView({ behavior: 'smooth' });
  }

  toggleMobileMenu(): void {
    this.mobileMenuOpen = !this.mobileMenuOpen;
  }

  goToContact(): void {
    this.router.navigate(['/contact']);
  }
}
