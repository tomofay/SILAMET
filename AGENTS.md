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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.0
- laravel/framework (LARAVEL) - v11
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/breeze (BREEZE) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- alpinejs (ALPINEJS) - v3
- tailwindcss (TAILWINDCSS) - v3

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs
- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches when dealing with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The `search-docs` tool is perfect for all Laravel-related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless there is something very complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v11 rules ===

## Laravel 11

- Use the `search-docs` tool to get version-specific documentation.
- Laravel 11 brought a new streamlined file structure which this project now uses.

### Laravel 11 Structure
- In Laravel 11, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

### New Artisan Commands
- List Artisan commands using Boost's MCP tool, if available. New commands available in Laravel 11:
    - `php artisan make:enum`
    - `php artisan make:class`
    - `php artisan make:interface`

=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== phpunit/core rules ===

## PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== tailwindcss/core rules ===

## Tailwind CSS

- Use Tailwind CSS classes to style HTML; check and use existing Tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc.).
- Think through class placement, order, priority, and defaults. Remove redundant classes, add classes to parent or child carefully to limit repetition, and group elements logically.
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing; don't use margins.

<code-snippet name="Valid Flex Gap Spacing Example" lang="html">
    <div class="flex gap-8">
        <div>Superior</div>
        <div>Michigan</div>
        <div>Erie</div>
    </div>
</code-snippet>

### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.

=== tailwindcss/v3 rules ===

## Tailwind CSS 3

- Always use Tailwind CSS v3; verify you're using only classes supported by this version.
</laravel-boost-guidelines>
