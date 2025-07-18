import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService, User } from '../auth.service';
import { LayoutComponent } from '../shared/layout/layout';

@Component({
  selector: 'app-contact',
  standalone: true,
  imports: [CommonModule, FormsModule, LayoutComponent],
  templateUrl: './contact.html',
  styleUrl: './contact.css'
})
export class ContactComponent implements OnInit {
  user: User | null = null;
  loading = true;
  error = '';

  // Contact form data
  contactForm = {
    name: '',
    email: '',
    subject: '',
    message: ''
  };

  // Form submission state
  isSubmitting = false;
  submitSuccess = false;
  submitError = '';

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    // Check authentication status
    this.authService.checkAuth().subscribe(isAuthenticated => {
      // Get user data from the observable
      this.authService.currentUser$.subscribe(user => {
        this.user = user;
        this.loading = false;
        
        // Pre-fill form with user data if available
        if (user) {
          this.contactForm.name = user.name;
          this.contactForm.email = user.email;
        }
      });
    });
  }

  onSubmit(): void {
    if (this.isSubmitting) return;

    // Basic validation
    if (!this.contactForm.name || !this.contactForm.email || !this.contactForm.subject || !this.contactForm.message) {
      this.submitError = 'Mohon lengkapi semua field yang diperlukan';
      return;
    }

    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(this.contactForm.email)) {
      this.submitError = 'Format email tidak valid';
      return;
    }

    this.isSubmitting = true;
    this.submitError = '';

    // Simulate form submission (replace with actual API call)
    setTimeout(() => {
      this.isSubmitting = false;
      this.submitSuccess = true;
      
      // Reset form after successful submission
      setTimeout(() => {
        this.resetForm();
      }, 3000);
    }, 2000);
  }

  resetForm(): void {
    this.contactForm = {
      name: this.user?.name || '',
      email: this.user?.email || '',
      subject: '',
      message: ''
    };
    this.submitSuccess = false;
    this.submitError = '';
  }

  goToDashboard(): void {
    this.router.navigate(['/dashboard']);
  }

  loginWithGoogle(): void {
    this.authService.loginWithGoogle();
  }
} 