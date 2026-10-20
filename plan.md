### Langkah 2: Mengisi Schema Migration

Setelah file-file migration di atas terbentuk, isi method up() dan down() sesuai spesifikasi di TODO.md:

1. add_role_to_users_table:  
   • up(): $table->enum('role', ['admin', 'dosen', 'mahasiswa'])->default('mahasiswa')->after('password');  
   • down(): $table->dropColumn('role');
2. create_user_profiles_table:  
   • Kolom: user_id (foreignId('user_id')->unique()->constrained()->cascadeOnDelete()), nim_nip, phone (nullable), unit.
3. create_buildings_table:  
   • Kolom: code (string, unique), name (string).
4. create_facilities_table:  
   • Kolom: name (string).
5. create_rooms_table:  
   • Kolom: building_id (foreignId('building_id')->constrained()->cascadeOnDelete()), code (string, unique), name (string), floor (integer), capacity  
   (integer), type (enum(['kelas', 'lab', 'rapat'])), status (enum(['active', 'maintenance'])->default('active')), photo (string, nullable).
6. create_facility_room_table:  
   • Kolom: facility_id (foreignId()->constrained()->cascadeOnDelete()), room_id (foreignId()->constrained()->cascadeOnDelete()), primary key gabungan:
   $table->primary(['facility_id', 'room_id']);.
7. create_bookings_table:  
   • Kolom:  
    • user_id (foreignId()->constrained()->cascadeOnDelete())  
    • room_id (foreignId()->constrained()->restrictOnDelete()) — catatan: restrictOnDelete() penting agar ruangan yang memiliki histori booking  
    tidak terhapus begitu saja.  
    • title, purpose (text, nullable), attendees (integer).  
    • start_at (dateTime), end_at (dateTime).  
    • status (enum(['pending', 'approved', 'rejected', 'cancelled', 'completed'])->default('pending')).  
    • approved_by (foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()).  
    • rejection_reason (text, nullable).  
   • Index Wajib:  
    $table->index(['room_id', 'start_at', 'end_at']);  

8. create_usage_logs_table:  
   • Kolom: booking_id (foreignId()->unique()->constrained()->cascadeOnDelete()), checked_in_at (dateTime, nullable), checked_out_at (dateTime,  
   nullable), actual_attendees (integer, nullable).  


──────

### Langkah 3: Menyiapkan Eloquent Model

Di setiap class model (app/Models/), tambahkan properti $fillable, $casts, serta method relasi:

• User:  
 • Relasi: hasOne(UserProfile::class), hasMany(Booking::class)  
 • UserProfile:  
 • Relasi: belongsTo(User::class)  
 • Building:  
 • Relasi: hasMany(Room::class)  
 • Room:  
 • Relasi: belongsTo(Building::class), belongsToMany(Facility::class, 'facility_room'), hasMany(Booking::class)  
 • Facility:  
 • Relasi: belongsToMany(Room::class, 'facility_room')  
 • Booking:  
 • Relasi: belongsTo(User::class), belongsTo(Room::class), belongsTo(User::class, 'approved_by'), hasOne(UsageLog::class)  
 • Casts: 'start_at' => 'datetime', 'end_at' => 'datetime'  
 • UsageLog:  
 • Relasi: belongsTo(Booking::class)  
 • Casts: 'checked_in_at' => 'datetime', 'checked_out_at' => 'datetime'

──────

### Langkah 4: Menyiapkan Seeder

Buat seeder untuk mengisi data awal:

    php artisan make:seeder InitialDataSeeder


• Fungsi command: Membuat file class seeder di database/seeders/InitialDataSeeder.php yang dapat diisi data dummy sesuai target TODO (1 admin, beberapa
dosen & mahasiswa, 3–4 gedung, ±15 ruangan, ±8 fasilitas, dan ±30 booking).  
 • Panggil seeder ini di dalam DatabaseSeeder.php:  
 $this->call(InitialDataSeeder::class);

──────

### Langkah 5: Eksekusi & Validasi Migration

Setelah schema, model, dan seeder selesai ditulis, gunakan command-command berikut:

#### 1. Cek Status Migration

    php artisan migrate:status

• Fungsi command: Menampilkan tabel daftar semua file migration dan statusnya (Ran: Yes/No). Berguna untuk memastikan seluruh file terbaca sesuai urutan
timestamp.

#### 2. Jalankan Fresh Migration + Seed

    php artisan migrate:fresh --seed

• Fungsi command: 1. Menghapus (drop) semua tabel yang ada di database saat ini. 2. Menjalankan ulang seluruh migration dari awal secara berurutan. 3. Menjalankan DatabaseSeeder untuk langsung mengisi database dengan data pengujian. 4. Perintah ini adalah tolok ukur bahwa Phase 1 selesai dengan bersih (clean exit code 0).
