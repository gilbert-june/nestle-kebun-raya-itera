# Deployment Guide - Angular Frontend

## Masalah yang Ditemukan
Error yang Anda alami disebabkan oleh:
1. **MIME Type Error**: Server mengembalikan `text/html` untuk file JavaScript alih-alih `application/javascript`
2. **SPA Routing**: Server tidak mengarahkan route Angular ke `index.html`

## Solusi

### 1. Upload File .htaccess
Pastikan file `.htaccess` yang sudah dibuat di folder `frontend/` diupload ke root domain Anda (`nestlekebunrayaitera.com`).

### 2. Struktur Folder yang Benar
Pastikan struktur folder di server seperti ini:
```
public_html/
├── .htaccess (file yang sudah dibuat)
└── nestle-kebun-raya-itera/
    └── frontend/
        └── dist/
            └── frontend/
                └── browser/
                    ├── index.html
                    ├── main-*.js
                    ├── polyfills-*.js
                    ├── styles-*.css
                    └── chunk-*.js
```

### 3. Build Angular untuk Production
```bash
cd frontend
ng build --configuration production
```

### 4. Upload File Build
Upload semua file dari folder `frontend/dist/frontend/browser/` ke folder `nestle-kebun-raya-itera/frontend/dist/frontend/browser/` di server.

### 5. Test OAuth Flow
1. Buka `https://nestlekebunrayaitera.com`
2. Klik login dengan Google
3. Pilih akun Google
4. Seharusnya redirect ke dashboard tanpa error

## Troubleshooting

### Jika masih error MIME type:
1. Pastikan modul `mod_headers` aktif di Apache
2. Coba tambahkan ini di `.htaccess`:
```apache
<IfModule mod_mime.c>
    AddType application/javascript .js
    AddType text/css .css
</IfModule>
```

### Jika route tidak berfungsi:
1. Pastikan modul `mod_rewrite` aktif
2. Cek error log Apache
3. Pastikan path di `.htaccess` sesuai dengan struktur folder

### Jika CORS error:
1. Pastikan backend API (`api.nestlekebunrayaitera.com`) sudah dikonfigurasi dengan benar
2. Cek apakah domain API sudah sesuai di `environment.prod.ts`

## File yang Penting
- `.htaccess` - Konfigurasi Apache untuk SPA routing dan MIME types
- `environment.prod.ts` - URL API untuk production
- `index.html` - Entry point Angular app
- File JavaScript dan CSS hasil build

## Backup Plan
Jika `.htaccess` tidak berfungsi, coba gunakan konfigurasi ini:
```apache
RewriteEngine On
RewriteBase /

# Force JavaScript MIME type
<FilesMatch "\.js$">
    ForceType application/javascript
</FilesMatch>

# SPA routing
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ nestle-kebun-raya-itera/frontend/dist/frontend/browser/index.html [L]
``` 