import { Component, Input, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { AuthService, User } from '../../auth.service';

@Component({
  selector: 'app-layout',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './layout.html',
  styleUrls: ['./layout.css']
})
export class LayoutComponent implements OnInit {
  @Input() user: User | null = null;
  @Input() loading = false;
  @Input() error = '';

  showUserDropdown = false;
  mobileMenuOpen = false;

  // System status
  alert: any;
  alertLoading = false;

  // Modal state and mock fuzzy logic result
  showAlertModal = false;
  fuzzyResult = {
    suhu: 'Dingin',
    kelembaban: 'Kering',
    cahaya: 'Sedang',
    pompa: 'Pompa akan mati...'
  };

  constructor(
    private authService: AuthService,
    private router: Router
  ) { }

  toggleUserMenu(): void {
    this.showUserDropdown = !this.showUserDropdown;
  }

  closeUserMenu(): void {
    this.showUserDropdown = false;
  }

  toggleMobileMenu(): void {
    this.mobileMenuOpen = !this.mobileMenuOpen;
  }

  navigateToManageAccount(): void {
    this.router.navigate(['/manage-account']);
    this.closeUserMenu();
  }

  getAlert(): void {
    this.alertLoading = true;
    this.authService.getWithAuth('/api/alert').subscribe({
      next: (response: any) => {
        if (response.success) {
          this.alert = response.data ? response.data : [];
          if(this.alert) {
            this.alert.type_lower = this.alert.type.toLowerCase()
          }
          console.log(this.alert)
        } else {
          console.error('Failed to load alert status');
        }
        this.alertLoading = false;
      },
      error: (error: any) => {
        console.error('Error loading alert status:', error);
        this.alertLoading = false;
      }
    });
  }

  logout(): void {
    this.authService.logout().subscribe({
      next: () => {
        this.closeUserMenu();
      },
      error: () => {
        this.closeUserMenu();
      }
    });
  }

  loginWithGoogle(): void {
    this.authService.loginWithGoogle();
  }

  showAlerts(): void {
    // TODO: Replace with real data fetching if available
    this.showAlertModal = true;
  }

  closeAlertModal(): void {
    this.showAlertModal = false;
  }

  exportToExcel(): void {
    this.router.navigate(['/export']);
  }

  navigateToDashboard(): void {
    this.router.navigate(['/dashboard']);
  }

  navigateToLanding(): void {
    this.router.navigate(['/']);
  }

  navigateToContact(): void {
    this.router.navigate(['/contact']);
    this.closeUserMenu();
  }

  goToContact(): void {
    this.router.navigate(['/contact']);
  }

  loadUserDetail(): void {
    console.log("This user:", this.user)
  }

  ngOnInit(): void {
    // setInterval(() => {
    //   this.loadUserDetail();
    // }, 1000);
    this.getAlert();
  }
} 