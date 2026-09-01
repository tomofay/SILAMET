# SILAMET — Documentation

**Sistem Informasi Manajemen Inventarisasi Laboratorium Terintegrasi Gate Security UHF RFID & Modul Pemeliharaan**

> Lab SIJA — SMK Negeri 1 Cibinong

---

## 📋 Contents

- [Overview](#overview)
- [Tech Stack](#tech-stack)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Folder Structure](#folder-structure)
- [Running the Application](#running-the-application)
- [Available Commands](#available-commands)
- [API Endpoints](#api-endpoints)
- [Roles & Permissions](#roles--permissions)
- [Database Schema](#database-schema)
- [Hardware Integration](#hardware-integration)
- [Development Notes](#development-notes)

---

## Overview

SILAMET adalah sistem manajemen inventaris laboratorium yang terintegrasi dengan **Gate Security UHF RFID** dan modul **Pemeliharaan & Perbaikan (M&R)**. Sistem ini mencakup:

- **Kiosk Layanan Mandiri** — Siswa meminjam/mengembalikan alat secara self-service via barcode scanner + webcam.
- **Gate Security** — Antena UHF RFID di pintu mendeteksi barang yang keluar-masuk lab. Barang `dipinjam` = akses diizinkan; barang `tersedia`/`under_maintenance` = alarm sirine + foto pelanggar.
- **Dashboard Admin** — Monitoring statistik, log gate, manajemen aset, tiket M&R, dan laporan (PDF/Excel).

### User Journey

```
[Siswa]                    [Admin/Teknisi/Kabeng/Kaprog]
     │                              │
Scan NIS Barcode            Login NIP + Password
     │                              │
Scan QR Alat              → Dashboard (Statistik)
     │                        ├─ Master Aset
Confirm Wajah              ├─ Peminjaman
     │                        ├─ Gate Logs
Barang 'dipinjam'          ├─ Maintenance & Repair
     │                        └─ Laporan (PDF/Excel)
                             (Monitoring/Gate/M&R)
```

---

## Tech Stack

| Layer | Technology | Version | Purpose |
|-------|-----------|---------|---------|
| Backend Framework | Laravel | 11.x | MVC, Eloquent ORM, Migration, Scheduling |
| Database | MySQL / SQLite | 8+ / Latest | Relational data storage |
| Authentication | Breeze Blade | 2.x | Web auth (login/register/profile) |
| API Auth | Sanctum | 3.x | Token-based API auth for Kiosk & Devices |
| Role Management | Spatie Laravel Permission | 6.x | RBAC granular (roles + permissions) |
| Frontend Styling | Tailwind CSS | 3.x | Utility-first CSS (custom SILAMET theme) |
| JS Framework | Alpine.js | 3.x | Lightweight reactivity (Blade directives) |
| PDF Export | barryvdh/laravel-dompdf | 3.x | Generate laporan PDF |
| Excel Export | maatwebsite/excel | 3.x | Rekap data ke Excel |
| Build Tool | Vite | 5.x | Asset compilation (CSS + JS) |

---

## System Requirements

- **PHP** ≥ 8.2
- **Composer** ≥ 2.0
- **Node.js** ≥ 18
- **MySQL** ≥ 8.0 _(atau SQLite untuk development)_
- **Extension PHP:** `php_openssl`, `php pdo`, `php mbstring`, `php tokenizers`, `php xml`, `php ctype`, `php json`, `php fileinfo`

---

## Installation

### 1. Clone or Create Project

```bash
composer create-project laravel/laravel silamet "11.*"
cd silamet
```

> *(Jika sudah ada dari scaffolding awal, skip step ini)*

### 2. Install Dependencies

```bash
# PHP packages
composer install

# Node packages
npm install && npm run build
```

### 3. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` sesuai konfigurasi database Anda:

**Untuk MySQL (XAMPP / Laragon):**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=silamet
DB_USERNAME=root
DB_PASSWORD=
```

**Untuk SQLite (Development cepat):**
```env
DB_CONNECTION=sqlite
# DB_HOST, DB_PORT, DB_DATABASE, dsb. dibiarkan komentar
```

> Buat database `silamet` terlebih dahulu jika menggunakan MySQL:
> ```sql
> CREATE DATABASE silamet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
> ```

### 4. Run Migrations & Seeders

```bash
php artisan migrate:fresh --seed
```

Ini akan:
- Membuat semua tabel database (7 tabel utama SILAMET + 2 tabel permission Spatie)
- Menyimpan 4 default roles (`siswa`, `teknisi`, `kabeng`, `kaprog`)
- Menyimpan akun superadmin pertama (email: `admin@silamet.id`, password: `password`)

### 5. Setup Storage Link

```bash
php artisan storage:link
```

Membuat symlink `public/storage` → `storage/app/public` untuk upload foto & barcode.

### 6. Verify Installation

Akses halaman login di browser:
```
http://localhost:8000/login
```

Login dengan kredensial seeder:
- **Email:** `admin@silamet.id`
- **Password:** `password`

---

## Configuration

### Environment Variables (`.env`)

```env
# Aplikasi
APP_NAME="SILAMET"
APP_ENV=local
APP_KEY=base64:...           # Generate otomatis oleh artisan key:generate
APP_DEBUG=true               # Set false untuk production
APP_TIMEZONE=Asia/Jakarta    # Timezone Indonesia
APP_LOCALE=id                # Bahasa Indonesia
APP_URL=http://localhost

# Database (sesuaikan dengan pilihan Anda)
DB_CONNECTION=sqlite         # mysql atau sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=silamet
# DB_USERNAME=root
# DB_PASSWORD=

# Session & Auth
SESSION_DRIVER=database      # Atau 'file' untuk dev cepat
SESSION_LIFETIME=120

# Upload & Filesystem
FILESYSTEM_DISK=local        # Untuk produksi: 's3' atau 'public'

# Queue (opsional, untuk background job)
QUEUE_CONNECTION=database

# Email (opsional, untuk notifikasi)
MAIL_MAILER=log              # Untuk prod: 'smtp'
```

### Tailwind CSS Custom Colors

Warna SILAMET sudah dikonfigurasi di `tailwind.config.js`:

```js
// Primary — Royal Blue (Navigasi & Utama)
bg-primary / text-primary

// Success — Green (Status Aman, Izin Lewat)
bg-success / text-success

// Danger — Red/Orange (Alarm Gate, Kerusakan)
bg-danger / text-danger

// Info — Cyan
bg-info

// Warning — Yellow
bg-warning

// Neutral Background
bg-neutral-50 (#F7FAFC)
```

### CORS Configuration

File `config/cors.php` sudah dikonfigurasi untuk mendukung:
- Origin localhost (development)
- LAN IPs (kiosk & device communication)
- `supports_credentials: true` untuk Sanctum API auth

---

## Folder Structure

```
silamet/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                      # Login, logout (dari Breeze)
│   │   │   ├── DashboardController.php    # Statistik utama dashboard
│   │   │   ├── MasterController.php       # CRUD items/aset/bahan
│   │   │   ├── LoanController.php         # Peminjaman & pengembalian
│   │   │   ├── GateLogController.php      # Monitoring log pintu RFID
│   │   │   ├── MaintenanceController.php  # Tiket M&R + log perbaikan
│   │   │   └── ReportController.php       # Ekspor PDF/Excel
│   │   ├── Middleware/
│   │   │   ├── EnsureUserHasRole.php      # Gate akses berbasis role (Spatie)
│   │   │   ├── HandleJsonApiException.php # Error response konsisten JSON
│   │   │   └── ApiAuthenticate.php        # Sanctum token auth untuk kiosk/device
│   │   └── Requests/                      # Form validation (akan dibuat per fitur)
│   │
│   ├── Models/
│   │   ├── User.php                       # HasRoles trait, $casts UserRole enum
│   │   ├── Item.php                       # Aset/bahan, status tersedia/dipinjam/under_maintenance
│   │   ├── Loan.php                       # Transaksi peminjaman
│   │   ├── GateLog.php                    # Log pembacaan RFID pintu
│   │   ├── MaintenanceTicket.php          # Laporan kerusakan
│   │   └── MaintenanceLog.php             # Riwayat eksekusi perbaikan
│   │
│   ├── Enums/
│   │   ├── UserRole.php                   # SISWA, TEKNISI, KABENG, KAPROG
│   │   ├── ItemType.php                   # ASET, BAHAN_HABIS_PAKAI
│   │   ├── ItemStatus.php                 # TERSEDIA, DIPINJAM, UNDER_MAINTENANCE
│   │   ├── LoanStatus.php                 # AKTIF, MENUNGGU_APPROVAL, SELESAI
│   │   ├── TicketStatus.php               # OPEN, IN_PROGRESS, RESOLVED
│   │   └── ServiceType.php                # INTERNAL, VENDOR_LUAR
│   │
│   └── Services/
│       ├── BarcodeService.php             # Generate QR/barcode PNG
│       ├── GateValidationService.php      # Validasi EPC tag real-time
│       ├── CameraService.php              # Snapshot webcam (siswa/gate)
│       └── ReportGenerationService.php    # Aggregation data PDF/Excel
│
├── config/
│   ├── permission.php                     # Spatie RBAC config
│   ├── dompdf.php                         # PDF export config
│   ├── excel.php                          # Excel export config
│   ├── sanctum.php                        # API token expiration
│   └── cors.php                           # Allowed origins (localhost + LAN)
│
├── database/
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2024_01_01_000003_create_personal_access_tokens_table.php
│   │   ├── 2024_01_01_000004_create_permission_tables.php     # Spatie
│   │   ├── 2024_01_01_000005_create_items_table.php
│   │   ├── 2024_01_01_000006_create_loans_table.php
│   │   ├── 2024_01_01_000007_create_gate_logs_table.php
│   │   ├── 2024_01_01_000008_create_maintenance_tickets_table.php
│   │   └── 2024_01_01_000009_create_maintenance_logs_table.php
│   │
│   └── seeders/
│       ├── DatabaseSeeder.php             # Orchestrator (panggil RoleSeeder + UserSeeder)
│       ├── RoleSeeder.php                 # Insert 4 roles default
│       └── UserSeeder.php                 # Insert superadmin account
│
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── app.blade.php              # Master layout (sidebar Argon-style)
│   │   │   └── guest.blade.php            # Layout login/register
│   │   ├── dashboard.blade.php            # Dasbor statistik utama
│   │   ├── items/                         # CRUD blades: index, create, edit, show
│   │   ├── loans/                         # Blades peminjaman & pengembalian
│   │   ├── gate-logs/                     # Monitoring log pintu
│   │   ├── maintenance/                   # Blades M&R (tickets + logs)
│   │   └── profiles/                      # Edit profil user (dari Breeze)
│   │
│   ├── css/
│   │   └── app.css                        # Tailwind directives + custom colors
│   └── js/                                # Alpine.js helpers
│
├── routes/
│   ├── web.php                            # Authenticated web routes (Blade views)
│   ├── api.php                            # /api/v1/* (Sanctum protected API)
│   └── auth.php                           # Auto-generated by Breeze
│
├── public/
│   └── uploads/                           # Foto snapshot, barcode PNG, exports
│
├── tests/
│   ├── Unit/
│   └── Feature/
│
├── .env.example                           # Template environment variables
├── tailwind.config.js                     # SILAMET color scheme
└── vite.config.js                         # Asset compilation
```

---

## Running the Application

### Development Server

```bash
# Jalankan backend server (port 8000)
php artisan serve

# Jalankan asset watcher (Vite HMR) — buka terminal terpisah
npm run dev
```

Kemudian akses: `http://localhost:8000`

### Production Build

```bash
npm run build
php artisan optimize
```

---

## Available Commands

| Command | Description |
|---------|-------------|
| `php artisan serve` | Start development server on `localhost:8000` |
| `php artisan migrate:fresh --seed` | Drop all tables, recreate, and seed default data |
| `php artisan migrate` | Run pending migrations only |
| `php artisan db:show` | Show all tables and their column info |
| `php artisan route:list` | List all registered routes (web + API) |
| `php artisan cache:clear` | Clear application cache |
| `php artisan view:clear` | Clear compiled Blade templates |
| `php artisan config:clear` | Clear configuration cache |
| `php artisan storage:link` | Create symlink for public file uploads |
| `php artisan tinker` | Interactive PHP console (REPL) |
| `php artisan key:generate` | Generate new APP_KEY |
| `php artisan db:wipe` | Drop all tables from the database |
| `php artisan queue:work` | Start queue worker (for background jobs) |
| `php artisan queue:listen` | Listen for queued jobs |
| `npm run dev` | Start Vite dev server with Hot Module Replacement |
| `npm run build` | Compile assets for production |
| `composer dump-autoload` | Regenerate autoload files after adding classes |

---

## API Endpoints

All API routes prefixed with `/api/v1/` and require Sanctum authentication unless noted otherwise.

### Authentication

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/api/v1/auth/login` | ❌ | Login & get Sanctum cookie |
| `POST` | `/api/v1/auth/logout` | ✅ | Invalidate session |

### Items (Katalog)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/v1/items` | ❌ | Public list items (untuk katalog kiosk) |
| `GET` | `/api/v1/items/{id}` | ❌ | Detail item tertentu |

### Loans (Peminjaman — Kiosk Flow)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/api/v1/loans` | ✅ | Create loan (scan NIS + scan barang + snapshot wajah) |
| `POST` | `/api/v1/loans/{id}/return` | ✅ | Return item (scan barcode barang + pilih kondisi) |

### Gate Security

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/api/v1/gate/check-tag` | ✅ | **HW-VX6330 reader call** — Validasi EPC tag saat melintasi pintu. Respons: `allowed` atau `denied` (+ trigger relay sirene) |

### Notes

- Semua respons API berupa JSON.
- Error response konsisten format:
  ```json
  {
    "success": false,
    "message": "Deskripsi error",
    "errors": {}  // jika validation error
  }
  ```

---

## Roles & Permissions

Sistem menggunakan **Spatie Laravel Permission** untuk Role-Based Access Control (RBAC).

### Default Roles

| Role | Akses |
|------|-------|
| **siswa** | Login via barcode NIS di Kiosk. Kirim laporan kerusakan. Tidak bisa akses web dashboard. |
| **teknisi** | Akses penuh modul Maintenance (tambah tiket, mulai/selesai perbaikan), input biaya servis. |
| **kabeng** (Kepala Bengkel) | Monitoring dashboard, log gate, persetujuan jadwal preventive maintenance. |
| **kaprog** (Kepala Program) | Melihat semua laporan inventaris, keuangan M&R, rekapitulasi aset. |

### Assigning Roles

```php
use App\Models\User;
use Spatie\Permission\Models\Role;

// Get role by name
$role = Role::where('name', 'teknisi')->first();

// Assign role to user
$user->assignRole($role);

// Check if user has role
$user->hasRole('kabeng');

// Remove role
$user->removeRole('kaprog');
```

### Middleware Protection

Gunakan middleware `role:` di routes:

```php
Route::middleware(['auth', 'role:teknisi|kabeng|kaprog'])->group(function () {
    Route::get('/maintenance', [MaintenanceController::class, 'index']);
});
```

---

## Database Schema

### Tabel Utama

#### `users`
Kolom: `id`, `nis_nip`, `name`, `role`, `password`, `created_at`, `updated_at`, `deleted_at`

#### `items`
Kolom: `id`, `item_code`, `epc_tag`, `name`, `type`, `stock`, `status`, `location`, `created_at`, `updated_at`, `deleted_at`

#### `loans`
Kolom: `id`, `user_id`, `item_id`, `status`, `condition_in`, `borrowed_at`, `returned_at`, `created_at`, `updated_at`

#### `gate_logs`
Kolom: `id`, `epc_tag_scanned`, `action_status`, `capture_image_path`, `created_at`

#### `maintenance_tickets`
Kolom: `id`, `item_id`, `reported_by`, `issue_description`, `status`, `created_at`, `updated_at`

#### `maintenance_logs`
Kolom: `id`, `ticket_id`, `handled_by`, `action_taken`, `service_type`, `cost`, `repaired_at`, `created_at`, `updated_at`

### Relasi

```
users 1──N loans        (user → pinjam)
users 1──N tickets      (user → laporkan)
users 1──N logs         (technician → kerjakan)
items 1──N loans        (item → dipinjam)
items 1──N tickets      (item → rusak)
tickets 1──1 logs       (ticket → history perbaikan)
items.epc_tag 1──N logs (RFID tag → deteksi di pintu)
```

> Untuk diagram visual lengkap, lihat file `DATABASE_SCHEMA.md` di root projek.

---

## Hardware Integration

### Perangkat yang Didukung

| Perangkat | Tipe | Interface | Status |
|-----------|------|-----------|--------|
| Barcode Scanner USB | HID Keyboard Mode | USB | ✅ Siap integrasi |
| Webcam USB | JPEG Capture | USB | ✅ Siap integrasi |
| UHF RFID Reader HW-VX6330 | RS232/RS485 | Serial Port | ⏳ Fase berikutnya |
| UHF RFID Tags (EPC Gen2) | ISO18000-6C | Physical Tag | — |
| Sirine 12V + Relay Module | Digital Output | GPIO/Relay | ⏳ Fase berikutnya |
| PC Listener Service | Port Serial | COM Port | ⏳ Fase berikutnya |

### Integrasi HW-VX6330 (Antarmuka Gate)

Reader akan menghubungi endpoint:
```
POST http://localhost/api/v1/gate/check-tag
{
  "epc_tag": "EPC_STRING_DARI_TAG"
}
```

Respons:
```json
// Jika barang dipinjam → Izin lewat
{
  "success": true,
  "action": "allowed",
  "message": "Barang dengan status 'dipinjam' — akses diizinkan."
}

// Jika barang tersedia/under maintenance → Alarm
{
  "success": true,
  "action": "denied",
  "message": "Barang tidak memiliki izin — alarm aktif.",
  "trigger_sirene": true,
  "capture_photo": true
}
```

> **Catatan:** Trigger sirene 12V dan snapshot kamera gate perlu implementasi service layer tambahan di Fase berikutnya. Siapkan variabel `PORT_SERIAL_READER` di `.env` saat ini:
> ```env
> # RFID Gate Hardware
> PORT_SERIAL_READER=COM3    # Sesuaikan port COM reader
> BAUD_RATE=115200           # Default HW-VX6330
> ```

---

## Development Notes

### Code Style Conventions

- Menggunakan **PHP 8.1+** native enums untuk status codes (bukan string hardcoded)
- Controller methods mengikuti RESTful conventions
- Service classes untuk business logic yang kompleks
- Middleware `EnsureUserHasRole` untuk gate akses berbasis role
- Custom exception handler (`HandleJsonApiException`) untuk konsistensi JSON error response

### Adding New Routes

Tambahkan route di `routes/web.php` (Blade) atau `routes/api.php` (JSON):

```php
// Web (Blade view)
Route::middleware(['auth', 'role:kabeng|kaprog'])
    ->prefix('gate-logs')
    ->group(function () {
        Route::get('/', [GateLogController::class, 'index']);
        Route::get('/{id}', [GateLogController::class, 'show']);
    });

// API (JSON response)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('/loans', [LoanController::class, 'store']);
});
```

### Testing

```bash
# Jalankan semua test
php artisan test

# Jalankan test spesifik
php artisan test --filter=LoanTest
```

### Debugging

```bash
# Lihat semua routes aktif
php artisan route:list

# Cek koneksi database
php artisan tinker
>>> DB::table('users')->count();

# Clear semua cache
php artisan optimize:clear
```

---

## License

Proprietary — Hak Cipta © 2024 SMK Negeri 1 Cibinong.

---

## Authors

- **Galih Wisnu Baratha** — Product Requirement & Architecture
- SILAMET Development Team

---

## References

| Dokumen | Lokasi |
|---------|--------|
| Product Requirements Document | [`prd.md`](../prd.md) |
| Database Schema Specification | [`DATABASE_SCHEMA.md`](../DATABASE_SCHEMA.md) |
| UI/UX Design Specification | [`UI_UX_DESIGN.md`](../UI_UX_DESIGN.md) |
