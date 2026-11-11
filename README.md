# Perpustakaan API

REST API Laravel 12 untuk autentikasi token Sanctum dan CRUD buku. Dokumentasi OpenAPI 3 dibuat dengan PHP Attributes dan L5-Swagger.

## Persyaratan

- PHP 8.3 atau lebih baru; aktifkan `pdo_sqlite`, `mbstring`, dan `openssl`
- Composer 2
- SQLite untuk konfigurasi bawaan; MySQL dapat digunakan dengan `pdo_mysql`

## Instalasi lokal

Di PowerShell:

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
if (-not (Test-Path database\database.sqlite)) { New-Item database\database.sqlite -ItemType File | Out-Null }
if (-not ((Get-Content .env | Where-Object { $_ -match '^APP_KEY=.+$' }))) { php artisan key:generate }
php artisan migrate --seed
php artisan l5-swagger:generate
php artisan serve
```

Di Linux/macOS:

```bash
composer install
cp .env.example .env
touch database/database.sqlite
grep -q '^APP_KEY=.' .env || php artisan key:generate
php artisan migrate --seed
php artisan l5-swagger:generate
php artisan serve
```

Gunakan `php artisan migrate:fresh --seed` hanya pada database development yang seluruh isinya boleh dihapus.

## Dokumentasi API

- Swagger UI: <http://localhost:8000/api/documentation>
- OpenAPI JSON: <http://localhost:8000/docs>
- Berkas hasil generate: `storage/api-docs/api-docs.json`

Saat development, `L5_SWAGGER_GENERATE_ALWAYS=true` memperbarui dokumentasi saat dibuka. Di produksi, set nilainya `false` dan jalankan `php artisan l5-swagger:generate` saat deployment.

## Autentikasi dan endpoint

Register dan login mengembalikan token Sanctum. Untuk route terproteksi, kirim header `Authorization` dengan skema `Bearer` dan token tersebut, serta `Accept: application/json`.

| Method | Endpoint | Akses | Keterangan |
| --- | --- | --- | --- |
| POST | `/api/register` | Publik | Buat akun dan token |
| POST | `/api/login` | Publik | Login; throttle 6 percobaan per menit |
| GET | `/api/me` | Sanctum | Profil pengguna |
| POST | `/api/logout` | Sanctum | Cabut token aktif |
| GET | `/api/books` | Sanctum | Daftar, pencarian, dan paginasi |
| POST | `/api/books` | Sanctum | Buat buku |
| GET | `/api/books/{book}` | Sanctum | Detail buku |
| PUT/PATCH | `/api/books/{book}` | Sanctum | Ubah buku; field opsional |
| DELETE | `/api/books/{book}` | Sanctum | Hapus buku |

`GET /api/books` menerima:

- `search`: cari teks pada judul dan penulis (maksimal 255 karakter).
- `per_page`: jumlah per halaman; dibatasi otomatis antara 1 dan 100.
- `page`: nomor halaman, minimal 1.

Input pencarian atau paginasi yang bertipe salah, serta nomor halaman di bawah 1, menghasilkan respons validasi `422`. Buku mempunyai field `title`, `author`, `publisher`, `isbn`, `published_year`, `stock`, dan `description`. ISBN harus unik; `publisher` dan `description` boleh `null`. Respons resource mengikuti bentuk `{ "data": ... }`; daftar berpaginasi juga berisi `links` dan `meta`.

Contoh body untuk membuat buku:

```json
{
  "title": "Laskar Pelangi",
  "author": "Andrea Hirata",
  "publisher": "Bentang Pustaka",
  "isbn": "9789793062792",
  "published_year": 2005,
  "stock": 10,
  "description": "Novel tentang anak-anak Belitung."
}
```

Akun demo seeder: `admin@example.com` / `password`. Gunakan kredensial lain di lingkungan produksi.

## Database

SQLite dipakai secara default. Untuk MySQL, ubah `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env`, lalu pastikan ekstensi `pdo_mysql` aktif.

Seeder membuat akun demo dan 20 buku jika belum tersedia dan aman dijalankan ulang tanpa menggandakan data demo.

## Pemeriksaan

```bash
php artisan route:list --path=api
php artisan l5-swagger:generate
php artisan test
```

Feature tests mencakup registrasi, login dan throttle, logout, akses terproteksi, pencarian dan paginasi (termasuk input query tidak valid), validasi ISBN, CRUD buku, serta seeder idempoten. PHPUnit memakai SQLite in-memory.

## Produksi

- Set `APP_ENV=production`, `APP_DEBUG=false`, gunakan `APP_KEY` aman dan domain HTTPS.
- Set `L5_SWAGGER_GENERATE_ALWAYS=false` dan generate dokumentasi saat deployment.
- Batasi akses ke `/api/documentation` bila spesifikasi tidak boleh publik.
- Atur masa berlaku token di `config/sanctum.php` dan gunakan database produksi.
- Jangan gunakan akun demo atau menjalankan `migrate:fresh` pada database produksi.
