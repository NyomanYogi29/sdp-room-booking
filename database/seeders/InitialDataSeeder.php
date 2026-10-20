<?php

namespace Database\Seeders;

use App\Models\AcademicSlot;
use App\Models\Booking;
use App\Models\Building;
use App\Models\Facility;
use App\Models\Room;
use App\Models\UsageLog;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Kamus Waktu Akademik SKS (Slots A s.d. O) SDP Undiksha
        $slotsData = [
            ['code' => 'A', 'start_time' => '07:30', 'end_time' => '08:20', 'label' => '07:30 - 08:20', 'order' => 1, 'duration_minutes' => 50, 'description' => 'SKS Pagi 1'],
            ['code' => 'B', 'start_time' => '08:30', 'end_time' => '09:20', 'label' => '08:30 - 09:20', 'order' => 2, 'duration_minutes' => 50, 'description' => 'SKS Pagi 2'],
            ['code' => 'C', 'start_time' => '09:30', 'end_time' => '10:20', 'label' => '09:30 - 10:20', 'order' => 3, 'duration_minutes' => 50, 'description' => 'SKS Pagi 3'],
            ['code' => 'D', 'start_time' => '10:30', 'end_time' => '11:20', 'label' => '10:30 - 11:20', 'order' => 4, 'duration_minutes' => 50, 'description' => 'SKS Siang 1'],
            ['code' => 'E', 'start_time' => '11:30', 'end_time' => '12:20', 'label' => '11:30 - 12:20', 'order' => 5, 'duration_minutes' => 50, 'description' => 'SKS Siang 2'],
            ['code' => 'F', 'start_time' => '12:30', 'end_time' => '13:20', 'label' => '12:30 - 13:20', 'order' => 6, 'duration_minutes' => 50, 'description' => 'SKS Siang 3'],
            ['code' => 'G', 'start_time' => '13:30', 'end_time' => '14:20', 'label' => '13:30 - 14:20', 'order' => 7, 'duration_minutes' => 50, 'description' => 'SKS Sore 1'],
            ['code' => 'H', 'start_time' => '14:30', 'end_time' => '15:30', 'label' => '14:30 - 15:30', 'order' => 8, 'duration_minutes' => 60, 'description' => 'SKS Jeda / Ishoma'],
            ['code' => 'I', 'start_time' => '15:30', 'end_time' => '16:20', 'label' => '15:30 - 16:20', 'order' => 9, 'duration_minutes' => 50, 'description' => 'SKS Sore 2'],
            ['code' => 'J', 'start_time' => '16:20', 'end_time' => '17:10', 'label' => '16:20 - 17:10', 'order' => 10, 'duration_minutes' => 50, 'description' => 'SKS Sore 3'],
            ['code' => 'K', 'start_time' => '17:30', 'end_time' => '18:20', 'label' => '17:30 - 18:20', 'order' => 11, 'duration_minutes' => 50, 'description' => 'SKS Malam 1'],
            ['code' => 'L', 'start_time' => '18:20', 'end_time' => '19:10', 'label' => '18:20 - 19:10', 'order' => 12, 'duration_minutes' => 50, 'description' => 'SKS Malam 2'],
            ['code' => 'M', 'start_time' => '19:30', 'end_time' => '20:20', 'label' => '19:30 - 20:20', 'order' => 13, 'duration_minutes' => 50, 'description' => 'SKS Malam 3'],
            ['code' => 'N', 'start_time' => '20:20', 'end_time' => '21:10', 'label' => '20:20 - 21:10', 'order' => 14, 'duration_minutes' => 50, 'description' => 'SKS Malam 4'],
            ['code' => 'O', 'start_time' => '21:10', 'end_time' => '22:00', 'label' => '21:10 - 22:00', 'order' => 15, 'duration_minutes' => 50, 'description' => 'SKS Malam 5 / Batas Operasional'],
        ];

        foreach ($slotsData as $slot) {
            AcademicSlot::create($slot);
        }

        // 2. Akun Pengguna & Profil (Admin, Dosen, Mahasiswa)
        $usersData = [
            [
                'name' => 'Administrator Undiksha',
                'email' => 'admin@undiksha.ac.id',
                'role' => 'admin',
                'password' => Hash::make('password'),
                'profile' => [
                    'nim_nip' => '198501012010121001',
                    'phone' => '081234567890',
                    'unit' => 'UPT TIK Undiksha',
                ],
            ],
            [
                'name' => 'Dr. I Gede Made, M.Cs.',
                'email' => 'dosen1@undiksha.ac.id',
                'role' => 'dosen',
                'password' => Hash::make('password'),
                'profile' => [
                    'nim_nip' => '197903152005011002',
                    'phone' => '081234567891',
                    'unit' => 'Pendidikan Teknik Informatika',
                ],
            ],
            [
                'name' => 'Ni Wayan Sukma, M.T.',
                'email' => 'dosen2@undiksha.ac.id',
                'role' => 'dosen',
                'password' => Hash::make('password'),
                'profile' => [
                    'nim_nip' => '198807202015042001',
                    'phone' => '081234567892',
                    'unit' => 'Sistem Informasi',
                ],
            ],
            [
                'name' => 'I Putu Arya Mahasiswa',
                'email' => 'mhs1@undiksha.ac.id',
                'role' => 'mahasiswa',
                'password' => Hash::make('password'),
                'profile' => [
                    'nim_nip' => '2215101001',
                    'phone' => '081234567893',
                    'unit' => 'Ilmu Komputer',
                ],
            ],
            [
                'name' => 'Kadek Ayu Lestari',
                'email' => 'mhs2@undiksha.ac.id',
                'role' => 'mahasiswa',
                'password' => Hash::make('password'),
                'profile' => [
                    'nim_nip' => '2215101002',
                    'phone' => '081234567894',
                    'unit' => 'Sistem Informasi',
                ],
            ],
            [
                'name' => 'Komang Agus Wijaya',
                'email' => 'mhs3@undiksha.ac.id',
                'role' => 'mahasiswa',
                'password' => Hash::make('password'),
                'profile' => [
                    'nim_nip' => '2215101003',
                    'phone' => '081234567895',
                    'unit' => 'Pendidikan Teknik Informatika',
                ],
            ],
        ];

        $users = [];
        foreach ($usersData as $data) {
            $profileData = $data['profile'];
            unset($data['profile']);

            $user = User::create($data);
            UserProfile::create(array_merge(['user_id' => $user->id], $profileData));
            $users[$user->email] = $user;
        }

        // 3. Fasilitas Kampus
        $facilities = [
            'Proyektor 4K' => Facility::create(['name' => 'Proyektor 4K']),
            'AC Central' => Facility::create(['name' => 'AC Central']),
            'Sound System & Mic Wireless' => Facility::create(['name' => 'Sound System & Mic Wireless']),
            'Smart TV 65 Inch' => Facility::create(['name' => 'Smart TV 65 Inch']),
            'Whiteboard & Marker' => Facility::create(['name' => 'Whiteboard & Marker']),
            'High-Speed WiFi 6' => Facility::create(['name' => 'High-Speed WiFi 6']),
        ];

        // 4. Gedung sesuai spesifikasi
        $buildings = [
            'Gedung R.A. Kartini' => Building::create([
                'code' => 'RAK',
                'name' => 'Gedung R.A. Kartini',
            ]),
            'Gedung Ki Hadjar Dewantara' => Building::create([
                'code' => 'KHD',
                'name' => 'Gedung Ki Hadjar Dewantara',
            ]),
            'Gedung Utama' => Building::create([
                'code' => 'GUT',
                'name' => 'Gedung Utama',
            ]),
        ];

        // 5. Data Ruangan Resmi (ROOM_LIST)
        $rawRooms = [
            // Lantai 1 Gedung R.A. Kartini
            ['code' => 'RAK_1.1', 'name' => 'Ruang 1.1', 'building' => 'Gedung R.A. Kartini', 'floor' => 1, 'capacity' => 40, 'type' => 'kelas'],
            ['code' => 'RAK_1.2', 'name' => 'Ruang 1.2', 'building' => 'Gedung R.A. Kartini', 'floor' => 1, 'capacity' => 40, 'type' => 'kelas'],
            ['code' => 'RAK_1.3', 'name' => 'Ruang 1.3', 'building' => 'Gedung R.A. Kartini', 'floor' => 1, 'capacity' => 40, 'type' => 'kelas'],
            ['code' => 'RAK_1.4', 'name' => 'Ruang 1.4', 'building' => 'Gedung R.A. Kartini', 'floor' => 1, 'capacity' => 17, 'type' => 'kelas'],

            // Lantai 2 Gedung R.A. Kartini
            ['code' => 'RAK_2.1', 'name' => 'Ruang 2.1', 'building' => 'Gedung R.A. Kartini', 'floor' => 2, 'capacity' => 40, 'type' => 'kelas'],
            ['code' => 'RAK_2.2', 'name' => 'Ruang 2.2', 'building' => 'Gedung R.A. Kartini', 'floor' => 2, 'capacity' => 40, 'type' => 'kelas'],
            ['code' => 'RAK_2.3', 'name' => 'Ruang 2.3', 'building' => 'Gedung R.A. Kartini', 'floor' => 2, 'capacity' => 40, 'type' => 'kelas'],
            ['code' => 'RAK_2.4', 'name' => 'Ruang 2.4', 'building' => 'Gedung R.A. Kartini', 'floor' => 2, 'capacity' => 17, 'type' => 'kelas'],

            // Lantai 3 Gedung R.A. Kartini
            ['code' => 'RAK_3.1', 'name' => 'Ruang 3.1', 'building' => 'Gedung R.A. Kartini', 'floor' => 3, 'capacity' => 40, 'type' => 'kelas'],
            ['code' => 'RAK_3.2', 'name' => 'Ruang 3.2', 'building' => 'Gedung R.A. Kartini', 'floor' => 3, 'capacity' => 40, 'type' => 'kelas'],
            ['code' => 'RAK_3.3', 'name' => 'Ruang 3.3', 'building' => 'Gedung R.A. Kartini', 'floor' => 3, 'capacity' => 40, 'type' => 'kelas'],
            ['code' => 'RAK_3.4', 'name' => 'Ruang 3.4', 'building' => 'Gedung R.A. Kartini', 'floor' => 3, 'capacity' => 17, 'type' => 'kelas'],

            // Lantai 4 Gedung R.A. Kartini
            ['code' => 'RAK_4.1', 'name' => 'Ruang 4.1', 'building' => 'Gedung R.A. Kartini', 'floor' => 4, 'capacity' => 40, 'type' => 'kelas'],
            ['code' => 'RAK_4.2', 'name' => 'Ruang 4.2', 'building' => 'Gedung R.A. Kartini', 'floor' => 4, 'capacity' => 40, 'type' => 'kelas'],
            ['code' => 'RAK_4.3', 'name' => 'Ruang 4.3', 'building' => 'Gedung R.A. Kartini', 'floor' => 4, 'capacity' => 40, 'type' => 'kelas'],
            ['code' => 'RAK_4.4', 'name' => 'Ruang 4.4', 'building' => 'Gedung R.A. Kartini', 'floor' => 4, 'capacity' => 17, 'type' => 'kelas'],

            // Lantai 2 Gedung Ki Hadjar Dewantara
            ['code' => 'KHD_2.2', 'name' => 'Ruang 2.2', 'building' => 'Gedung Ki Hadjar Dewantara', 'floor' => 2, 'capacity' => 40, 'type' => 'kelas'],
            ['code' => 'KHD_2.3', 'name' => 'Ruang 2.3', 'building' => 'Gedung Ki Hadjar Dewantara', 'floor' => 2, 'capacity' => 40, 'type' => 'kelas'],
            ['code' => 'KHD_2.4', 'name' => 'Ruang 2.4', 'building' => 'Gedung Ki Hadjar Dewantara', 'floor' => 2, 'capacity' => 17, 'type' => 'kelas'],

            // Lantai 3 Gedung Ki Hadjar Dewantara
            ['code' => 'KHD_3.2', 'name' => 'Ruang 3.2', 'building' => 'Gedung Ki Hadjar Dewantara', 'floor' => 3, 'capacity' => 40, 'type' => 'kelas'],
            ['code' => 'KHD_3.3', 'name' => 'Ruang 3.3', 'building' => 'Gedung Ki Hadjar Dewantara', 'floor' => 3, 'capacity' => 40, 'type' => 'kelas'],
            ['code' => 'KHD_3.4', 'name' => 'Ruang 3.4', 'building' => 'Gedung Ki Hadjar Dewantara', 'floor' => 3, 'capacity' => 17, 'type' => 'kelas'],

            // Lantai 4 Gedung Ki Hadjar Dewantara
            ['code' => 'KHD_4.2', 'name' => 'Ruang 4.2', 'building' => 'Gedung Ki Hadjar Dewantara', 'floor' => 4, 'capacity' => 40, 'type' => 'kelas'],
            ['code' => 'KHD_4.3', 'name' => 'Ruang 4.3', 'building' => 'Gedung Ki Hadjar Dewantara', 'floor' => 4, 'capacity' => 40, 'type' => 'kelas'],
            ['code' => 'KHD_4.4', 'name' => 'Ruang 4.4', 'building' => 'Gedung Ki Hadjar Dewantara', 'floor' => 4, 'capacity' => 17, 'type' => 'kelas'],

            // Fasilitas Utama / Serbaguna
            [
                'code' => 'AUDITORIUM',
                'name' => 'Auditorium SDP',
                'building' => 'Gedung Utama',
                'floor' => 1,
                'capacity' => 200,
                'type' => 'rapat',
                'description' => 'Aula serbaguna kegiatan dan seminar kampus',
            ],
            [
                'code' => 'HYBRID',
                'name' => 'Ruang Hybrid 1.2',
                'building' => 'Gedung Ki Hadjar Dewantara',
                'floor' => 1,
                'capacity' => 25,
                'type' => 'lab',
                'description' => 'Perkuliahan hybrid atau ruang meeting direksi',
            ],
        ];

        $rooms = [];
        foreach ($rawRooms as $r) {
            $building = $buildings[$r['building']];

            $room = Room::create([
                'building_id' => $building->id,
                'code' => $r['code'],
                'name' => $r['name'],
                'description' => $r['description'] ?? null,
                'floor' => $r['floor'],
                'capacity' => $r['capacity'],
                'type' => $r['type'],
                'status' => 'active',
                'photo' => null,
            ]);

            // Assign fasilitas standar
            $room->facilities()->sync([
                $facilities['AC Central']->id,
                $facilities['Whiteboard & Marker']->id,
                $facilities['High-Speed WiFi 6']->id,
                $facilities['Proyektor 4K']->id,
            ]);

            $rooms[$r['code']] = $room;
        }

        // Fasilitas tambahan untuk Auditorium & Hybrid
        $rooms['AUDITORIUM']->facilities()->sync(array_values(array_map(fn ($f) => $f->id, $facilities)));
        $rooms['HYBRID']->facilities()->sync([
            $facilities['AC Central']->id,
            $facilities['Smart TV 65 Inch']->id,
            $facilities['Sound System & Mic Wireless']->id,
            $facilities['High-Speed WiFi 6']->id,
        ]);

        // 6. Contoh Pemesanan Berdasarkan Slot SKS
        $tomorrow = Carbon::now()->addDay();

        // Booking 1: Slot A-C (07:30 - 10:20) di Ruang RAK_1.1
        $booking1 = Booking::create([
            'user_id' => $users['dosen1@undiksha.ac.id']->id,
            'room_id' => $rooms['RAK_1.1']->id,
            'title' => 'Kuliah Rekayasa Web (3 SKS)',
            'purpose' => 'Praktikum integrasi RESTful API Laravel & MariaDB',
            'attendees' => 38,
            'start_at' => $tomorrow->copy()->setTime(7, 30, 0),
            'end_at' => $tomorrow->copy()->setTime(10, 20, 0),
            'slot_start' => 'A',
            'slot_end' => 'C',
            'status' => 'approved',
            'approved_by' => $users['admin@undiksha.ac.id']->id,
        ]);

        // Booking 2: Slot D-E (10:30 - 12:20) di Ruang HYBRID
        Booking::create([
            'user_id' => $users['dosen2@undiksha.ac.id']->id,
            'room_id' => $rooms['HYBRID']->id,
            'title' => 'Kuliah Hybrid Manajemen Proyek TI',
            'purpose' => 'Sesi tatap muka dan video conference dengan narasumber industri',
            'attendees' => 22,
            'start_at' => $tomorrow->copy()->setTime(10, 30, 0),
            'end_at' => $tomorrow->copy()->setTime(12, 20, 0),
            'slot_start' => 'D',
            'slot_end' => 'E',
            'status' => 'pending',
        ]);

        // Booking 3: Slot G-H (13:30 - 15:30) di AUDITORIUM (Selesai dengan UsageLog)
        $past = Carbon::now()->subDay();
        $booking3 = Booking::create([
            'user_id' => $users['mhs1@undiksha.ac.id']->id,
            'room_id' => $rooms['AUDITORIUM']->id,
            'title' => 'Seminar Nasional Himpunan Mahasiswa Jurusan TI',
            'purpose' => 'Seminar kecerdasan buatan dan karir bidang teknologi',
            'attendees' => 180,
            'start_at' => $past->copy()->setTime(13, 30, 0),
            'end_at' => $past->copy()->setTime(15, 30, 0),
            'slot_start' => 'G',
            'slot_end' => 'H',
            'status' => 'completed',
            'approved_by' => $users['admin@undiksha.ac.id']->id,
        ]);

        UsageLog::create([
            'booking_id' => $booking3->id,
            'checked_in_at' => $past->copy()->setTime(13, 35, 0),
            'checked_out_at' => $past->copy()->setTime(15, 25, 0),
            'actual_attendees' => 175,
        ]);
    }
}
