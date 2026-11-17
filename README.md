# 🌟 Yayasan Pelita Bahagia Anak Nusantara — Monorepo

Repositori resmi **Yayasan Pelita Bahagia Anak Nusantara** yang menggabungkan seluruh ekosistem aplikasi ke dalam satu repositori (*Monorepo*):
1. **`cms/`**: Backend Content Management System (CMS) & REST API berbasis **Laravel 12, Livewire & Volt**.
2. **`frontend/`**: Antarmuka publik website yayasan berbasis **Laravel 12 & Tailwind CSS** yang mengonsumsi data via REST API dari CMS dengan sistem proteksi *Smart Fallback*.

---

## 📁 Struktur Direktori Monorepo

```text
PelitaBahagiaAnakNusantara/
├── cms/                     # Backend CMS & REST API (Port 8000)
│   ├── app/                 # Controller, Models, Livewire/Volt Components
│   ├── routes/api.php       # Endpoint REST API publik untuk Frontend
│   ├── routes/web.php       # Dashboard Panel Admin & Auth
│   └── database/migrations/ # Skema database MySQL
├── frontend/                # Frontend Website Publik (Port 8001)
│   ├── app/Services/        # ApiClientService & CmsDataService
│   ├── resources/views/     # Halaman publik (Beranda, Program, Kegiatan, Donasi)
│   └── resources/data/      # JSON Fallback (Safety Net Anti-Crash)
├── .gitignore               # Konfigurasi proteksi file sensitif monorepo
└── README.md                # Panduan instalasi dan operasional terpadu
```

---

## 🏛️ Arsitektur Integrasi

Sistem ini menggunakan arsitektur **Decoupled Architecture / Headless CMS Lite**:
* **Frontend** berjalan di port `8001` (atau domain utama saat production).
* **CMS Backend** berjalan di port `8000` (atau subdomain admin saat production).
* **Komunikasi Data**: Terhubung via REST API (`/api/*`) berformat JSON standar dengan fallback file lokal di frontend jika CMS sedang offline.

```text
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

## 💻 Prasyarat Sistem (Prerequisites)

Sebelum memulai instalasi, pastikan sistem komputer/server Anda memenuhi syarat berikut:
* **PHP:** `>= 8.2` (dengan ekstensi `pdo_mysql`, `curl`, `mbstring`, `openssl`, `fileinfo`)
* **Composer:** `>= 2.x`
* **MySQL Database Server:** (bisa melalui XAMPP, Laragon, MariaDB, dsb.)
* **Node.js & NPM:** Node.js `>= 18.x`
* **Git:** Versi terbaru

---

## 🚀 Panduan Instalasi Cepat

### 1. Clone Repositori
```bash
git clone https://github.com/zoeeyy21/PelitaBahagiaAnakNusantara.git
cd PelitaBahagiaAnakNusantara
```

---

### 2. Setup CMS Backend (`cms/`)

1. **Masuk ke folder CMS dan pasang dependensi:**
   ```bash
   cd cms
   composer install
   npm install
   ```

2. **Buat Database MySQL:**
   Buka phpMyAdmin atau MySQL CLI, buat database baru:
   ```sql
   CREATE DATABASE pelitabahagia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Konfigurasi Environment `.env`:**
   Salin contoh file `.env`:
   ```bash
   copy .env.example .env
   php artisan key:generate
   ```
   Buka file `.env` di folder `cms/` dan sesuaikan koneksi database:
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

4. **Jalankan Migrasi & Seeder:**
   ```bash
   php artisan migrate --seed
   ```

5. **Buat Symlink Storage (Penting untuk upload gambar & QRIS):**
   ```bash
   php artisan storage:link
   ```

6. **Build Asset Vite:**
   ```bash
   npm run build
   ```

---

### 3. Setup Frontend (`frontend/`)

1. **Pindah ke folder Frontend dan pasang dependensi:**
   ```bash
   cd ../frontend
   composer install
   npm install
   ```

2. **Konfigurasi Environment `.env`:**
   Salin file `.env`:
   ```bash
   copy .env.example .env
   php artisan key:generate
   ```

3. **Pastikan URL CMS terhubung dengan benar:**
   Buka file `.env` di folder `frontend/` dan pastikan konfigurasi berikut:
   ```env
   APP_NAME="Yayasan Pelita Bahagia Anak Nusantara"
   APP_ENV=local
   APP_URL=http://127.0.0.1:8001

   # URL CMS Backend
   CMS_BASE_URL=http://127.0.0.1:8000
   CMS_API_URL=http://127.0.0.1:8000/api
   CMS_API_KEY=
   ```

4. **Build Asset Vite:**
   ```bash
   npm run build
   ```

---

## 🏃 Menjalankan Aplikasi di Lokal (Development)

Untuk menjalankan kedua aplikasi secara bersamaan, buka **2 terminal terpisah**:

### Terminal 1: CMS Backend (Port 8000)
```bash
cd PelitaBahagiaAnakNusantara/cms
php artisan serve --port=8000
```
> CMS berjalan di: **[http://127.0.0.1:8000](http://127.0.0.1:8000)**  
> Halaman Login Admin: **[http://127.0.0.1:8000/login](http://127.0.0.1:8000/login)**  
> *Default Akun Seeder: Email: `admin@pelitabahagia.org` | Password: `password`*

### Terminal 2: Frontend Website (Port 8001)
```bash
cd PelitaBahagiaAnakNusantara/frontend
php artisan serve --port=8001
```
> Website Frontend berjalan di: **[http://127.0.0.1:8001](http://127.0.0.1:8001)**

---

## 🔌 Detail Integrasi & Fitur Unggulan

### 1. REST API Endpoint
Frontend mengonsumsi endpoint dari CMS:
- `GET /api/site`: Informasi umum yayasan, kontak, nomor rekening, QRIS yayasan, bagan organisasi, dan FAQ.
- `GET /api/programs`: Daftar program kerja dan program galang dana aktif.
- `GET /api/programs/{id}`: Detail program kerja beserta dokumentasi.
- `GET /api/activities`: Daftar berita kegiatan yang sudah terlaksana beserta dokumentasi foto.
- `GET /api/campaigns`: Data kampanye donasi yang sedang berjalan.
- `POST /api/donations`: Menerima pengajuan donasi dari pengunjung dan bukti transfer.

### 2. Smart Hybrid Fallback (Anti-Crash)
Frontend dilengkapi mekanisme `CmsDataService` yang bertindak sebagai *safety net*:
1. Mencoba mengambil data *fresh* dari CMS via API.
2. Jika server CMS sedang mati, maintenance, atau koneksi timeout, sistem **secara otomatis beralih menggunakan data fallback lokal** di folder `resources/data/`.
3. Pengunjung website tidak akan pernah melihat error 500 (*blank error screen*).

### 3. Smart Asset URL (`cms_asset()`)
Frontend memiliki helper pintar `cms_asset($path)` di `app/helpers.php`:
* Mengubah path gambar relatif dari CMS (seperti `storage/photos/...` atau `uploads/...`) menjadi URL absolut yang mengarah ke domain CMS aktif.
* Menangani gambar SVG, URL eksternal (CDN/Cloudinary), maupun path lokal secara transparan.

---

## 🌐 Panduan Deploy ke Server Production

Dalam arsitektur production (VPS / Cloud), disarankan membagi domain menjadi:
* **Domain Utama (`yayasanpelitabahagia.org`)**: Diarahkan ke folder `PelitaBahagiaAnakNusantara/frontend/public`.
* **Subdomain Admin (`admin.yayasanpelitabahagia.org`)**: Diarahkan ke folder `PelitaBahagiaAnakNusantara/cms/public`.

### Contoh Konfigurasi Nginx:

#### 1. CMS Backend (`admin.yayasanpelitabahagia.org`)
```nginx
server {
    listen 80;
    server_name admin.yayasanpelitabahagia.org;
    root /var/www/PelitaBahagiaAnakNusantara/cms/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }
}
```

#### 2. Frontend Website (`yayasanpelitabahagia.org`)
```nginx
server {
    listen 80;
    server_name yayasanpelitabahagia.org www.yayasanpelitabahagia.org;
    root /var/www/PelitaBahagiaAnakNusantara/frontend/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }
}
```

#### Konfigurasi `.env` Frontend di Production:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yayasanpelitabahagia.org

CMS_BASE_URL=https://admin.yayasanpelitabahagia.org
CMS_API_URL=https://admin.yayasanpelitabahagia.org/api
```

---

## 📄 Lisensi & Kontributor

Hak Cipta © 2026 **Yayasan Pelita Bahagia Anak Nusantara**.  
Dikembangkan untuk mendukung kegiatan kemanusiaan, sosial, dan pendidikan anak-anak nusantara.
