# SILAMET — Documentation
**Sistem Informasi Manajemen Inventarisasi Laboratorium Terintegrasi Gate Security UHF RFID & Modul Pemeliharaan**

> Laboratorium Jurusan Sistem Informasi, Jaringan, dan Aplikasi (SIJA) — SMK Negeri 1 Cibinong

---

## 📋 Daftar Isi

- [1. Ringkasan Sistem (*Overview*)](#1-ringkasan-sistem-overview)
- [2. Arsitektur & Tech Stack](#2-arsitektur--tech-stack)
- [3. Kebutuhan Sistem & Perangkat Keras](#3-kebutuhan-sistem--perangkat-keras)
- [4. Struktur Direktori Proyek](#4-struktur-direktori-proyek)
- [5. Instalasi & Setup Cepat](#5-instalasi--setup-cepat)
- [6. Menjalankan Aplikasi](#6-menjalankan-aplikasi)
- [7. Perangkat Keras & Daemon Gate Security](#7-perangkat-keras--daemon-gate-security)
- [8. Perintah Artisan & Pengujian Otomatis](#8-perintah-artisan--pengujian-otomatis)
- [9. REST API Endpoints](#9-rest-api-endpoints)
- [10. Hak Akses Pengguna (*RBAC*)](#10-hak-akses-pengguna-rbac)
- [11. Skema Basis Data & Aturan Jatuh Tempo](#11-skema-basis-data--aturan-jatuh-tempo)
- [12. Dokumen Terkait & Referensi](#12-dokumen-terkait--referensi)

---

## 1. Ringkasan Sistem (*Overview*)

**SILAMET** adalah platform manajemen aset dan inventaris laboratorium komputer & jaringan yang menggabungkan:
1. **Self-Service Kiosk (`/kiosk`):** Meja layanan mandiri siswa berbasis layar sentuh dengan pemindai barcode/QR USB HID, pemilihan durasi pemakaian fleksibel, validasi kontak telepon siswa, dan snapshot webcam instan (< 5 detik per transaksi).
2. **Gate Security UHF RFID (`hardware/`):** Pemindai antena reader HW-VX6330 di kusen pintu lab yang memverifikasi tag EPC Gen2 secara real-time. Barang dengan status sah `dipinjam` diizinkan lewat (*allowed*); sedangkan barang `tersedia` atau `under_maintenance` yang dibawa keluar langsung memicu sirine 12V via Arduino Uno Relay (COM4) dan snapshot foto pelanggar.
3. **Modul Pemeliharaan & Tiket Kerusakan (M&R):** Form pelaporan kerusakan otomatis saat pengembalian barang rusak yang seketika mengisolasi alat dari katalog Kiosk, dilengkapi pencatatan riwayat pengerjaan teknisi dan kalkulasi biaya servis.
4. **Pemantauan Keterlambatan Real-Time:** Deteksi otomatis peminjaman yang melewati batas jatuh tempo (`due_at`) yang langsung terpantau pada dasbor admin lengkap dengan informasi kontak siswa.

### Diagram Alur Utama (User Journey)

```
[Siswa di Meja Kiosk]                       [Staf di Web Dashboard]
        │                                              │
Scan QR Kartu Pelajar (NIS)                     Login NIP + Password
        │                                              │
Pilih Barang & Durasi                           Dashboard Utama (`/dashboard`)
(Pulang Sekolah / 1-7 Hari)                       ├─ Master Data Aset & Bahan
        │                                         ├─ Log Gerbang RFID (`/gate-logs`)
Webcam Snapshot Wajah                             ├─ Tiket Pemeliharaan (`/maintenance`)
        │                                         ├─ Cetak Label QR & Kartu Pelajar
Status Barang = 'dipinjam'                        └─ Ekspor Laporan (PDF / Excel)
        │
[Melintasi Pintu Lab Gate UHF RFID]
        ├─ Status 'dipinjam'           ──► Akses ALLOWED (Sirine Hening)
        └─ Status 'tersedia' / Rusak   ──► Akses DENIED  (Sirine 12V + Snapshot Wajah)
```

---

## 2. Arsitektur & Tech Stack

| Layer / Komponen | Teknologi | Versi | Peran & Fungsi |
| :--- | :--- | :--- | :--- |
| **Backend Framework** | Laravel | 11.x | REST API, Controller, Eloquent ORM, Scheduler |
| **Database** | MySQL | 8.0+ | Database relasional (`db_silamet`) dengan Soft Deletes |
| **Frontend UI** | Blade + Tailwind CSS | v3 | UI modern, responsif mobile/tablet/kiosk monitor |
| **Reactivity** | Alpine.js | v3 | State management interaktif Kiosk & modal dashboard |
| **Autentikasi & RBAC**| Laravel Sanctum + Spatie | v6 | Token API Kiosk & Role-Based Access Control |
| **Laporan & Ekspor** | DomPDF & Laravel Excel | v3 | Cetak bukti laporan PDF & rekapitulasi data Excel |
| **Hardware Daemon** | Python (`pyserial`, OpenCV) | 3.10+ | Serial daemon pembaca reader HW-VX6330 & webcam gate |
| **Mikrokontroler** | Arduino Uno | ATmega328P | Kontroler Relay Buzzer / Sirine 12V di port `COM4` |
| **MCP Integration** | Model Context Protocol | Toolbox | Integrasi AI DB Inspection via `db_config.yaml` |

---

## 3. Kebutuhan Sistem & Perangkat Keras

### Kebutuhan Perangkat Lunak:
- **PHP** ≥ 8.2 (ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`)
- **Composer** ≥ 2.2
- **Node.js** ≥ 18 & **NPM** ≥ 9
- **MySQL** ≥ 8.0 (atau MariaDB 10.4+)
- **Python** ≥ 3.9 (untuk background service gate security)

### Kebutuhan Perangkat Keras Lab:
1. **Meja Kiosk:**
   - PC / Laptop Kiosk dengan monitor layar sentuh.
   - Barcode & QR Code Scanner USB (emulasi HID keyboard).
   - Webcam USB (mendukung WebRTC HTML5).
2. **Pintu Gerbang (Gate Security):**
   - Integrated UHF RFID Reader (HW-VX6330, ISO18000-6C EPC Gen2) + Konverter RS232-to-USB (COM2/COM3).
   - Arduino Uno + Modul Relay Optocoupler (Pin 8 ke Relay, COM4).
   - Sirine / Buzzer 12V DC.
   - Webcam USB Gerbang Pintu (snapshot insiden pelanggaran).

---

## 4. Struktur Direktori Proyek

```
SILAMET/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/                   # REST API Kiosk & Gate Security
│   │   │   ├── DashboardController.php# Metrik & statistik lab
│   │   │   ├── KioskController.php    # Layar Kiosk mandiri
│   │   │   ├── MasterController.php   # Manajemen alat & bahan
│   │   │   ├── LoanController.php     # Transaksi peminjaman
│   │   │   ├── GateLogController.php  # Audit trail log pintu gerbang
│   │   │   └── MaintenanceController.php # Tiket kerusakan & log teknisi
│   │   └── Middleware/                # RBAC Spatie & autentikasi
│   ├── Models/                        # User, Item, Loan, GateLog, Maintenance
│   └── Services/                      # GateValidationService, BarcodeTokenService
├── database/
│   ├── migrations/                    # Skema tabel migrasi database
│   └── seeders/                       # LaboratorySeeder (Data riil SMKN 1 Cibinong)
├── hardware/
│   ├── gate_listener.py               # Production Python serial listener daemon
│   ├── simulate_gate.py               # Virtual hardware gate reader simulator
│   ├── requirements.txt               # Dependencies: pyserial, requests, opencv-python
│   └── README.md                      # Panduan wiring hardware Arduino & pinout
├── public/                            # Web root & asset builds
├── resources/
│   ├── views/                         # Blade templates: Kiosk, Dashboard, Cards, Barcodes
│   └── css/                           # Tailwind CSS & theme tokens
├── routes/                            # web.php & api.php
├── storage/                           # Local storage (foto snapshot peminjam & gate)
├── tests/                             # 94 Automated Unit & Feature Tests
├── AGENTS.md                          # Aturan perilaku pengkodean AI assistant
├── SYSTEM_SPECIFICATION.md            # Acuan tunggal spesifikasi lengkap (SSOT)
├── db_config.yaml                     # Konfigurasi MCP Toolbox for Databases
├── artisan                            # CLI Laravel
├── composer.json
├── package.json
└── tailwind.config.js
```

---

## 5. Instalasi & Setup Cepat

### 1. Kloning Repositori & Pasang Dependensi
```powershell
# Dependensi PHP
composer install

# Dependensi Frontend (Tailwind & Alpine)
npm install
npm run build

# Dependensi Python Hardware Listener
cd hardware
pip install -r requirements.txt
cd ..
```

### 2. Konfigurasi Environment (`.env`)
Salin file `.env.example` ke `.env`:
```powershell
copy .env.example .env
php artisan key:generate
```

Sesuaikan koneksi database MySQL di `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_silamet
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Eksekusi Migrasi & Seeder Laboratorium
```powershell
php artisan migrate:fresh --seed
php artisan storage:link
```
> Perintah ini akan menyiapkan seluruh tabel database dan mengisikan data master default (pengguna admin/teknisi/siswa dan sampel alat laboratorium SIJA).

---

## 6. Menjalankan Aplikasi

### Cara Termudah (1-Click All-in-One Launcher)
Tersedia launcher otomatis sehingga Anda **tidak perlu membuka 3 jendela terminal terpisah**:
- **Cukup klik ganda (double-click):** file [`START_SILAMET.bat`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/START_SILAMET.bat) pada folder root proyek.
- Atau jalankan melalui terminal:
  ```powershell
  npm run start:all
  ```
Launcher ini secara bersamaan menjalankan 3 service sekaligus dalam 1 jendela konsol berwarna:
1. **Laravel Backend Server** (`php artisan serve --port=8000`)
2. **Vite Frontend Asset Watcher** (`npm run dev`)
3. **Hardware Gate Listener** (UHF RFID Reader di `COM2` dan Arduino Relay Buzzer di `COM4`)

> **Tips Mode Simulasi (Tanpa Hardware Fisik):**  
> Jika sedang menguji tanpa perangkat RFID/Arduino tercolok, klik ganda [`START_SILAMET_SIMULASI.bat`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/START_SILAMET_SIMULASI.bat) atau jalankan `npm run start:sim`.

---

### Cara Manual (Terminal Terpisah)
Jika Anda ingin menjalankan setiap service di jendela terminal terpisah secara manual:
```powershell
# Terminal 1: Web server Laravel (port 8000)
php artisan serve --port=8000

# Terminal 2: Vite watcher frontend
npm run dev

# Terminal 3: Hardware listener daemon
python hardware/gate_listener.py --port COM2 --baud 57600 --relay-port COM4 --relay-baud 9600 --mode answer --debounce 4.0
```

- **Akses Meja Kiosk Mandiri:** `http://127.0.0.1:8000/kiosk` *(Disarankan Chrome/Edge dengan parameter `--kiosk`)*.
- **Akses Dashboard Web Admin/Staf:** `http://127.0.0.1:8000/login`.

### Akun Login Default Seeder:
- **Kepala Program (Kaprog):** NIP `197501012000121001` / Email `kaprog@silamet.sch.id` (Password: `password`)
- **Kepala Bengkel (Kabeng):** NIP `198002022005011002` / Email `kabeng@silamet.sch.id` (Password: `password`)
- **Teknisi Lab:** NIP `199003032015021003` / Email `teknisi@silamet.sch.id` (Password: `password`)
- **Siswa:** Akses via pemindaian NIS pada Meja Kiosk (contoh NIS: `10220938`, `10220939`).

---

## 7. Perangkat Keras & Daemon Gate Security

### Menjalankan Hardware Listener Riil
Menghubungkan pembaca UHF RFID HW-VX6330 dan Arduino Uno Relay Buzzer ke server lokal:
```powershell
python hardware/gate_listener.py --port COM2 --baud 57600 --relay-port COM4 --relay-baud 9600 --mode answer --debounce 4.0
```

### Menjalankan Simulator Virtual (Tanpa Hardware Fisik)
Jika sedang mengembangkan fitur tanpa kabel hardware reader terpasang:
```powershell
python hardware/simulate_gate.py
```
atau gunakan opsi listener simulator:
```powershell
python hardware/gate_listener.py --simulate
```

---

## 8. Perintah Artisan & Pengujian Otomatis

### Menjalankan Seluruh Automated Test Suite
Sistem dilengkapi dengan unit dan feature testing menyeluruh:
```powershell
php artisan test
```
> **Status:** Terverifikasi **100% PASS**.

---

## 9. REST API Endpoints

Seluruh API terpusat pada prefix `/api/v1/`:

| Metode | Endpoint | Autentikasi | Fungsi & Keterangan |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/v1/auth/login` | Publik | Login instan Kiosk menggunakan nomor NIS/NIP |
| `POST` | `/api/v1/auth/logout` | Sanctum Token | Logout dan invalidasi token Kiosk |
| `GET` | `/api/v1/items` | Publik | Ambil katalog alat yang berstatus `tersedia` |
| `GET` | `/api/v1/items/{code}` | Publik | Detail alat berdasarkan `item_code` atau barcode |
| `GET` | `/api/v1/loans/active-by-nis` | Publik | Ambil daftar pinjaman aktif siswa peminjam |
| `POST` | `/api/v1/loans` | Sanctum Token | Proses peminjaman alat Kiosk (+ snapshot foto) |
| `POST` | `/api/v1/loans/return-kiosk` | Publik | Pengembalian mandiri multi-barang & lapor rusak |
| `POST` | `/api/v1/gate/check-tag` | Publik | **Hardware Gate Endpoint** (verifikasi EPC tag & status respon `allowed` / `denied`) |

---

## 10. Hak Akses Pengguna (*RBAC*)

Sistem menerapkan pembagian peran tegas (*Role-Based Access Control*):
- **Siswa (`siswa`):** Khusus mode Kiosk Layanan Mandiri. Dilarang mengakses dashboard admin web.
- **Teknisi (`teknisi`):** Akses penuh modul Maintenance & Repair (`/maintenance`), eksekusi perbaikan alat rusak, dan restorasi status barang kembali `tersedia`.
- **Kepala Bengkel (`kabeng`):** Pengawasan ketersediaan alat, audit trail log gerbang RFID (`/gate-logs`), dan persetujuan perawatan preventif.
- **Kepala Program (`kaprog`):** Rekapitulasi aset, monitoring biaya perbaikan, audit keamanan, dan ekspor laporan resmi.

---

## 11. Skema Basis Data & Aturan Jatuh Tempo

### Aturan Perhitungan Batas Pengembalian (`due_at`):
- **Pulang Sekolah (`pulang_sekolah`):** Hari yang sama pukul **16:00 WIB**.
- **1 Hari (`1_hari`):** `borrowed_at + 1 hari` (pukul 23:59 WIB).
- **2 Hari (`2_hari`):** `borrowed_at + 2 hari` (pukul 23:59 WIB).
- **3 Hari (`3_hari`):** `borrowed_at + 3 hari` (pukul 23:59 WIB).
- **1 Minggu (`1_minggu`):** `borrowed_at + 7 hari` (pukul 23:59 WIB).

### Ringkasan Tabel Database:
- `users`: Master pengguna lab (NIS/NIP, nama, kontak WhatsApp, role).
- `items`: Master katalog alat dan bahan (kode barang, token barcode, EPC tag UHF RFID, status ketersediaan, lokasi rak).
- `loans`: Transaksi peminjaman (relasi siswa-alat, durasi, due date, foto peminjam, waktu kembali).
- `gate_logs`: Rekam jejak pembacaan gerbang RFID (`allowed` vs `denied`, foto snapshot pelanggar).
- `maintenance_tickets`: Tiket kendala teknis dan alat rusak.
- `maintenance_logs`: Riwayat tindakan servis, teknisi penanggung jawab, dan rincian biaya.

---

## 12. Dokumen Terkait & Referensi

- **[SYSTEM_SPECIFICATION.md](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/SYSTEM_SPECIFICATION.md)** — Dokumen spesifikasi sistem lengkap (*Single Source of Truth*), logika bisnis, dan diagram relasi tabel (ERD).
- **[AGENTS.md](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/AGENTS.md)** — Standar pengkodean, aturan anti-mock data, dan pedoman desain untuk agen AI & kontributor.
- **[hardware/README.md](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/hardware/README.md)** — Petunjuk perakitan kabel serial RS232/USB, skema Arduino Uno Relay, dan konfigurasi buzzer 12V.
- **[db_config.yaml](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/db_config.yaml)** — Konfigurasi query inspeksi database untuk MCP Toolbox for Databases.

---

## 📜 Lisensi & Hak Cipta

Hak Cipta © 2026 **Laboratorium SIJA — SMK Negeri 1 Cibinong**. Seluruh hak cipta dilindungi undang-undang.
