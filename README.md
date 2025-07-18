# Nestle Kebun Raya - Google OAuth Login

A simple authentication system using Laravel 12 (Backend) and Angular 20 (Frontend) with Google OAuth integration.

## Features

- 🔐 Google OAuth authentication (no email/password required)
- 🎨 Beautiful, modern UI design
- 🔄 Automatic user profile sync from Google
- 🚀 Laravel 12 + Angular 20 stack
- 📱 Responsive design

## Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- Angular CLI
- Google OAuth credentials

## Setup Instructions

### 1. Backend Setup (Laravel)

```bash
cd backend

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Start the development server
php artisan serve
```

### 2. Frontend Setup (Angular)

```bash
cd frontend

# Install dependencies
npm install

# Start the development server
ng serve
```

### 3. Google OAuth Configuration

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the Google+ API
4. Go to "Credentials" and create an OAuth 2.0 Client ID
5. Add authorized redirect URIs:
   - `http://localhost:8000/auth/google/callback` (for development)
   - `http://your-domain.com/auth/google/callback` (for production)

### 4. Environment Configuration

Update your `.env` file in the backend:

```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

## Usage

1. Start both servers:
   - Backend: `php artisan serve` (runs on http://localhost:8000)
   - Frontend: `ng serve` (runs on http://localhost:4200)

2. Open http://localhost:4200 in your browser

3. Click "Continue with Google" to authenticate

4. After successful authentication, you'll be redirected to the dashboard

## API Endpoints

- `GET /auth/google` - Redirect to Google OAuth
- `GET /auth/google/callback` - Handle Google OAuth callback
- `GET /user` - Get current authenticated user
- `POST /logout` - Logout user

## Project Structure

```
nestle-kebun-raya/
├── backend/                 # Laravel 12 API
│   ├── app/
│   │   ├── Http/Controllers/
│   │   │   └── AuthController.php
│   │   ├── Models/
│   │   │   └── User.php
│   │   └── Http/Middleware/
│   │       └── Cors.php
│   ├── routes/
│   │   └── web.php
│   └── config/
│       └── services.php
└── frontend/               # Angular 20 App
    ├── src/app/
    │   ├── auth.service.ts
    │   ├── login/
    │   └── dashboard/
    └── src/environments/
```

## Technologies Used

### Backend
- Laravel 12
- Laravel Socialite
- Spatie Laravel Permission
- MySQL/SQLite

### Frontend
- Angular 20
- TypeScript
- CSS3 with modern design
- RxJS for state management

## Security Features

- CORS middleware for secure cross-origin requests
- Session-based authentication
- CSRF protection
- Secure OAuth flow

## Development

### Backend Development
```bash
cd backend
php artisan serve
```

### Frontend Development
```bash
cd frontend
ng serve
```

### Database
```bash
cd backend
php artisan migrate
php artisan migrate:refresh --seed
```

## Production Deployment

1. Update environment variables for production
2. Set up proper SSL certificates
3. Configure production database
4. Build Angular app: `ng build --configuration production`
5. Deploy Laravel backend following Laravel deployment best practices

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT). 