# AGENTS.md — SILAMET PROJECT CODING & BEHAVIOR RULES
**Sistem Informasi Manajemen Inventarisasi Laboratorium Terintegrasi Gate Security UHF RFID & Modul Pemeliharaan**  
**Ruang Lingkup:** Laboratorium Jurusan Sistem Informasi, Jaringan, dan Aplikasi (SIJA) — SMK Negeri 1 Cibinong  
**Stack Utama:** Laravel 11 (PHP 8.2+), Eloquent ORM, MySQL (`db_silamet`), Blade Templates, Tailwind CSS v3, Alpine.js, Python Serial Daemon (`pyserial`, OpenCV), Arduino Uno Relay.

---

## 1. STRICT DB-FIRST & ANTI-MOCK DATA POLICY
- **NO HARDCODED MOCK DATA:** DILARANG KERAS membuat data karangan, array dummy (`$mockData = [...]` atau `const mockData = [...]`), atau generator angka acak (`Math.random()`, `rand()`) di dalam Laravel Controller, Blade Views, Alpine.js state, API Routes, maupun Service layer.
- **DIRECT DB VERIFICATION:** Setiap data yang diproyeksikan ke layar HARUS berasal dari query Database riil via Eloquent Model / Query Builder atau API REST Sanctum.
- **EMPTY STATES:** Jika query database mengembalikan collection kosong (`$items->isEmpty()`), array kosong (`[]`), atau `null`, tampilkan *Empty State* resmi yang bersih (contoh: "Belum ada data peminjaman aktif", "Tidak ada riwayat insiden gate"), DILARANG mengisi ruang kosong tersebut dengan angka atau teks buatan.
- **SCHEMA VERIFICATION BEFORE CODING:** Agent WAJIB memeriksa skema tabel dan kolom asli sebelum menulis kode dengan mengecek file migrasi di `database/migrations/`, Model Eloquent di `app/Models/`, atau menjalankan perintah verifikasi (`php artisan tinker`).

---

## 2. SILAMET PROFESSIONAL DESIGN SYSTEM (ANTI-AI SLOP)
Hindari gaya visual generik buatan AI yang pasaran (glowing neon gradients, glassmorphism berlebihan, layout bento tanpa fungsi). Terapkan standar visual SaaS & Kiosk profesional ala Linear, Vercel, dan Tailwind UI:

### A. Color & Surface Palette (Berdasarkan `tailwind.config.js`)
- **Primary:** Modern Indigo (`#4F46E5`), Dark Indigo/Slate 900 (`#0F172A`), Light Indigo (`#818CF8`), Soft Indigo (`#EEF2FF`).
- **Brand / Accent:** Sky Blue (`#0EA5E9` / `#0284C7`), Card Header Deep Indigo (`#1E1B4B`).
- **Semantic Feedback:**
  - Emerald (`success`: `#10B981` / `#065F46`) untuk barang tersedia, akses gate diizinkan (*allowed*), dan status peminjaman sukses.
  - Rose/Red (`danger`: `#EF4444` / `#991B1B`) untuk pelanggaran gate (*denied*), alarm sirine aktif, dan alat rusak/terlambat.
  - Amber (`warning`: `#F59E0B` / `#B45309`) untuk masa tenggang, barang dalam pemeliharaan (*under_maintenance*).
  - Cyan (`info`: `#06B6D4`) untuk status scanner USB siaga dan informasi sistem.
- **DILARANG:** Mencampur warna neon cyan glow, violet-purple glow, atau gradien teks pelangi yang tidak fungsional.

### B. Layout & Spacing
- Gunakan struktur tata letak padat informasi (*data-dense layout*) berbasis grid 4px/8px (`p-4`, `p-6`, `gap-3`, `gap-4`).
- Utamakan struktur batas permukaan solid berbasis border tipis 1px (`border border-neutral-200` pada Light Mode, `border-neutral-800` pada Dark Surface) daripada menggunakan *box-shadow* yang tebal dan kabur.
- **Kiosk & Touch-Friendly:** Elemen antarmuka Kiosk (`/kiosk`) wajib memiliki tombol sentuh berukuran minimal 44x44px, teks jelas terbaca pada jarak pandang Kiosk, dan 100% responsif pada viewport 375px/390px (mobile), 768px (tablet), hingga layar sentuh monitor Kiosk (1024px+).

### C. Typography
- Gunakan font Sans (`Inter`, `Outfit`, `Figtree`) dengan hierarki tegas: Heading `font-bold` / `font-extrabold`, Body Text `text-neutral-600` / `text-neutral-900`.
- Gunakan font *monospace* (`font-mono`) untuk:
  - Nomor Induk Siswa/NIP (`nis_nip`).
  - Barcode / QR token dan ID unik barang (`item_code`, `barcode_token`).
  - Nilai EPC Tag UHF RFID (`epc_tag`).
  - Kode rak / lokasi penempatan alat (`room`, `rack_code`).
  - Timestamp dan format tanggal/waktu.

---

## 3. STRICT ICON-ONLY & NO-DECORATION POLICY
Guna menghindari tampilan *childish* atau *cluttered* akibat ornamen visual AI:

- **STRICT ICON UTILITY:** Gunakan ikon SVG garis (outline) konsisten ala Lucide / Heroicons (`fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"` atau `2`). DILARANG mencampur emoji unicode (✨, 🚀, 🔥, 🤖) sebagai dekorasi UI produksi.
- **NO DECORATIVE BADGES:** DILARANG menggunakan floating badge tanpa aksi fungsional seperti `"✨ Powered by AI"`, `"🚀 New Feature"`, atau *glow effect* dekoratif di belakang ikon.
- **CLEAN ICON WRAPPER:** Jika ikon diletakkan di dalam tombol atau header kartu, gunakan pembungkus simpel tanpa efek bersinar (`p-2 rounded-xl bg-neutral-100 text-neutral-600` atau `bg-primary/10 text-primary`).
- **FUNCTIONAL ACTION ICONS:** Tombol tindakan dalam tabel data (seperti Edit, Hapus, Detail, Cetak Barcode, Filter) WAJIB menggunakan tombol ringkas dengan *tooltip* / `title` dan `aria-label` yang jelas.

---

## 4. PRODUCTION COPYWRITING & ZERO-PLACEHOLDER POLICY (STRICT)
DILARANG KERAS menyisipkan teks generik, jargon pemanis (*marketing fluff*), atau catatan developer ke dalam kode UI/Data:

- **KONTEKS RIIL LAB SIJA:** Gunakan terminologi nyata Laboratorium Komputer & Jaringan SIJA SMKN 1 Cibinong (misal: Router MikroTik, Switch Manageable, Kabel UTP Cat6, Fusion Splicer, Tang Krimping, dsb).
- **NO GENERIC MARKETING FLUFF:** DILARANG menulis kalimat tidak berguna seperti *"Solusi cerdas terbaik untuk masa depan digital Anda"*, *"Platform revolusioner terpercaya"*, dsb. Gunakan teks langsung, fungsional, dan instruktif.
- **NO DEVELOPER NOTES IN PRODUCTION UI:** DILARANG MENAMPILKAN teks instruksi internal seperti *"Catatan: Data ini dummy/placeholder yang dapat diubah di controller"*, *"Lorem ipsum"*, dsb.
- **EXPLICIT ERROR MESSAGES:** Pesan validasi atau error toast harus spesifik dan menyebutkan konteks (misal: *"Nomor WhatsApp belum terdaftar. Harap hubungi admin lab."*, *"Barang 'SLM-001' sedang dalam pemeliharaan (rusak) dan tidak dapat dipinjam."*).

---

## 5. HARDWARE & KIOSK INTEGRATION CONSTRAINTS
Khusus untuk fitur yang bersinggungan dengan perangkat keras dan Kiosk:

- **USB HID SCANNER BUFFERING (NO CONCATENATION):**
  - Scanner USB bekerja sebagai emulasi keyboard cepat yang diakhiri karakter *Enter*.
  - Setiap kali input diproses (`verifyReturnNis()`, `handleNisSubmit()`, `addScannedItem()`), nilai model dan elemen DOM input WAJIB langsung dikosongkan (`$refs.input.value = ''` dan Alpine model = `''`).
  - Cegah penumpukan string barcode yang tertempel (*concatenated string*) saat pemindaian berulang atau saat terjadi kegagalan pemindaian.
  - Scanner autofocus WAJIB dijaga secara halus via `@blur="keepFocus($event)"`, namun **DILARANG** mencuri fokus saat pengguna sedang berinteraksi dengan elemen form interaktif (seperti `textarea` catatan kerusakan barang).
- **UHF RFID GATE SECURITY DAEMON:**
  - Komunikasi antara Gate UHF Reader HW-VX6330 dan Arduino Uno Relay (COM4) dikendalikan melalui `hardware/gate_listener.py`.
  - Integrasi API gate wajib mengarah ke `POST /api/v1/gate/check-tag` dengan respon terstandardisasi: `allowed` (status barang `dipinjam`) atau `denied` (status barang `tersedia` / `under_maintenance` -> memicu buzzer sirine 12V dan snapshot webcam gate).
- **WEBCAM SNAPSHOT VERIFICATION:**
  - Pengambilan foto wajah pada Kiosk dan foto pelanggar pada Gate menggunakan capture frame WebRTC / OpenCV yang dikirim sebagai Base64 atau multipart upload ke storage lokal publik (`storage/app/public/snapshots/`).

---

## 6. CODE QUALITY & ARCHITECTURE PIPELINE
Saat mengimplementasikan fitur baru, perbaikan bug, atau refactoring:

1. **Step 1 (Schema & Exploration):** Periksa skema tabel migrasi di `database/migrations/` dan relasi Model Eloquent (`app/Models/`).
2. **Step 2 (Backend & Transaction Layer):** Buat query / Controller / API Route yang mengambil data riil dari DB. Gunakan `DB::transaction` untuk operasi multi-tabel yang harus atomik (misal: peminjaman barang + update status barang + snapshot log).
3. **Step 3 (Automated Test Execution):** Jalankan pengujian otomatis via `php artisan test`. Pastikan seluruh test suite (94+ tests, 260+ assertions) tetap 100% **PASS** tanpa ada regresi.
4. **Step 4 (Clean Blade & Alpine UI Wiring):** Hubungkan data ke komponen Blade dan Alpine.js tanpa variabel mock dan tanpa teks *fluff*. Pastikan tampilan responsif dan tidak menghasilkan horizontal scrollbar liar.
5. **Step 5 (Hardware & Daemon Check):** Pastikan endpoint yang digunakan Kiosk dan script Python hardware listener tetap sinkron.

DILARANG LOMPAT LANGSUNG KE STEP 4 SEBELUM STEP 1-3 TERVERIFIKASI.
