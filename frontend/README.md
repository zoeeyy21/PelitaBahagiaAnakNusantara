# 🌟 Yayasan Pelita Bahagia Anak Nusantara — Frontend Platform

Website resmi publik **Yayasan Pelita Bahagia Anak Nusantara** (`FE-PelitaBahagia`). Dibangun menggunakan **Laravel 12**, **Tailwind CSS**, dan diintegrasikan secara dinamis dengan **CMS Pelita Bahagia** (`CMS-PelitaBahagia`) melalui arsitektur REST API dengan sistem *Smart Hybrid Fallback*.

---

## 📑 Daftar Isi
1. [Arsitektur Sistem](#1-arsitektur-sistem)
2. [Prasyarat Sistem (Prerequisites)](#2-prasyarat-sistem-prerequisites)
3. [Panduan Instalasi & Pemasangan](#3-panduan-instalasi--pemasangan)
   - [A. Setup CMS Backend](#a-setup-cms-backend-cms-pelitabahagia)
   - [B. Setup Frontend](#b-setup-frontend-fe-pelitabahagia)
4. [Menjalankan Aplikasi di Lokal (Development)](#4-menjalankan-aplikasi-di-lokal-development)
5. [Detail Integrasi Frontend & CMS](#5-detail-integrasi-frontend--cms)
   - [A. Endpoint REST API](#a-endpoint-rest-api)
   - [B. Penanganan Gambar & Media (`cms_asset`)](#b-penanganan-gambar--media-cms_asset)
   - [C. Alur Transaksi Donasi QRIS](#c-alur-transaksi-donasi-qris)
   - [D. Smart Fallback Safety Net (Anti-Crash)](#d-smart-fallback-safety-net-anti-crash)
6. [Panduan Deploy ke Server Online (Production)](#6-panduan-deploy-ke-server-online-production)
7. [Troubleshooting & FAQ](#7-troubleshooting--faq)

---

## 1. Arsitektur Sistem

Platform ini menggunakan pola arsitektur **Decoupled Monolith / Headless CMS Lite**:
* **Frontend (`FE-PelitaBahagia`)**: Berjalan mandiri, ringan, dan cepat. Bertugas menampilkan antarmuka pengunjung, katalog program, kegiatan berita, dan formulir donasi QRIS.
* **Backend CMS (`CMS-PelitaBahagia`)**: Berjalan di service terpisah. Menyediakan panel admin berbasis Livewire & Volt untuk mengelola program, berita kegiatan, laporan donasi, kontak yayasan, serta bagan struktur organisasi.
* **Komunikasi Data**: Terhubung via REST API (`/api/*`) dengan format JSON standar.

```
┌──────────────────────────────────────┐          REST API          ┌──────────────────────────────────────┐
│        FRONTEND (Pengunjung)         │ ◄────────────────────────► │         CMS BACKEND (Admin)          │
│   (Port 8001 / Domain Utama)        │        (Port 8000)         │    (Port 8000 / Subdomain Admin)     │
│                                      │                            │                                      │
│  - Beranda & Profil Yayasan          │    GET  /api/site          │  - Panel Admin Livewire / Volt       │
│  - Program Kerja & Detail Program    │    GET  /api/programs      │  - Manajemen Program & Donasi        │
│  - Kegiatan Terlaksana & Galeri      │    GET  /api/activities    │  - Manajemen Berita & Kegiatan       │
│  - Galang Dana & Form Donasi QRIS    │    GET  /api/campaigns     │  - Upload Bagan Struktur Organisasi  │
│  - Smart Fallback: resources/data/   │    POST /api/donations     │  - Database MySQL (pelitabahagia)    │
└──────────────────────────────────────┘                            └──────────────────────────────────────┘
```

---

## 2. Prasyarat Sistem (Prerequisites)

Pastikan lingkungan server / komputer pengembang telah terpasang:
* **PHP:** `>= 8.2` (dengan ekstensi `pdo_mysql`, `curl`, `mbstring`, `openssl`, `fileinfo`)
* **Composer:** `>= 2.x`
* **MySQL Database Server:** (bisa menggunakan bawaan XAMPP / MariaDB)
* **Node.js & NPM:** Node.js `>= 18.x`
* **Git:** versi terbaru

---

## 3. Panduan Instalasi & Pemasangan

Clone kedua repositori ke dalam satu direktori induk (misal: `C:\CCI-RND\`):
* CMS: `https://github.com/zoeeyy21/CMS-PelitaBahagia.git`
* Frontend: `https://github.com/zoeeyy21/FE-PelitaBahagia.git`

```bash
git clone https://github.com/zoeeyy21/CMS-PelitaBahagia.git cms-pelita-bahagia
git clone https://github.com/zoeeyy21/FE-PelitaBahagia.git fe-pelita-bahagia
```

---

### A. Setup CMS Backend (`CMS-PelitaBahagia`)

1. **Masuk ke folder CMS & Pasang Dependensi Composer:**
   ```bash
   cd cms-pelita-bahagia
   composer install
   ```

2. **Buat Database MySQL:**
   * Buka **phpMyAdmin** atau terminal MySQL:
   ```sql
   CREATE DATABASE pelitabahagia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Salin & Sesuaikan File Konfigurasi `.env`:**
   ```bash
   copy .env.example .env
   php artisan key:generate
   ```
   Pastikan konfigurasi database di file `.env` CMS:
   ```env
   APP_NAME="CMS Pelita Bahagia"
   APP_ENV=local
   APP_URL=http://127.0.0.1:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=pelitabahagia
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. **Jalankan Migrasi & Database Seeder:**
   ```bash
   php artisan migrate --seed
   ```
   *(Perintah ini akan membuat tabel dan menyuntikkan user admin default)*

5. **Buat Symlink Storage (PENTING untuk Foto Media):**
   ```bash
   php artisan storage:link
   ```

6. **Kompilasi Aset Frontend Admin:**
   ```bash
   npm install
   npm run build
   ```

---

### B. Setup Frontend (`FE-PelitaBahagia`)

1. **Masuk ke folder Frontend & Pasang Dependensi:**
   ```bash
   cd ../fe-pelita-bahagia
   composer install
   npm install
   ```

2. **Salin & Konfigurasi File `.env`:**
   ```bash
   copy .env.example .env
   php artisan key:generate
   ```

3. **Atur Koneksi Integrasi ke CMS di `.env` Frontend:**
   Tambahkan baris berikut di bagian bawah `.env`:
   ```env
   # URL endpoint API CMS (Port 8000 lokal)
   CMS_API_URL=http://127.0.0.1:8000/api

   # URL penyimpanan aset gambar CMS
   CMS_ASSET_URL=http://127.0.0.1:8000/storage
   ```

4. **Kompilasi Asset Tailwind CSS & JS:**
   ```bash
   npm run build
   ```

---

## 4. Menjalankan Aplikasi di Lokal (Development)

Untuk menjalankan kedua aplikasi secara berdampingan di komputer lokal, buka **dua jendela terminal terpisah**:

### Terminal 1: Jalankan CMS Backend (Port 8000)
```bash
cd cms-pelita-bahagia
php artisan serve --port=8000
```
* **Akses Panel Admin:** [http://127.0.0.1:8000/login](http://127.0.0.1:8000/login)
* **Kredensial Default:**
  * **Email:** `admin@pelitabahagia.test`
  * **Password:** `password`

### Terminal 2: Jalankan Frontend Publik (Port 8001)
```bash
cd fe-pelita-bahagia
php artisan serve --port=8001
```
* **Akses Website Publik:** [http://127.0.0.1:8001](http://127.0.0.1:8001)

---

## 5. Detail Integrasi Frontend & CMS

### A. Endpoint REST API

Frontend berkomunikasi dengan CMS melalui controller `app/Services/CmsClient.php` yang memanggil rute-rute publik berikut di CMS:

| Method | Endpoint | Fungsi |
| :--- | :--- | :--- |
| `GET` | `/api/site` | Mengambil identitas yayasan, kontak, medsos, statistik capaian, dan bagan struktur organisasi |
| `GET` | `/api/faqs` | Mengambil daftar pertanyaan umum (FAQ) untuk halaman kontak |
| `GET` | `/api/programs` | Mengambil seluruh katalog program bantuan |
| `GET` | `/api/programs/{slug}` | Mengambil detail spesifik satu program berserta galeri fotonya |
| `GET` | `/api/activities` | Mengambil daftar catatan kegiatan/berita relawan |
| `GET` | `/api/activities/{slug}` | Mengambil detail artikel kegiatan, dokumentasi, dan tanggal |
| `GET` | `/api/campaigns` | Mengambil daftar program penggalangan dana aktif |
| `GET` | `/api/campaigns/{slug}` | Mengambil detail kampanye donasi, target nominal, perolehan, donatur, dan paket donasi |
| `POST` | `/api/donations` | Mengirim data transaksi donasi baru dari formulir website |

---

### B. Penanganan Gambar & Media (`cms_asset`)

Karena file fisik foto diunggah dari CMS (`storage/app/public/...`), Frontend menggunakan helper global `cms_asset($path)` yang terdefinisi di `app/helpers.php`:
* **Di Lokal:** Otomatis menghasilkan `http://127.0.0.1:8000/storage/{path}`.
* **Di Production:** Cukup ubah `CMS_ASSET_URL` di file `.env` frontend menjadi domain CMS online Anda (misal `https://admin.pelitabahagia.org/storage`), seluruh foto program, kegiatan, dan bagan struktur organisasi akan langsung terhubung tanpa mengubah kode Blade!

Contoh pemanggilan di Blade:
```blade
<img src="{{ cms_asset($program->image) }}" alt="{{ $program->name }}" />
```

---

### C. Alur Transaksi Donasi QRIS

Alur donasi dirancang sederhana dan ramah pengguna tanpa dependensi gateway berbayar:
1. Donatur memilih program donasi di halaman `/donasi/{slug}`.
2. Donatur memilih paket sedekah atau memasukkan nominal bebas, lalu mengisi nama dan kontak.
3. Saat menekan tombol **"Lanjutkan Pembayaran"**, muncul **Modal QRIS Pembayaran**.
4. Donatur memindai kode QRIS dan menyelesaikan transfer lewat aplikasi m-Banking/e-Wallet.
5. Donatur menekan tombol **"Saya Sudah Membayar"**:
   * Data dikirim via `POST /donasi` ke CMS endpoint `/api/donations`.
   * CMS langsung mencatat status donasi sebagai **`paid` (Lunas)** dan mencatat `paid_at = now()`.
   * Di dashboard admin CMS, donasi berstatus hijau **Lunas**.
   * Di website frontend, angka **Donatur** dan **Dana Terkumpul** program tersebut otomatis langsung bertambah.

---

### D. Smart Fallback Safety Net (Anti-Crash)

Frontend dilengkapi sistem perlindungan `CmsClient::get($endpoint, $cacheKey, $fallbackCallable)`:
* **Jika CMS Normal:** Data diambil realtime dari CMS dengan caching efisien (5 detik di local, 2 menit di production).
* **Jika Server CMS Down / Maintenance:** Frontend otomatis beralih membaca file lokal di `resources/data/*.php` (`site.php`, `faqs.php`, dll).
* **Hasil:** Website frontend **tidak akan pernah mengalami Error 500** di hadapan pengunjung meskipun server backend CMS sedang dimatikan atau diperbarui.

---

## 6. Panduan Deploy ke Server Online (Production)

Saat memasang platform ini di hosting VPS (cPanel, Nginx, CyberPanel, dll):

### 1. Rekomendasi Struktur Domain:
* **Website Publik:** `https://pelitabahagia.org` (Arahkan document root ke `fe-pelita-bahagia/public`)
* **CMS Admin:** `https://admin.pelitabahagia.org` (Arahkan document root ke `cms-pelita-bahagia/public`)

### 2. Checklist File `.env` Production:

**Di Server CMS (`cms-pelita-bahagia/.env`):**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://admin.pelitabahagia.org

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=nama_db_hosting
DB_USERNAME=user_db_hosting
DB_PASSWORD=password_db_hosting
```

**Di Server Frontend (`fe-pelita-bahagia/.env`):**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pelitabahagia.org

# Arahkan ke domain CMS online:
CMS_API_URL=https://admin.pelitabahagia.org/api
CMS_ASSET_URL=https://admin.pelitabahagia.org/storage
```

### 3. Perintah Pasca-Deploy di Server:
Di folder **CMS**:
```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Di folder **Frontend**:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 7. Troubleshooting & FAQ

* **T: Foto program atau struktur organisasi tidak muncul (Error 404 pada gambar)?**  
  **J:** Pastikan perintah `php artisan storage:link` sudah berhasil dijalankan di folder CMS, dan nilai `CMS_ASSET_URL` di file `.env` frontend sudah sesuai dengan URL/port CMS yang berjalan.

* **T: Data yang baru saya edit di CMS belum langsung muncul di Frontend?**  
  **J:** Frontend memiliki cache performa. Di mode lokal cache berdurasi 5 detik. Di server production berdurasi 2 menit. Anda bisa menjalankan `php artisan cache:clear` di folder frontend jika ingin memaksa pembaruan data secara instan.

* **T: Di mana tempat upload gambar Struktur Organisasi?**  
  **J:** Masuk ke panel Admin CMS di menu **"Profil & Kontak"** (`/admin/contact`), scroll ke bawah ke bagian **"Bagan Struktur Organisasi"**, upload file gambar bagan, lalu simpan.

---

## 📄 Lisensi
Hak Cipta © 2026 Yayasan Pelita Bahagia Anak Nusantara. Seluruh hak cipta dilindungi undang-undang.
