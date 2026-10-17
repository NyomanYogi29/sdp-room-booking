# TODO — sdp-room-booking

Dashboard pemantauan & pemesanan ruangan Kampus Undiksha Denpasar.
Stack: Laravel 12 · MySQL · Sanctum (token) · REST API JSON · Postman/.http

## Asumsi

- **Deliverable inti = REST API** (sesuai Section 8–9). UI dashboard dikerjakan paling akhir (Phase 8) dan opsional.
- Role dibatasi 3: `admin`, `dosen`, `mahasiswa`.
- Scope dijaga sempit: 7 tabel, 1 alur bisnis utama (booking → approval → penggunaan aktual).
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
- [x] Set `.env` (DB\_\*, APP_URL, `FILESYSTEM_DISK=public`), jalankan `php artisan migrate`
- [x] `php artisan install:api`
- [x] Tambah trait `HasApiTokens` di `User`
- [x] `php artisan storage:link`
- [x] Siapkan prefix route `/api/v1` di `routes/api.php`
- [x] Commit awal + `.gitignore` + README kerangka

## Phase 1 — Database & Model `[MVP]`

- [ ] Migration: tambah `role` ke `users`
- [ ] Migration: `user_profiles`, `buildings`, `rooms`, `facilities`, `facility_room`, `bookings`, `usage_logs`
- [ ] Foreign key + `onDelete` yang tepat (cascade untuk pivot, restrict untuk `rooms` yang punya booking)
- [ ] Index `bookings (room_id, start_at, end_at)`
- [ ] Model + `$fillable` + `$casts` (datetime, enum status)
- [ ] Relasi Eloquent: `hasOne`/`belongsTo`, `hasMany`/`belongsTo`, `belongsToMany`
- [ ] Seeder: 1 admin, beberapa dosen & mahasiswa, 3–4 gedung, ±15 ruangan, ±8 fasilitas, ±30 booking
- [ ] `php artisan migrate:fresh --seed` berjalan bersih

## Phase 2 — Autentikasi (Sanctum) `[MVP]`

- [ ] `AuthController`: `register`, `login`, `logout`, `profile`
- [ ] `RegisterRequest` & `LoginRequest` (Form Request)
- [ ] Register hanya boleh membuat role `mahasiswa`/`dosen`; `admin` hanya dari seeder
- [ ] Revoke token saat logout (`currentAccessToken()->delete()`)
- [ ] Rate limit endpoint login
- [ ] Uji: tanpa token → 401, token salah → 401, kredensial salah → 422

## Phase 3 — CRUD Master Data `[MVP]`

- [ ] `BuildingController` (`apiResource`) + FormRequest + Resource
- [ ] `FacilityController` (`apiResource`) + FormRequest + Resource
- [ ] `RoomController` (`apiResource`) + `StoreRoomRequest`/`UpdateRoomRequest` + `RoomResource`
- [ ] Sinkronisasi fasilitas ruangan via `sync()` (field `facility_ids[]`)
- [ ] Upload foto: validasi `image|mimes:jpg,jpeg,png|max:2048`, simpan path saja ke DB
- [ ] Update foto: hapus file lama dari Storage sebelum simpan yang baru (update multipart memakai `POST` + `_method=PUT`)
- [ ] Hapus ruangan: hapus file foto dari Storage
- [ ] `GET /rooms`: search `q` (nama/kode), filter `building_id`, `type`, `status`, `min_capacity`, `facility_id`, pagination
- [ ] Mutasi master data hanya `admin` (Gate `manage-master-data`)

## Phase 4 — Booking (alur inti) `[MVP]`

- [ ] `StoreBookingRequest`: `room_id` exists, `start_at` di masa depan, `end_at` setelah `start_at`, jam operasional, `attendees` ≤ kapasitas ruangan, ruangan berstatus `active`
- [ ] Cek bentrok jadwal di dalam `DB::transaction` + `lockForUpdate()` (kondisi overlap: `start < existing.end AND end > existing.start`, abaikan status `rejected`/`cancelled`)
- [ ] Bentrok jadwal → HTTP 409 dengan pesan terstruktur (422 hanya untuk validasi input)
- [ ] `POST /bookings`: status awal `pending`
- [ ] `PATCH /bookings/{id}/approve` & `/reject` (admin, `approved_by` terisi; reject wajib `rejection_reason`); re-cek bentrok di dalam transaction saat approve
- [ ] `PATCH /bookings/{id}/cancel` (hanya pemilik, hanya sebelum `start_at`)
- [ ] `GET /bookings`: admin lihat semua, user biasa hanya miliknya; filter `status`, `room_id`, `date_from`, `date_to`; search `q` (judul); pagination
- [ ] `BookingPolicy`: `view`, `cancel` (owner), `update` (owner & masih `pending`)
- [ ] `BookingResource` memuat room & user (eager loading `with()` untuk hindari N+1)

## Phase 5 — Monitoring Penggunaan

- [ ] `POST /bookings/{id}/check-in` & `/check-out` → isi `usage_logs` (hanya booking `approved`, dalam rentang waktu)
- [ ] `GET /rooms/{id}/availability?date=YYYY-MM-DD` → slot terpakai pada tanggal tsb
- [ ] `GET /dashboard/summary` (admin): total ruangan, sedang dipakai sekarang, booking hari ini, booking pending
- [ ] `GET /dashboard/utilization`: tingkat pemakaian per gedung/ruangan dalam rentang tanggal, ruangan terpopuler, jumlah no-show
- [ ] Scheduler (opsional): tandai booking selesai → `completed`

## Phase 6 — RBAC `[MVP]`

- [ ] Gate `manage-master-data` (admin)
- [ ] Gate `approve-booking` (admin)
- [ ] Gate `view-dashboard` (admin)
- [ ] `RoomPolicy` (CRUD hanya admin, view semua user terautentikasi)
- [ ] Terapkan `authorize()` / `can:` di setiap controller
- [ ] Uji matriks akses: tiap role × tiap endpoint → 200/201/403

## Phase 7 — Pengujian & Dokumentasi `[MVP]`

- [ ] Folder `http/` berisi file `.http` per modul (auth, rooms, bookings, dashboard) atau Postman collection
- [ ] Setiap endpoint punya skenario: sukses, 401, 403, 404, 422 (dan 409 untuk booking)
- [ ] Postman: environment variable `base_url` & `token`
- [ ] Feature test minimal untuk bentrok jadwal & RBAC (PHPUnit/Pest)
- [ ] README: cara instalasi, daftar endpoint, akun seeder, format response & error
- [ ] Tabel endpoint final (method, URL, role, fungsi)

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
| `apiResource` | `/api/v1/buildings`               | admin (mutasi) | CRUD gedung               |
| `apiResource` | `/api/v1/facilities`              | admin (mutasi) | CRUD fasilitas            |
| `apiResource` | `/api/v1/rooms`                   | admin (mutasi) | CRUD ruangan + foto       |
| GET           | `/api/v1/rooms/{id}/availability` | auth           | Slot terpakai per tanggal |
| GET/POST      | `/api/v1/bookings`                | auth           | List / ajukan booking     |
| GET           | `/api/v1/bookings/{id}`           | owner/admin    | Detail booking            |
| PATCH         | `/api/v1/bookings/{id}/approve`   | admin          | Setujui                   |
| PATCH         | `/api/v1/bookings/{id}/reject`    | admin          | Tolak                     |
| PATCH         | `/api/v1/bookings/{id}/cancel`    | owner          | Batalkan                  |
| POST          | `/api/v1/bookings/{id}/check-in`  | owner/admin    | Mulai pemakaian           |
| POST          | `/api/v1/bookings/{id}/check-out` | owner/admin    | Selesai pemakaian         |
| GET           | `/api/v1/dashboard/summary`       | admin          | Ringkasan                 |
| GET           | `/api/v1/dashboard/utilization`   | admin          | Statistik pemakaian       |

## Definition of Done (selaras checklist evaluasi modul)

- [ ] Composer & Laravel berjalan, migration + seeder bersih
- [ ] API Resource memberi format JSON konsisten di semua endpoint
- [ ] Upload, update, dan delete file bekerja (file lama terhapus)
- [ ] Auth Sanctum bekerja: register, login, logout, endpoint terproteksi
- [ ] Form Request + response 422 terstruktur di semua mutasi
- [ ] Search, filter, pagination bekerja bersamaan
- [ ] RBAC via Gate/Policy terverifikasi (403 untuk akses terlarang)
- [ ] Transaction mencegah double-booking
- [ ] Seluruh endpoint terdokumentasi di Postman/`.http`
