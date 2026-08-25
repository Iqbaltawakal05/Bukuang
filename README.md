# Bukuang — Personal Finance Management System (Backend API)

![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)
![Tests](https://img.shields.io/badge/Tests-62%20Passed-10B981?style=for-the-badge&logo=phpunit&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

Bukuang Backend REST API adalah layanan API RESTful untuk Sistem Manajemen Keuangan Pribadi (*Personal Finance Management System*). Dibangun menggunakan **Laravel 11**, otentikasi **Laravel Sanctum**, **PostgreSQL** dengan tipe angka desimal presisi tinggi `DECIMAL(15, 2)`, serta pemrosesan antrean latar belakang (*Background Job Queue*) untuk pemrosesan ekspor laporan dan transaksi berulang.

---

## 🌟 Fitur Utama API

1. **Authentication & Profile Management (`/api/v1/auth`, `/api/v1/profile`)**
   - Registrasi user, Login (Sanctum Bearer Token), Logout, Update Profil, & Ubah Password.
2. **Category Management (`/api/v1/categories`)**
   - Pengelolaan kategori Pemasukan (*Income*) & Pengeluaran (*Expense*).
   - Kategori Default Sistem vs Kategori Kustom per User.
3. **Transaction Management (`/api/v1/transactions`)**
   - CRUD Pemasukan & Pengeluaran.
   - Filter Rentang Tanggal, Filter Tipe, Filter Kategori, Search Text, Sorting, & Pagination.
4. **Budget Management (`/api/v1/budgets`)**
   - Pengaturan batas anggaran bulanan per kategori.
   - Kalkulasi otomatis pengeluaran (`spent`), sisa (`remaining`), persentase penggunaan, & status peringatan (`NORMAL`, `WARNING`, `EXCEEDED`).
   - Mencegah duplikasi alokasi anggaran pada bulan & tahun yang sama.
5. **Financial Goals & Setoran Dana (`/api/v1/financial-goals`)**
   - Pengelolaan target tabungan (`target_amount`, `current_amount`, `target_date`, `status`).
   - Endpoint **Setor Dana (`POST /api/v1/financial-goals/{id}/contributions`)** yang menghitung akumulasi setoran dan otomatis meng-update status ke `completed` saat target tercapai.
6. **Recurring Transactions & Scheduler (`/api/v1/recurring-transactions`)**
   - Jadwal transaksi berulang (harian, mingguan, bulanan, tahunan).
   - Artisan Command (`php artisan transactions:process-recurring`) untuk pemrosesan otomatis.
7. **Dashboard Analytics & Charts (`/api/v1/dashboard`)**
   - **Summary Metrik**: Total Balance, Pemasukan/Pengeluaran Bulanan, Tabungan, Ringkasan Budget, & 5 Transaksi Terbaru.
   - **Charts**: Grafik Tren 6 Bulan (Income vs Expense) & Grafik Lingkaran Distribusi Pengeluaran per Kategori.
8. **Reports Analytics (`/api/v1/reports`)**
   - Laporan ringkasan per periode (`daily`, `weekly`, `monthly`, `yearly`, `custom`) dengan breakdown kategori & persentase.
9. **Export Feature & Queue (`/api/v1/exports`)**
   - Request ekspor laporan latar belakang (*Asynchronous Background Job Queue*) untuk format **PDF**, **CSV**, dan **XLSX**.
   - Endpoint cek status pengerjaan job & **Download File Hasil Ekspor**.

---

## 🛠️ Requirements & Prasyarat Sistem

* **PHP**: `>= 8.3`
* **Composer**: `>= 2.0`
* **Database**: PostgreSQL / SQLite / MySQL
* **PHP Extensions**: `pdo`, `pdo_pgsql` / `pdo_mysql`, `mbstring`, `openssl`, `json`

---

## ⚙️ Cara Instalasi & Setup Lokal

1. **Clone Repositori**:
   ```bash
   git clone https://github.com/Iqbaltawakal05/bukuang.git
   cd bukuang
   ```

2. **Install Dependensi Composer**:
   ```bash
   composer install
   ```

3. **Konfigurasi Environment**:
   Duplikat berkas `.env.example` menjadi `.env`:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Atur konfigurasi database pada `.env`:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=bukuang
   DB_USERNAME=postgres
   DB_PASSWORD=yourpassword
   ```

4. **Jalankan Database Migration & Seeder**:
   ```bash
   php artisan migrate --seed
   ```

5. **Jalankan Server Development**:
   ```bash
   php artisan serve
   ```
   Layanan API akan berjalan di `http://127.0.0.1:8000`.

---

## 🧪 Jalankan Testing Suites

Seluruh endpoint dan logika bisnis didukung oleh pengujian otomatis (*Feature Tests*):

```bash
php artisan test
```

**Hasil Pengujian**:
- **62 Test Suite Passed** (100% Pass Rate)
- **231 Assertions**

---

## 📑 Spesifikasi Endpoint API (Akses Singkat)

Semua endpoint terlindung oleh Bearer Token kecuali `auth/register` dan `auth/login`.

| Method | Endpoint | Deskripsi |
|---|---|---|
| `POST` | `/api/v1/auth/register` | Register pengguna baru |
| `POST` | `/api/v1/auth/login` | Login & Generate Token Sanctum |
| `POST` | `/api/v1/auth/logout` | Revoke Token Active |
| `GET` | `/api/v1/profile` | Ambil Data Profil User |
| `PUT` | `/api/v1/profile` | Update Informasi Profil |
| `PUT` | `/api/v1/profile/password` | Update Password |
| `GET\|POST` | `/api/v1/categories` | List & Tambah Kategori |
| `PUT\|DELETE`| `/api/v1/categories/{id}` | Edit & Soft Delete Kategori Custom |
| `GET\|POST` | `/api/v1/transactions` | List (Filter/Search/Sort) & Tambah Transaksi |
| `PUT\|DELETE`| `/api/v1/transactions/{id}` | Edit & Hapus Transaksi |
| `GET\|POST` | `/api/v1/budgets` | List Budget Bulanan & Set Budget Kategori |
| `GET\|POST` | `/api/v1/financial-goals` | List & Buat Target Tabungan |
| `POST` | `/api/v1/financial-goals/{id}/contributions` | Tambah Setoran Dana ke Target Tabungan |
| `GET\|POST` | `/api/v1/recurring-transactions` | List & Tambah Jadwal Transaksi Berulang |
| `GET` | `/api/v1/dashboard/summary` | Metrik Summary Dashboard |
| `GET` | `/api/v1/dashboard/charts` | Data Grafik Tren 6 Bulan & Pie Chart Kategori |
| `GET` | `/api/v1/reports/summary` | Laporan Keuangan Periode (Daily/Weekly/Monthly/Yearly/Custom) |
| `POST` | `/api/v1/exports` | Request Job Ekspor Laporan (PDF, CSV, XLSX) |
| `GET` | `/api/v1/exports/{id}/download` | Unduh File Hasil Ekspor Laporan |

---

## 📜 Lisensi
Repositori ini dirilis di bawah lisensi [MIT License](LICENSE).
