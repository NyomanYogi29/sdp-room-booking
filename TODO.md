# TODO — sdp-room-booking

Dashboard pemantauan & pemesanan ruangan Kampus Undiksha Denpasar.
Stack: Laravel 12 · MariaDB 11.4 (Docker) · Sanctum (token) · REST API JSON · Postman/.http

## Asumsi

- **Deliverable inti = REST API Skala Produksi** (sesuai Section 8–9). UI dashboard dikerjakan paling akhir (Phase 8) dan opsional.
- Role dibatasi 3: `admin`, `dosen`, `mahasiswa`.
- Scope dijaga rapi & robust: 7 tabel, 1 alur bisnis utama (booking → approval → penggunaan aktual).
- Standard Response Envelope seragam: `{ success, message, data/errors }`.
- `[MVP]` = wajib untuk memenuhi tugas. Tanpa tag = nice-to-have.

## Pemetaan Tugas → Fitur

| Requirement tugas           | Implementasi                                                                |
| --------------------------- | --------------------------------------------------------------------------- |
| Migration, seeder, Eloquent | Semua tabel di bawah                                                        |
| Relasi 1-to-1               | `User` ↔ `UserProfile`, `Booking` ↔ `UsageLog`                              |
| Relasi 1-to-N               | `Building` → `Room`, `Room` → `Booking`, `User` → `Booking`                 |
| Relasi N-to-N               | `Room` ↔ `Facility` (pivot `facility_room`)                                 |
| TCL `DB::transaction`       | Pembuatan & approval booking (cek bentrok jadwal)                           |
| RBAC Gate & Policy          | `BookingPolicy`, `RoomPolicy`, Gate `manage-master-data`, `approve-booking` |
| API Resource                | `RoomResource`, `BookingResource`, dll.                                     |
| Sanctum                     | register, login, logout, profile                                            |
| Form Request + HTTP 422     | Satu FormRequest per aksi store/update                                      |
| Upload file                 | Foto ruangan (Storage disk `public`)                                        |
| Search, filter, pagination  | Rooms & Bookings                                                            |
| Pengujian                   | File `.http` / Postman collection                                           |

## Skema Data

| Tabel           | Kolom utama                                                                                                                                                                      |
| --------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `users`         | id, name, email, password, **role** (`admin`/`dosen`/`mahasiswa`)                                                                                                                |
| `user_profiles` | user_id (unique FK), nim_nip, phone, unit (prodi/jurusan)                                                                                                                        |
| `buildings`     | id, code, name                                                                                                                                                                   |
| `rooms`         | id, building_id (FK), code (unique), name, floor, capacity, type (`kelas`/`lab`/`rapat`), status (`active`/`maintenance`), photo                                                 |
| `facilities`    | id, name (proyektor, AC, whiteboard, dll.)                                                                                                                                       |
| `facility_room` | facility_id, room_id (pivot)                                                                                                                                                     |
| `bookings`      | id, user_id, room_id, title, purpose, attendees, start_at, end_at, status (`pending`/`approved`/`rejected`/`cancelled`/`completed`), approved_by (nullable FK), rejection_reason |
| `usage_logs`    | booking_id (unique FK), checked_in_at, checked_out_at, actual_attendees                                                                                                          |

Index wajib: `bookings (room_id, start_at, end_at)`.

---

## Phase 0 — Setup `[MVP]`

- [x] Starter kit Blade (Breeze) sudah terpasang: biarkan `routes/auth.php`, controller `Auth\*`, dan view login/register apa adanya. Itu auth session untuk UI web; auth API (Sanctum token) dibuat terpisah di `App\Http\Controllers\Api` dan `routes/api.php`, jadi tidak bentrok
- [x] Jalankan `npm install && npm run dev` agar asset Vite Breeze termuat
- [x] MariaDB 11.4 terisolasi di Docker Compose (`docker compose up -d`, port 3307)
- [x] Set `.env` (`DB_CONNECTION=mariadb`, `DB_PORT=3307`, `FILESYSTEM_DISK=public`, `APP_URL=http://localhost:8000`)
- [x] `php artisan install:api`
- [x] Tambah trait `HasApiTokens` di `User`
- [x] `php artisan storage:link`
- [x] Siapkan prefix route `/api/v1` di `routes/api.php`
- [x] Commit awal + `.gitignore` + README kerangka

## Phase 1 — Database & Model `[MVP]`

- [x] Migration: tambah `role` ke `users`
- [x] Migration: `user_profiles`, `buildings`, `rooms`, `facilities`, `facility_room`, `bookings`, `usage_logs`
- [x] Foreign key + `onDelete` yang tepat (cascade untuk pivot, restrict untuk `rooms` yang punya booking)
- [x] Index `bookings (room_id, start_at, end_at)`
- [x] Model + `$fillable` + `$casts` (datetime, enum status)
- [x] Relasi Eloquent: `hasOne`/`belongsTo`, `hasMany`/`belongsTo`, `belongsToMany`
- [x] Seeder: 1 admin, beberapa dosen & mahasiswa, 3 gedung, 4 ruangan, 6 fasilitas, contoh booking & usage log
- [x] `php artisan migrate:fresh --seed` berjalan bersih di MariaDB Docker

## Phase 2 — Autentikasi (Sanctum) `[MVP]`

- [x] `AuthController`: `register`, `login`, `logout`, `profile`
- [x] `ApiRegisterRequest` & `ApiLoginRequest` (Form Request)
- [x] Register hanya boleh membuat role `mahasiswa`/`dosen`; `admin` hanya dari seeder
- [x] Revoke token saat logout (`currentAccessToken()->delete()`)
- [x] Rate limit endpoint login (throttle 10/min)
- [x] Uji: tanpa token → 401, token salah → 401, kredensial salah → 422

## Phase 3 — CRUD Master Data & Storage `[MVP]`

- [x] `BuildingController` + `BuildingRequest` + `BuildingResource`
- [x] `FacilityController` + `FacilityRequest` + `FacilityResource`
- [x] `RoomController` + `StoreRoomRequest`/`UpdateRoomRequest` + `RoomResource`
- [x] Sinkronisasi fasilitas ruangan via `sync()` (field `facility_ids[]`)
- [x] Upload foto: validasi `image|mimes:jpeg,png,jpg,webp|max:2048`, simpan ke `storage/app/public/rooms`
- [x] Update foto: hapus file lama dari Storage sebelum simpan yang baru
- [x] Hapus ruangan: hapus file foto fisik dari Storage
- [x] Accessor `photo_url` menghasilkan URL publik via Storage symlink
- [x] `GET /rooms`: search `q` (nama/kode), filter `building_id`, `type`, `status`, `min_capacity`, `facility_id`, pagination
- [x] Mutasi master data hanya `admin` (Gate `manage-master-data` / `RoomPolicy`)

## Phase 4 — Booking (alur inti & Concurrency) `[MVP]`

- [x] `StoreBookingRequest`: `room_id` exists, `start_at` di masa depan, `end_at` setelah `start_at`, jam operasional, `attendees` ≤ kapasitas ruangan, ruangan berstatus `active`
- [x] Cek bentrok jadwal di dalam `DB::transaction` + `lockForUpdate()` (kondisi overlap: `start < existing.end AND end > existing.start`, abaikan status `rejected`/`cancelled`)
- [x] Bentrok jadwal → HTTP 409 Conflict dengan response JSON terstruktur (422 untuk validasi input)
- [x] `POST /bookings`: status awal `pending`
- [x] `PATCH /bookings/{id}/approve` & `/reject` (admin, `approved_by` terisi; reject wajib `rejection_reason`); re-cek bentrok di dalam transaction saat approve
- [x] `PATCH /bookings/{id}/cancel` (hanya pemilik, hanya sebelum `start_at`)
- [x] `GET /bookings`: admin lihat semua, user biasa hanya miliknya; filter `status`, `room_id`, `date_from`, `date_to`; search `q` (judul); pagination
- [x] `BookingPolicy`: `view`, `cancel` (owner), `update` (owner & masih `pending`), `checkIn`, `checkOut`
- [x] `BookingResource` memuat room, user, approver, usage log (eager loading `with()` untuk hindari N+1)

## Phase 5 — Monitoring Penggunaan

- [x] `POST /bookings/{id}/check-in` & `/check-out` → isi `usage_logs` (hanya booking `approved`, dalam rentang waktu)
- [x] `GET /rooms/{id}/availability?date=YYYY-MM-DD` → slot terpakai pada tanggal tsb
- [x] `GET /dashboard/summary` (admin): total ruangan, sedang dipakai sekarang, booking hari ini, booking pending
- [x] `GET /dashboard/utilization` (admin): tingkat pemakaian per gedung/ruangan, ruangan terpopuler

## Phase 6 — RBAC & Global Exception Handling `[MVP]`

- [x] Gate `manage-master-data` (admin)
- [x] Gate `approve-booking` (admin)
- [x] Gate `view-dashboard` (admin)
- [x] `RoomPolicy` (CRUD hanya admin, view semua user terautentikasi)
- [x] Terapkan `authorize()` / `can:` di setiap controller
- [x] Global API JSON Exception Handling di `bootstrap/app.php` (menjamin response 401, 403, 404, 422 selalu format JSON seragam `{ success, message, errors }`)

## Phase 7 — Pengujian & Dokumentasi `[MVP]`

- [x] File `tests/api/sdp_room_booking.http` interaktif mencakup seluruh 31 endpoints
- [x] Setiap endpoint punya skenario: sukses, 401, 403, 404, 422, dan 409 untuk bentrok booking
- [x] Automated Feature test (`tests/Feature/Api/RoomBookingApiTest.php`) mencakup Auth, Storage photo lifecycle, double-booking conflict 409, dan RBAC (100% PASS)
- [x] Definition of Done terpenuhi lengkap

## Phase 8 — Dashboard UI (opsional, setelah API stabil)

- [ ] Pendekatan UI: Blade + Tailwind (sudah dipilih lewat Breeze), auth memakai session bawaan Breeze
- [ ] Pindahkan logika booking (cek bentrok, approve) ke Action/Service class yang dipanggil bersama oleh API controller dan Blade controller, agar aturan bisnis tidak terduplikasi
- [ ] Halaman: login, dashboard ringkasan, daftar ruangan (search/filter), form booking, daftar booking, halaman approval admin
- [ ] Kalender/timeline ketersediaan ruangan

---

## Daftar Endpoint (target)

| Method        | URL                               | Role           | Fungsi                    |
| ------------- | --------------------------------- | -------------- | ------------------------- |
| POST          | `/api/v1/register`                | publik         | Daftar akun               |
| POST          | `/api/v1/login`                   | publik         | Login, terima token       |
| POST          | `/api/v1/logout`                  | auth           | Revoke token              |
| GET           | `/api/v1/profile`                 | auth           | Data user login           |
| GET           | `/api/v1/buildings`               | publik         | List gedung               |
| POST          | `/api/v1/buildings`               | admin          | Tambah gedung             |
| GET           | `/api/v1/buildings/{id}`          | auth           | Detail gedung             |
| PUT/PATCH     | `/api/v1/buildings/{id}`          | admin          | Update gedung             |
| DELETE        | `/api/v1/buildings/{id}`          | admin          | Hapus gedung              |
| GET           | `/api/v1/facilities`              | publik         | List fasilitas            |
| POST          | `/api/v1/facilities`              | admin          | Tambah fasilitas          |
| GET           | `/api/v1/facilities/{id}`         | auth           | Detail fasilitas          |
| PUT/PATCH     | `/api/v1/facilities/{id}`         | admin          | Update fasilitas          |
| DELETE        | `/api/v1/facilities/{id}`         | admin          | Hapus fasilitas           |
| GET           | `/api/v1/rooms`                   | publik         | List ruangan (+ filter)   |
| POST          | `/api/v1/rooms`                   | admin          | Tambah ruangan + foto     |
| GET           | `/api/v1/rooms/{id}`              | publik         | Detail ruangan            |
| POST/PUT      | `/api/v1/rooms/{id}`              | admin          | Update ruangan + ganti foto|
| DELETE        | `/api/v1/rooms/{id}`              | admin          | Hapus ruangan + foto fisik|
| GET           | `/api/v1/rooms/{id}/availability` | publik         | Slot terpakai per tanggal |
| GET           | `/api/v1/bookings`                | auth           | List pemesanan            |
| POST          | `/api/v1/bookings`                | auth           | Buat pemesanan (Anti 409) |
| GET           | `/api/v1/bookings/{id}`           | owner/admin    | Detail pemesanan          |
| PATCH         | `/api/v1/bookings/{id}/approve`   | admin          | Setujui pemesanan         |
| PATCH         | `/api/v1/bookings/{id}/reject`    | admin          | Tolak pemesanan           |
| PATCH         | `/api/v1/bookings/{id}/cancel`    | owner          | Batalkan pemesanan        |
| POST          | `/api/v1/bookings/{id}/check-in`  | owner/admin    | Mulai pemakaian           |
| POST          | `/api/v1/bookings/{id}/check-out` | owner/admin    | Selesai pemakaian         |
| GET           | `/api/v1/dashboard/summary`       | admin          | Ringkasan metrik          |
| GET           | `/api/v1/dashboard/utilization`   | admin          | Statistik pemakaian       |

## Definition of Done (selaras checklist evaluasi modul)

- [x] Composer & Laravel berjalan, migration + seeder bersih di MariaDB Docker
- [x] API Resource memberi format JSON konsisten di semua endpoint
- [x] Upload, update, dan delete file bekerja (file lama terhapus dari storage)
- [x] Auth Sanctum bekerja: register, login, logout, endpoint terproteksi
- [x] Form Request + response 422 terstruktur di semua mutasi
- [x] Search, filter, pagination bekerja bersamaan
- [x] RBAC via Gate/Policy terverifikasi (403 untuk akses terlarang)
- [x] Concurrency Transaction & Locking mencegah double-booking (HTTP 409 Conflict)
- [x] Seluruh endpoint terdokumentasi di Postman/`.http`
- [x] Test suite otomatis 100% PASS (31 tests, 102 assertions)
