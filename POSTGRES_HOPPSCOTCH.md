# Dokumentasi PostgreSQL + Hoppscotch

Dokumen ini menjalankan API QR Multi-Link secara berurutan menggunakan PostgreSQL dan Hoppscotch.

## 1. Prasyarat

Pastikan tersedia:

- PHP 8.3 atau lebih baru.
- Composer.
- PostgreSQL 15 atau lebih baru.
- Node.js dan npm.
- Project Laravel pada folder backend.

Versi project saat ini menggunakan Laravel `^13.17`, Sanctum `^4.0`, dan PostgreSQL melalui konfigurasi `.env`.

## 2. Buat database PostgreSQL

Buka **SQL Shell (psql)** atau terminal PostgreSQL sebagai user administrator.

```sql
CREATE USER qr_app WITH PASSWORD 'ganti_password_kuat';
CREATE DATABASE qr_multilink OWNER qr_app;
```

Masuk ke database:

```sql
\c qr_multilink
```

Verifikasi koneksi:

```sql
SELECT current_database(), current_user, version();
```

Jika user atau database sudah pernah dibuat, gunakan perintah pengecekan berikut sebelum membuat ulang:

```sql
SELECT usename FROM pg_user WHERE usename = 'qr_app';
SELECT datname FROM pg_database WHERE datname = 'qr_multilink';
```

## 3. Atur file `.env`

Salin `.env.example` menjadi `.env`, lalu sesuaikan bagian PostgreSQL:

```dotenv
APP_NAME="QR Multi-Link"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=qr_multilink
DB_USERNAME=qr_app
DB_PASSWORD=ganti_password_kuat

SESSION_DRIVER=database
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false
SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:8000

PUBLIC_BASE_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173
QR_MAX_LINKS=20
QR_EVENT_RETENTION_DAYS=90
QR_ANALYTICS_HASH_KEY=ganti_dengan_secret_acak_panjang
```

Generate application key:

```bash
php artisan key:generate
```

> Gunakan hostname yang konsisten. Untuk contoh ini semua request Hoppscotch diarahkan ke `http://localhost:8000`.

## 4. Jalankan migration

Bersihkan cache konfigurasi dan jalankan migration:

```bash
php artisan config:clear
php artisan migrate
```

Cek tabel PostgreSQL:

```sql
\dt
\d qr_pages
\d qr_links
\d qr_events
```

Tabel utama yang harus muncul:

- `users`
- `qr_pages`
- `qr_links`
- `qr_events`
- `sessions`
- `personal_access_tokens`

## 5. Buat admin

Jika command admin tersedia di project:

```bash
php artisan qr:admin admin@example.com --name="Admin QR" --password="password"
```

Jika command belum tersedia, buat user melalui seeder atau mekanisme admin internal project. Jangan menyediakan registrasi publik untuk aplikasi ini.

## 6. Jalankan Laravel

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Tes endpoint dasar:

```bash
curl.exe -i http://localhost:8000/up
```

Collection kompatibel Hoppscotch tersedia di project:

[`hoppscotch-qr-multilink.postman_collection.json`](file:///D:/project%20kantor/qr%20generator/be/qr-multilink-api/hoppscotch-qr-multilink.postman_collection.json)

> Gunakan file **Postman Collection v2.1** ini, bukan file `hoppscotch-qr-multilink.json` sebelumnya. Importer Hoppscotch mengenali skema Postman v2.1 secara langsung, sehingga nama collection dan semua request tidak menjadi **Untitled**.

Langkah import:

1. Hapus collection/request lama yang berjudul **Untitled**.
2. Buka Hoppscotch dan klik menu **Import**.
3. Pilih file `hoppscotch-qr-multilink.postman_collection.json`.
4. Pilih atau biarkan Hoppscotch mendeteksi format **Postman Collection v2.1**.
5. Klik **Import**.
6. Buka menu **Collections**. Collection akan bernama **QR Multi-Link API - Full Collection**.
7. Isi atau aktifkan variable `base_url` dengan `http://localhost:8000` bila belum terisi.

File berisi 4 folder dan 18 request lengkap: method, URL, headers, body JSON, serta script untuk mengambil ID hasil create. Pada Hoppscotch, bila script Postman tidak dipindahkan otomatis, nilai `qr_page_id`, `qr_code`, `link_id`, `link_token`, dan `second_link_id` dapat diambil dari response request create.

### Wajib: setup CSRF sebelum login

Collection sudah memiliki header `X-XSRF-TOKEN: {{xsrf_token}}` pada seluruh request yang mengubah data. Lakukan ini sekali setiap sesi baru:

1. Jalankan **01. Get CSRF Cookie**.
2. Pastikan response mengirim cookie `XSRF-TOKEN` dan cookie session Laravel.
3. Buka menu **Variables** pada request atau collection, lalu isi `xsrf_token` dengan nilai cookie `XSRF-TOKEN` yang sudah di-*URL decode*.
   - Contoh: bila nilai cookie adalah `eyJpdiU3...%3D%3D`, masukkan nilai setelah tanda `%` di-decode menjadi karakter aslinya.
4. Jangan menghapus cookie session setelah menjalankan request CSRF.
5. Jalankan **02. Login Admin** kembali.

Laravel memvalidasi dua hal sekaligus: cookie session dan header `X-XSRF-TOKEN`. Error `CSRF token mismatch` berarti salah satu dari keduanya belum terkirim, token belum di-*decode*, atau token berasal dari sesi lama. Jika masih muncul, hapus cookie untuk `localhost`, jalankan ulang **Get CSRF Cookie**, lalu perbarui `xsrf_token`.

Untuk Sanctum cookie, jalankan request dari hostname yang sama dan pastikan Hoppscotch mengizinkan cookie. Jika Hoppscotch versi web tidak menyimpan cookie `localhost`, gunakan Hoppscotch Desktop atau extension desktop agent; browser biasanya membatasi cookie lintas situs dari `https://hoppscotch.io` ke `http://localhost:8000`.

## 8. Urutan request di Hoppscotch

### Step 1 — Ambil CSRF cookie

`GET {{base_url}}/sanctum/csrf-cookie`

Jalankan terlebih dahulu dan jangan menghapus cookie yang diterima.

### Step 2 — Login

`POST {{base_url}}/api/v1/auth/login`

```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

Expected: `200` dan cookie session Laravel tersimpan.

### Step 3 — Cek user aktif

`GET {{base_url}}/api/v1/auth/me`

Expected: `200` dengan `id`, `name`, dan `email`.

### Step 4 — Buat QR dalam status draft

`POST {{base_url}}/api/v1/qr-pages`

```json
{
  "title": "Toko Kopi Nusantara",
  "description": "Semua link resmi toko"
}
```

Expected: `201`. Script response otomatis menyimpan `data.id` ke `qr_page_id` dan `data.code` ke `qr_code`.

### Step 5 — Tambahkan link WhatsApp

`POST {{base_url}}/api/v1/qr-pages/{{qr_page_id}}/links`

```json
{
  "label": "WhatsApp",
  "icon_key": "whatsapp",
  "destination_url": "https://wa.me/6281234567890",
  "is_active": true
}
```

Expected: `201`. Script response otomatis menyimpan `data.id` ke `link_id` dan `data.token` ke `link_token`.

### Step 6 — Tambahkan link website

Jalankan request **02. Add Website Link**. Script response otomatis menyimpan ID link kedua ke `second_link_id`.

### Step 7 — Urutkan link

`PUT {{base_url}}/api/v1/qr-pages/{{qr_page_id}}/links/reorder`

```json
{
  "ids": [
    "{{link_id}}",
    "{{second_link_id}}"
  ]
}
```

Array `ids` wajib memuat semua link aktif maupun nonaktif yang belum dihapus, masing-masing tepat satu kali.

### Step 8 — Publish QR

`PATCH {{base_url}}/api/v1/qr-pages/{{qr_page_id}}`

```json
{
  "status": "active"
}
```

Expected: `200`. QR tidak dapat active jika belum memiliki minimal satu link aktif.

### Step 8 — Ambil data publik

`GET {{base_url}}/api/v1/public/qr/{{qr_code}}`

Expected: `200`, hanya menampilkan link aktif dan `click_url`. `destination_url` tidak boleh muncul di response publik.

### Step 9 — Tes URL scan

`GET {{base_url}}/go/{{qr_code}}`

Expected: `302` menuju halaman frontend `/qr/{{qr_code}}`. Request ini menambah `total_scans` dan membuat row pada `qr_events`.

Jika Hoppscotch mengikuti redirect otomatis, matikan opsi **Follow redirects** untuk melihat status `302` dan header `Location`.

### Step 10 — Tes klik link

`GET {{base_url}}/r/{{link_token}}`

Expected: `302` menuju `destination_url` terbaru. Request ini menambah counter click dan membuat event `click`.

### Step 11 — Lihat statistik

`GET {{base_url}}/api/v1/qr-pages/{{qr_page_id}}/stats?from=2026-10-01&to=2026-10-27`

Expected: total scan, total click, series harian, dan top links.

### Step 12 — Ubah tujuan link

`PATCH {{base_url}}/api/v1/qr-pages/{{qr_page_id}}/links/{{link_id}}`

```json
{
  "destination_url": "https://wa.me/6289876543210"
}
```

QR tidak perlu dibuat ulang. URL `/r/{{link_token}}` tetap sama, tetapi redirect berikutnya menggunakan tujuan terbaru.

## 9. Verifikasi langsung dari PostgreSQL

Jalankan query berikut pada database `qr_multilink`:

```sql
SELECT id, code, title, status, total_scans, total_clicks
FROM qr_pages
ORDER BY created_at DESC;
```

```sql
SELECT id, qr_page_id, token, label, destination_url, is_active, total_clicks
FROM qr_links
ORDER BY created_at DESC;
```

```sql
SELECT id, qr_page_id, qr_link_id, type, visitor_key, created_at
FROM qr_events
ORDER BY created_at DESC
LIMIT 20;
```

Expected:

- Scan menghasilkan `type = 'scan'` dan `qr_link_id = NULL`.
- Click menghasilkan `type = 'click'` dan `qr_link_id` berisi ID link.
- `visitor_key` berupa hash, bukan IP mentah.

## 10. Contoh validasi gagal

### URL HTTP ditolak

```json
{
  "label": "Unsafe",
  "icon_key": "website",
  "destination_url": "http://example.com",
  "is_active": true
}
```

Expected: `422`.

### URL internal ditolak

```json
{
  "label": "Internal",
  "icon_key": "website",
  "destination_url": "https://localhost/admin",
  "is_active": true
}
```

Expected: `422`.

### Publish tanpa link aktif

Buat QR baru lalu langsung kirim `status=active`.

Expected: `422`.

### Akses QR milik user lain

Gunakan `qr_page_id` milik user lain.

Expected: `404`, bukan `403`, agar ID tidak mudah dienumerasi.

## 11. Troubleshooting PostgreSQL

### `SQLSTATE[08006]` atau connection refused

1. Pastikan service PostgreSQL berjalan.
2. Pastikan `DB_HOST`, `DB_PORT`, username, password benar.
3. Uji dari terminal:

```bash
psql -h 127.0.0.1 -U qr_app -d qr_multilink
```

### `database does not exist`

Buat database dengan user administrator:

```sql
CREATE DATABASE qr_multilink OWNER qr_app;
```

### `password authentication failed`

Reset password:

```sql
ALTER USER qr_app WITH PASSWORD 'password_baru';
```

Lalu samakan `DB_PASSWORD` pada `.env` dan jalankan:

```bash
php artisan config:clear
```

### Migration gagal karena tabel sudah ada

Untuk local development saja:

```bash
php artisan migrate:fresh
```

> Perintah ini menghapus seluruh tabel dan data. Jangan jalankan di production.

## 12. Logout

Jalankan:

`POST {{base_url}}/api/v1/auth/logout`

Setelah logout, request ke endpoint privat seperti `/api/v1/auth/me` harus gagal karena session sudah invalid.
