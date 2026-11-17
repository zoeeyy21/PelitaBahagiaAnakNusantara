# Panduan Integrasi API CMS ke Frontend (Pelita Bahagia)

Arsitektur integrasi antara **Frontend (`cci-clean`)** dan **CMS (`cms-pelita-bahagia-main`)** sudah terpasang dan siap digunakan secara penuh.

Integrasi ini dirancang menggunakan konsep **Smart Hybrid Fallback**:
1. **CMS Online**: Frontend mengambil data langsung secara dinamis dari database CMS melalui REST API.
2. **CMS Offline / Pemeliharaan**: Frontend secara otomatis beralih ke data lokal (`resources/data/`) tanpa error/crash, sehingga pengunjung tetap bisa membuka website tanpa gangguan.

---

## 1. Arsitektur & Titik Integrasi

### A. Sisi CMS (`cms-pelita-bahagia-main`)
- **Controller API**: `app/Http/Controllers/Api/PublicApiController.php`
- **Rute API**: `routes/api.php`
- **Daftar Endpoint Publik**:
  | Method | Endpoint | Keterangan |
  | :--- | :--- | :--- |
  | `GET` | `/api/site` | Identitas, kontak yayasan, dan statistik capaian |
  | `GET` | `/api/programs` | Daftar semua program kerja yayasan |
  | `GET` | `/api/programs/{slug}` | Detail satu program kerja |
  | `GET` | `/api/activities` | Daftar laporan kegiatan / berita terlaksana |
  | `GET` | `/api/activities/{slug}` | Detail laporan kegiatan & galeri dokumentasi |
  | `GET` | `/api/campaigns` | Daftar kampanye galang dana donasi |
  | `GET` | `/api/campaigns/{slug}` | Detail kampanye donasi, paket donasi, & cerita |
  | `GET` | `/api/faqs` | Pertanyaan yang sering diajukan (FAQ) |
  | `POST` | `/api/donations` | Submit donasi (otomatis simpan ke DB & generate Midtrans Snap) |
  | `GET` | `/api/donations/{code}/status` | Cek status pembayaran donasi (paid, pending, expired) |

---

### B. Sisi Frontend (`cci-clean`)
- **Service Client**: `app/Services/CmsClient.php`
  - Mengelola HTTP Client dengan timeout aman (2.5 detik) & cache otomatis.
  - Menghubungkan submit donasi ke endpoint `/api/donations` CMS.
  - Menyediakan helper URL media/gambar `CmsClient::assetUrl($path)` / fungsi helper `cms_asset($path)`.
- **Rute Utama**: `routes/web.php`
  - `cciSite()`: Memuat profil & statistik dari `/api/site`.
  - `cciPrograms()`: Memuat daftar program dari `/api/programs`.
  - `cciActivities()`: Memuat daftar kegiatan dari `/api/activities`.
  - `cciCampaigns()`: Memuat kampanye galang dana dari `/api/campaigns`.
  - `POST /donasi`: Meneruskan data donatur ke CMS dan menerima Snap Token / invoice pembayaran.

---

## 2. Konfigurasi Lingkungan (`.env`)

Di file `.env` frontend (`cci-clean`), telah ditambahkan:
```env
# URL REST API CMS (Gunakan port 8000 saat CMS dijalankan lokal)
CMS_API_URL=http://127.0.0.1:8000/api

# URL penyimpanan gambar publik dari CMS
CMS_ASSET_URL=http://127.0.0.1:8000/storage
```

---

## 3. Cara Menjalankan Keduanya di Lokal

Buka **dua jendela terminal** terpisah:

### Terminal 1 — Menjalankan CMS Backend
```bash
cd c:\CCI-RND\cms-pelita-bahagia-main
php artisan serve --port=8000
```
- Akses Panel Admin CMS: [http://127.0.0.1:8000/login](http://127.0.0.1:8000/login)
- Akun Default Seeder:
  - **Email**: `admin@pelitabahagia.test`
  - **Password**: `password`

### Terminal 2 — Menjalankan Frontend Publik
```bash
cd c:\CCI-RND\cci-clean
php artisan serve --port=8080
```
- Akses Website Publik: [http://127.0.0.1:8080](http://127.0.0.1:8080)

---

## 4. Pengujian Integrasi yang Sudah Terverifikasi

1. **Pengambilan Data Realtime**:
   Program yang diinput oleh Admin di CMS langsung muncul di frontend pada halaman `/program`, `/kegiatan`, dan `/donasi`.
2. **Transaksi Donasi**:
   Ketika donatur mengisi formulir donasi di halaman donasi frontend, data langsung terkirim via API ke CMS dan tersimpan dengan kode referensi format `YPB-YYYYMMDD-XXXXXX`.
3. **Penyelamat Downtime (Zero Downtime Fallback)**:
   Jika server CMS dimatikan atau sedang update, frontend tetap berjalan normal dengan menyajikan fallback data lokal secara otomatis tanpa memunculkan error 500 ke pengunjung.
