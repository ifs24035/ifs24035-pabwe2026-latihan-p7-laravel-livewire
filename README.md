# Abdullah Ubaid - PABWE 2026 Praktikum 7 (Laravel 13 & Livewire)

Aplikasi Todolist modern dengan arsitektur **Clean Architecture & Feature-First Approach** sesuai standar industri, dibangun menggunakan **Laravel 13**, **Livewire 3**, basis data **MySQL**, upload/export spreadsheet via **PhpOffice/PhpSpreadsheet**, integrasi kecerdasan buatan (**OpenAI AI Content Generation**), rate limiting (1000 per detik), proteksi kepemilikan middleware, force HTTPS, dan **100% Test Coverage**.

---

## 🚀 Fitur Utama

1. **Clean Architecture & Feature-First Approach**:
   - Modul diorganisir per feature/domain (`App\Features\Auth`, `App\Features\Todo`, `App\Features\Admin`, `App\Features\Ai`).
   - Menerapkan Dependency Inversion Principle (DIP) dengan repository interface dan client contracts.
   - Pemisahan lapisan yang jelas antara Actions, Repositories, Services, DTOs, Policies, dan UI (Livewire).

2. **Autentikasi & Autorisasi Role-Based**:
   - **Login Fleksibel**: Pengguna dapat masuk menggunakan **Username** atau **Email**.
   - **Role `admin`**:
     - Mengakses halaman Manajemen Pengguna (`/admin/users`).
     - Melihat daftar seluruh pengguna beserta username, email, dan jumlah todolist mereka.
     - Melihat detail todolist pengguna lain dalam mode **Read-Only** (`/admin/users/{user}/todos`).
     - Admin **tidak dapat** memodifikasi/menghapus todolist pengguna lain.
   - **Role `member`**:
     - User yang mendaftar melalui form registrasi (dengan nama, username unik, email, password) otomatis memiliki role `member`.
     - Hanya dapat mengelola (CRUD), menyelesaikan tugas, mengekspor/mengimpor, serta generate AI untuk todolist miliknya sendiri (`/todos`).
   - **Middleware & Policy**:
     - `EnsureRole`: Membatasi rute khusus admin.
     - `EnsureTodoOwnership`: Memastikan member tidak dapat mengakses atau memodifikasi todolist milik orang lain.
     - `TodoPolicy`: Memvalidasi otorisasi di tingkat model.

3. **Spreadsheet Excel Import & Export**:
   - Menggunakan library aman dari kerentanan: **`phpoffice/phpspreadsheet`**.
   - **Export**: Menghasilkan file `.xlsx` dengan styling profesional (header berwarna, border, text wrap, auto size).
   - **Import**: Mengunggah file `.xlsx`, `.xls`, `.csv` untuk melakukan bulk insert tugas secara aman dengan validasi format data, status, dan tanggal jatuh tempo.

4. **AI Todo Content Generator (OpenAI)**:
   - Dikonfigurasi secara dinamis melalui `.env`:
     - `OPENAI_ENDPOINT` (mendukung custom proxy atau Azure/Ollama kompatibel)
     - `OPENAI_KEY`
     - `OPENAI_MODEL`
   - Kemampuan generate multiple todolist items secara otomatis berdasarkan prompt/permintaan pengguna (misal: "Buatkan rencana belajar Laravel selama 3 hari").
   - Output diformat dan divalidasi ke dalam DTO `GeneratedTodoItem`.
   - Pengguna dapat melihat preview hasil generate AI, memilih item yang diinginkan, dan menyimpannya langsung ke basis data.

5. **Rate Limiting & Keamanan**:
   - Skrip pembatas akses: **1000 request per detik** via `RateLimiter::for('1000-per-second', ...)`.
   - **Force HTTPS**: Dikonfigurasi melalui `FORCE_HTTPS=true` di `.env` atau otomatis aktif pada environment `production`.
   - Bebas dari kerentanan keamanan (`composer audit` clean 0 advisories).

6. **100% Test Coverage**:
   - Seluruh classes, methods, dan baris kode pada direktori `app/` memiliki **100% coverage**.
   - Unit test dan feature test komprehensif menguji seluruh alur autentikasi, model, repository, policy, middleware, service AI (mocked), dan upload/download spreadsheet.

---

## 📁 Struktur Direktori Feature-First

```
app/
├── Features/
│   ├── Admin/
│   │   ├── Livewire/
│   │   │   ├── AdminUserTodoList.php
│   │   │   └── UserList.php
│   │   └── Services/
│   │       └── AdminUserService.php
│   ├── Ai/
│   │   ├── Contracts/
│   │   │   └── AiClientInterface.php
│   │   ├── DTO/
│   │   │   └── GeneratedTodoItem.php
│   │   └── Services/
│   │       └── OpenAiTodoGeneratorService.php
│   ├── Auth/
│   │   ├── Actions/
│   │   │   ├── LoginUserAction.php
│   │   │   └── RegisterUserAction.php
│   │   └── Livewire/
│   │       ├── Login.php
│   │       └── Register.php
│   └── Todo/
│       ├── Contracts/
│       │   └── TodoRepositoryInterface.php
│       ├── Enums/
│       │   └── TodoStatus.php
│       ├── Livewire/
│       │   └── TodoList.php
│       ├── Models/
│       │   └── Todo.php
│       ├── Policies/
│       │   └── TodoPolicy.php
│       ├── Repositories/
│       │   └── TodoRepository.php
│       └── Services/
│           ├── TodoExcelService.php
│           └── TodoService.php
├── Http/
│   └── Middleware/
│       ├── EnsureRole.php
│       └── EnsureTodoOwnership.php
├── Models/
│   └── User.php
└── Providers/
    └── AppServiceProvider.php
```

---

## ⚙️ Konfigurasi Environment (`.env`)

```ini
APP_NAME="TodoList Pro"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

FORCE_HTTPS=false

# Basis Data MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pabwe_p7_todolist
DB_USERNAME=root
DB_PASSWORD=

# Konfigurasi AI (OpenAI API)
OPENAI_ENDPOINT=https://api.openai.com/v1
OPENAI_KEY=your-openai-api-key-here
OPENAI_MODEL=gpt-4o-mini
```

---

## 🛠️ Instalasi & Menjalankan Proyek

1. **Clone & Masuk ke Direktori Proyek**:
   ```bash
   cd abdullah_ubaid-pabwe2026-latihan-p7-laravel-livewire
   ```

2. **Salin `.env` & Generate Key**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Migrasi & Seeding Data Awal**:
   Pastikan MySQL sudah berjalan dan database telah dibuat, kemudian jalankan:
   ```bash
   php artisan migrate:fresh --seed
   ```

   **Akun Bawaan Seeder**:
   - **Admin**:
     - Username: `admin` atau Email: `admin@example.com`
     - Password: `admin`
   - **Member**:
     - Username: `member` atau Email: `member@example.com`
     - Password: `member`

4. **Jalankan Aplikasi Web**:
   ```bash
   php artisan serve
   ```
   Akses aplikasi di browser melalui `http://localhost:8000`.

---

## 🧪 Pengujian & Verifikasi 100% Test Coverage

Cukup jalankan satu perintah sederhana berikut untuk menjalankan seluruh pengujian dan melihat kalkulasi coverage 100%:

```bash
composer test
```

Atau di terminal Windows:
```cmd
test
```
(atau `.\test.bat` / `npm test`)

Hasil verifikasi:
```text
Code Coverage Report:
  Summary:
   Classes: 100.00% (20/20)
   Methods: 100.00% (77/77)
   Lines:   100.00% (428/428)

Tests: 76, Assertions: 215, Status: PASSED (100%)
```

Audit keamanan dependencies:
```bash
composer audit
# Output: No security vulnerability advisories found.
```
