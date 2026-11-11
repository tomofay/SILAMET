# DAFTAR FITUR PENDING & ROADMAP PENGEMBANGAN (TODO) — SILAMET
**Sistem Informasi Manajemen Inventarisasi Laboratorium Terintegrasi Gate Security UHF RFID & Modul Pemeliharaan**  
**Ruang Lingkup:** Laboratorium Jurusan Sistem Informasi, Jaringan, dan Aplikasi (SIJA) — SMK Negeri 1 Cibinong  
**Status Dokumen:** Terakhir diperbarui `10 November 2026` (*Seluruh modul selesai — 131 tests / 579 assertions pass*)

---

## Ringkasan Status Seluruh Fitur

| No | Modul / Fitur | Prioritas | Status Saat Ini | Kategori |
| :---: | :--- | :---: | :---: | :--- |
| 1 | [Penjadwalan Perawatan Preventif & Hitung Mundur](#a-perawatan-preventif--hitung-mundur-sudah-diterapkan-) | — | **Sudah Diterapkan ✅** | Pemeliharaan (Preventif) |
| 2 | [Alur Perbaikan Kerusakan, Biaya & Restorasi Alat](#b-perbaikan-kerusakan-kuratif-biaya--restorasi-sudah-diterapkan-) | — | **Sudah Diterapkan ✅** | Pemeliharaan (Kuratif) |
| 3 | [Alert Internal Staf Tiket Rusak Baru](#c-notifikasi-alert-internal-tiket-rusak-baru-sudah-diterapkan-) | — | **Sudah Diterapkan ✅** | Notifikasi Staf |
| 4 | [Sistem Notifikasi Telegram Bot Lengkap](#d-integrasi-notifikasi-telegram-bot-sudah-diterapkan-) | — | **Sudah Diterapkan ✅** | Notifikasi & Alerting |
| 5 | [1 Kamera Hub, Multi-Tag Burst & Foto Pelanggar Web](#e-1-kamera-hub-optimasi-burst--foto-pelanggar-web-sudah-diterapkan-) | — | **Sudah Diterapkan ✅** | Hardware, Kiosk & Gate |
| 6 | [Redesign UI Data-Dense & Kiosk Minimalist](#f-redesign-ui-data-dense--kiosk-minimalist-sudah-diterapkan-) | — | **Sudah Diterapkan ✅** | Antarmuka Pengguna |
| 7 | [Launcher 1-File Seluruh Service](#g-launcher-1-file-semua-service-sudah-diterapkan-) | — | **Sudah Diterapkan ✅** | Operasional Lab |
| 8 | [Live Auto-Refresh Monitoring Gate (`/gate-logs`)](#h-live-auto-refresh-monitoring-gate-sudah-diterapkan-) | — | **Sudah Diterapkan ✅** | Gate Security |
| 9 | [Simpan & Tampilkan Foto Peminjam di Web (`/loans/{id}`)](#i-simpan--tampilkan-foto-verifikasi-peminjam-di-web-sudah-diterapkan-) | — | **Sudah Diterapkan ✅** | Sirkulasi Peminjaman |
| 10 | [Buku Stok Bahan Habis Pakai (*Stock Ledger*)](#j-buku-stok-bahan-habis-pakai-stock-ledger-sudah-diterapkan-) | — | **Sudah Diterapkan ✅** | Manajemen Bahan |
| 11 | [Pembersihan Arsip Foto Snapshot (`snapshots:prune`)](#k-pembersihan-arsip-foto-snapshot-storage-pruning-sudah-diterapkan-) | — | **Sudah Diterapkan ✅** | Pemeliharaan Server |
| 12 | [Ekspor Laporan PDF & Excel (`/reports`)](#l-ekspor-laporan-pdf--excel-reports-sudah-diterapkan-) | — | **Sudah Diterapkan ✅** | Laporan Lab |

---

## BAGIAN I — FITUR YANG SUDAH DITERAPKAN ✅

### A. Perawatan Preventif & Hitung Mundur — [SUDAH DITERAPKAN ✅]
- [x] **Jadwal Perawatan Berkala Fleksibel (`/maintenance/schedules`):**
  - CRUD jadwal di [`PreventiveScheduleController.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Http/Controllers/PreventiveScheduleController.php) dan model [`PreventiveSchedule.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Models/PreventiveSchedule.php) (interval custom misal MikroTik 3 bulan sekali, 1/6/12 bulan).
  - Kolom pelacakan tahap pengingat (`last_notified_stage`, `last_notified_at`) di tabel `preventive_schedules`.
- [x] **Tanggal Servis Terakhir & Kalkulasi Otomatis (*Countdown*):**
  - Aksi **"Catat Servis Selesai"** (`mark-serviced`) otomatis menghitung ulang `next_due_date = last_serviced_at + interval_months` dan mereset tahap notifikasi.
  - Badge hitung mundur semantik pada antarmuka (`> 14 hari` hijau, `8–14 hari` biru, `0–7 hari` kuning, `< 0 hari` merah).

### B. Perbaikan Kerusakan Kuratif, Biaya & Restorasi — [SUDAH DITERAPKAN ✅]
- [x] **Tiket Kerusakan & Auto-Lock Aset:** Siswa/guru dapat membuat tiket rusak dari Kiosk saat pengembalian maupun web admin (`/maintenance`). Status barang otomatis berubah ke `under_maintenance` dan langsung terkunci dari katalog peminjaman Kiosk.
- [x] **Detail Tindakan Perbaikan (`maintenance_logs`):** Mencatat deskripsi perbaikan teknisi dengan klasifikasi `internal` (teknisi lab SIJA) atau `vendor_luar` (servis eksternal/klaim garansi).
- [x] **Riwayat Biaya Perbaikan & Suku Cadang:** Kolom `cost` pada `maintenance_logs` mencatat rincian biaya perbaikan/sparepart untuk rekap keuangan laboratorium.
- [x] **Restorasi Otomatis Status Barang:** Saat tiket ditandai selesai (`status = 'resolved'`), barang otomatis kembali ke status `tersedia` dan dapat dipinjam kembali di Kiosk.

### C. Notifikasi Alert Internal Tiket Rusak Baru — [SUDAH DITERAPKAN ✅]
- [x] Badge jumlah tiket berstatus `open` tampil langsung pada sidebar menu **Maintenance & Repair** di [`resources/views/layouts/app.blade.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/resources/views/layouts/app.blade.php#L93-L111).
- [x] Pengiriman notifikasi otomatis via [`SendMaintenanceTicketTelegramJob.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Jobs/SendMaintenanceTicketTelegramJob.php) saat tiket rusak baru dibuat maupun saat perbaikan selesai.

### D. Integrasi Notifikasi Telegram Bot — [SUDAH DITERAPKAN ✅]
- [x] **Service Terpusat [`TelegramService.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Services/TelegramService.php):** Konfigurasi bot token, grup lab, dan chat admin melalui `.env` + [`config/services.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/config/services.php#L38-L49).
- [x] **Alert Pelanggaran Gate + Foto Snapshot:** [`SendGateViolationTelegramJob.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Jobs/SendGateViolationTelegramJob.php) mengirim pesan + foto (`sendPhoto`) ke grup & admin; otomatis **tidak mengirim** barang aktif `dipinjam` dan tidak menampilkan label lama `Status: Dipinjam` pada item yang sudah di-*soft-delete*.
- [x] **Pengingat Keterlambatan Peminjaman (`loans:check-overdue`):** [`CheckOverdueLoans.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Console/Commands/CheckOverdueLoans.php) menghitung durasi keterlambatan akurat (menit/jam/hari) serta mengirim ke grup lab maupun DM siswa (jika akun tertaut lewat perintah `/daftar` via [`SyncTelegramUsers.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Console/Commands/SyncTelegramUsers.php)).
- [x] **Pengingat Perawatan Preventif (`maintenance:check-due`) & Ringkasan Harian (`lab:daily-summary`):** [`CheckDueMaintenance.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Console/Commands/CheckDueMaintenance.php) mengirim pengingat H-7, H-3, H-1, Hari-H, dan Lewat Jadwal; [`SendDailyLabSummary.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Console/Commands/SendDailyLabSummary.php) mengirim rekap operasional pukul 17:00 WIB lewat penjadwalan di [`routes/console.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/routes/console.php).

### E. 1 Kamera Hub, Optimasi Burst & Foto Pelanggar Web — [SUDAH DITERAPKAN ✅]
- [x] **1 Kamera Fisik untuk 3 Fungsi (`http://127.0.0.1:8765`):** [`hardware/gate_listener.py`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/hardware/gate_listener.py) melayani preview & snapshot peminjaman Kiosk (`/stream.mjpg`, `/snapshot`), penyimpanan foto pelanggar Gate, dan lampiran Telegram sekaligus dari 1 kamera USB tanpa bentrok device OS.
- [x] **Kecepatan Notifikasi & Multi-Tag Burst:** Frame JPEG panas di RAM dikirim langsung pada tahap pertama, burst tag dikonsolidasikan per `150 ms` menjadi 1 lookup DB ([`GateValidationService::checkTags()`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Services/GateValidationService.php#L40-L85)), 1 foto disk, dan 1 pesan terpadu Telegram, dengan `queue:work --sleep=0.25`.
- [x] **Penampilan Foto Pelanggar di Website (`/gate-logs`):** Pemulihan *Windows NTFS Junction* `public/storage -> storage/app/public`, rute terproteksi auth [`GateLogController::photo()`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Http/Controllers/GateLogController.php#L48-L82), kolom thumbnail **`Snapshot Kamera`** + modal pratinjau di [`resources/views/gate-logs/index.blade.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/resources/views/gate-logs/index.blade.php) dan [`resources/views/gate-logs/show.blade.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/resources/views/gate-logs/show.blade.php).

### F. Redesign UI Data-Dense & Kiosk Minimalist — [SUDAH DITERAPKAN ✅]
- [x] Seluruh halaman admin & komponen memakai desain *border-first* profesional bebas emoji/AI slop sesuai [`AGENTS.md`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/AGENTS.md).
- [x] Halaman [`resources/views/kiosk/index.blade.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/resources/views/kiosk/index.blade.php) mempertahankan animasi indikator scanner, menggunakan header tipografi bersih **"SILAMET"**, dan tombol sentuh responsif (`>= 44x44px`).

### G. Launcher 1-File Semua Service — [SUDAH DITERAPKAN ✅]
- [x] [`START_SILAMET.bat`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/START_SILAMET.bat) & [`START_SILAMET_SIMULASI.bat`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/START_SILAMET_SIMULASI.bat) menjalankan `php artisan serve`, `npm run dev`, `hardware/gate_listener.py`, `queue:work`, dan `schedule:work` sekaligus dalam 1 terminal lewat `concurrently` di [`package.json`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/package.json#L11-L12).

### H. Live Auto-Refresh Monitoring Gate — [SUDAH DITERAPKAN ✅]
- [x] **Endpoint JSON Ringan [`GateLogController::feed()`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Http/Controllers/GateLogController.php#L34-L86):** Rute `GET /gate-logs/feed` (`gate-logs.feed`) menghitung counter statistik terbaru, me-render partial tabel [`_rows.blade.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/resources/views/gate-logs/partials/_rows.blade.php) hanya saat `latest_id` berubah, serta mengembalikan alert `denied` terbaru tanpa menyingkap nama item yang sudah di-*soft-delete*.
- [x] **Polling Live 3 Detik di [`gate-logs/index.blade.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/resources/views/gate-logs/index.blade.php):** Tombol toggle `LIVE 3s`, toggle `Suara: OFF/ON` (Web Audio beep opsional), toast notifikasi `#gate-alert-toast` + tombol **Lihat Foto**, delegasi modal snapshot, serta jeda otomatis saat tab tidak aktif atau sedang berada di halaman paginasi > 1.

### I. Simpan & Tampilkan Foto Verifikasi Peminjam di Web — [SUDAH DITERAPKAN ✅]
- [x] **Skema & Model:** Migrasi [`2026_11_10_131500_add_capture_image_path_to_loans_table.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/database/migrations/2026_11_10_131500_add_capture_image_path_to_loans_table.php) dan `$fillable` di [`Loan.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Models/Loan.php).
- [x] **Persistensi Kiosk & Rute Foto Aman:** [`LoanApiController::store()`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Http/Controllers/Api/LoanApiController.php#L85-L94) menyimpan `$photoPath` ke setiap transaksi peminjaman Kiosk; [`LoanController::photo()`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Http/Controllers/LoanController.php#L170-L204) menyajikan foto terproteksi auth di `GET /loans/{loan}/photo` (`loans.photo`).
- [x] **Antarmuka Web:** Kartu **Foto Verifikasi Peminjam (Kiosk)** pada [`loans/show.blade.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/resources/views/loans/show.blade.php) (dengan fallback status bersih bila foto null/dipruning) serta indikator ikon kamera di [`loans/index.blade.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/resources/views/loans/index.blade.php).

### J. Buku Stok Bahan Habis Pakai (*Stock Ledger*) — [SUDAH DITERAPKAN ✅]
- [x] **Skema & Model:** Migrasi [`2026_11_10_131600_create_stock_movements_table.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/database/migrations/2026_11_10_131600_create_stock_movements_table.php), enum [`StockMovementType.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Enums/StockMovementType.php), dan model [`StockMovement.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Models/StockMovement.php) lengkap dengan `stock_before`, `stock_after`, `recipient_name`, dan `description`.
- [x] **Transaksi Atomik & Audit Otomatis:** [`StockMovementController.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Http/Controllers/StockMovementController.php) mengunci baris item (`lockForUpdate()` inside `DB::transaction`), memvalidasi bahan `bahan_habis_pakai`, mencegah stok negatif; [`MasterController.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Http/Controllers/MasterController.php) otomatis mencatat mutasi awal maupun penyesuaian stok manual dari Master Aset.
- [x] **Antarmuka Buku Stok:** Menu sidebar **Buku Stok Bahan**, halaman penuh [`stock-movements/index.blade.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/resources/views/stock-movements/index.blade.php), modal mutasi cepat di [`items/index.blade.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/resources/views/items/index.blade.php), dan panel mutasi inline pada [`items/show.blade.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/resources/views/items/show.blade.php).

### K. Pembersihan Arsip Foto Snapshot (*Storage Pruning*) — [SUDAH DITERAPKAN ✅]
- [x] **Artisan Command [`PruneSnapshots.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Console/Commands/PruneSnapshots.php):** Perintah `php artisan snapshots:prune {--days=60} {--dry-run}` membersihkan `capture_image_path` log/peminjaman lama, menjaga file yang masih direferensikan baris yang lebih baru (*shared loan photo*), menghapus file yatim lama di `uploads/photos/gate` & `uploads/photos/loans`, serta mencetak statistik ruang terbebas.
- [x] **Penjadwalan Bulanan:** Terdaftar otomatis setiap tanggal 1 pukul `02:00 WIB` di [`routes/console.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/routes/console.php#L30-L34).

### L. Ekspor Laporan PDF & Excel (`/reports`) — [SUDAH DITERAPKAN ✅]
- [x] **Layanan Query & Ekspor [`ReportGenerationService.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Services/ReportGenerationService.php) & [`ReportController.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Http/Controllers/ReportController.php):** Mengambil data riil untuk 4 jenis laporan (`inventaris`/`inventory`, `loans`, `gate_logs`, `maintenance`) lengkap dengan validasi filter tanggal (`start_date`, `end_date`) & status.
- [x] **Template PDF Berkop Resmi (`Barryvdh\DomPDF`):** Layout bersama [`_layout.blade.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/resources/views/reports/pdf/_layout.blade.php) berkop SMKN 1 Cibinong Lab SIJA & blok tanda tangan, beserta template [`inventaris.blade.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/resources/views/reports/pdf/inventaris.blade.php), [`loans.blade.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/resources/views/reports/pdf/loans.blade.php), [`gate_logs.blade.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/resources/views/reports/pdf/gate_logs.blade.php), dan [`maintenance.blade.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/resources/views/reports/pdf/maintenance.blade.php).
- [x] **Class Excel Export (`Maatwebsite\Excel`):** [`ReportExport.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/app/Exports/ReportExport.php) menghasilkan berkas `.xlsx` terformat (teks EPC/NIS agar tidak terpotong notasi ilmiah, pemisah ribuan Rupiah pada biaya servis, header & zebra row rapi).
- [x] **Filter Periode & Status pada [`reports/index.blade.php`](file:///c:/Users/PC-SIJA/Desktop/projek/SILAMET/resources/views/reports/index.blade.php):** Input `start_date`/`end_date`, tombol preset cepat (*Hari Ini, 7 Hari Terakhir, Bulan Ini, Semua Periode*), filter status per jenis laporan, serta badge jumlah data aktual.

---

## BAGIAN II — FITUR YANG BELUM DITAMBAHKAN (PENDING)

*Tidak ada item pending tersisa — seluruh fitur utama dalam daftar pengembangan SILAMET telah diterapkan dan diverifikasi.*

---

## Catatan Khusus Arsitektur
- **Telegram Bot API:** Digunakan sebagai kanal notifikasi utama (gate + foto, overdue + durasi, preventif, tiket M&R, ringkasan harian) karena instan, gratis, dan mendukung grup teknisi tanpa batasan kuota API pihak ketiga.
- **WhatsApp Gateway:** Sudah ditiadakan dari otomatisasi pengingat; nomor telepon/WhatsApp siswa (`users.phone`) tetap disimpan untuk tautan kontak manual admin (`https://wa.me/...`).
