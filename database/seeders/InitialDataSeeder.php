<?php

namespace Database\Seeders;

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
        // 1. Users & Profiles
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

        // 2. Facilities
        $facilities = [
            'Proyektor 4K' => Facility::create(['name' => 'Proyektor 4K']),
            'AC Central' => Facility::create(['name' => 'AC Central']),
            'Sound System & Mic Wireless' => Facility::create(['name' => 'Sound System & Mic Wireless']),
            'Smart TV 65 Inch' => Facility::create(['name' => 'Smart TV 65 Inch']),
            'Whiteboard & Marker' => Facility::create(['name' => 'Whiteboard & Marker']),
            'High-Speed WiFi' => Facility::create(['name' => 'High-Speed WiFi']),
        ];

        // 3. Buildings
        $gkb = Building::create([
            'code' => 'GKB',
            'name' => 'Gedung Kuliah Bersama',
        ]);

        $glk = Building::create([
            'code' => 'GLK',
            'name' => 'Gedung Lab Komputer & Multimedia',
        ]);

        $rek = Building::create([
            'code' => 'REK',
            'name' => 'Gedung Rektorat & Aula Kampus',
        ]);

        // 4. Rooms
        $room1 = Room::create([
            'building_id' => $gkb->id,
            'code' => 'GKB-101',
            'name' => 'Ruang Teori 101',
            'floor' => 1,
            'capacity' => 40,
            'type' => 'kelas',
            'status' => 'active',
            'photo' => null,
        ]);
        $room1->facilities()->sync([
            $facilities['AC Central']->id,
            $facilities['Proyektor 4K']->id,
            $facilities['Whiteboard & Marker']->id,
            $facilities['High-Speed WiFi']->id,
        ]);

        $room2 = Room::create([
            'building_id' => $glk->id,
            'code' => 'GLK-LAB-AI',
            'name' => 'Laboratorium AI & Data Science',
            'floor' => 2,
            'capacity' => 30,
            'type' => 'lab',
            'status' => 'active',
            'photo' => null,
        ]);
        $room2->facilities()->sync([
            $facilities['AC Central']->id,
            $facilities['Smart TV 65 Inch']->id,
            $facilities['High-Speed WiFi']->id,
        ]);

        $room3 = Room::create([
            'building_id' => $rek->id,
            'code' => 'REK-AUD-01',
            'name' => 'Auditorium Utama Ganesha',
            'floor' => 3,
            'capacity' => 150,
            'type' => 'rapat',
            'status' => 'active',
            'photo' => null,
        ]);
        $room3->facilities()->sync([
            $facilities['AC Central']->id,
            $facilities['Proyektor 4K']->id,
            $facilities['Sound System & Mic Wireless']->id,
            $facilities['High-Speed WiFi']->id,
        ]);

        $room4 = Room::create([
            'building_id' => $gkb->id,
            'code' => 'GKB-204',
            'name' => 'Ruang Seminar 204',
            'floor' => 2,
            'capacity' => 60,
            'type' => 'kelas',
            'status' => 'maintenance',
            'photo' => null,
        ]);
        $room4->facilities()->sync([
            $facilities['Whiteboard & Marker']->id,
        ]);

        // 5. Initial Bookings
        $tomorrow = Carbon::now()->addDay()->setHour(9)->setMinute(0)->setSecond(0);

        // Booking 1: Approved booking tomorrow (9am - 12pm)
        $booking1 = Booking::create([
            'user_id' => $users['dosen1@undiksha.ac.id']->id,
            'room_id' => $room1->id,
            'title' => 'Kuliah Rekayasa Web Semester 5',
            'purpose' => 'Perkuliahan tatap muka praktikum integrasi REST API',
            'attendees' => 35,
            'start_at' => $tomorrow->copy(),
            'end_at' => $tomorrow->copy()->addHours(3),
            'status' => 'approved',
            'approved_by' => $users['admin@undiksha.ac.id']->id,
        ]);

        // Booking 2: Pending booking
        Booking::create([
            'user_id' => $users['mhs1@undiksha.ac.id']->id,
            'room_id' => $room2->id,
            'title' => 'Workshop Machine Learning HMJ TI',
            'purpose' => 'Kegiatan workshop mahasiswa pengenalan PyTorch dan Deep Learning',
            'attendees' => 25,
            'start_at' => Carbon::now()->addDays(2)->setHour(13)->setMinute(0)->setSecond(0),
            'end_at' => Carbon::now()->addDays(2)->setHour(16)->setMinute(0)->setSecond(0),
            'status' => 'pending',
        ]);

        // Booking 3: Completed booking with usage log
        $pastStart = Carbon::now()->subDays(2)->setHour(10)->setMinute(0)->setSecond(0);
        $pastEnd = $pastStart->copy()->addHours(2);
        $booking3 = Booking::create([
            'user_id' => $users['dosen2@undiksha.ac.id']->id,
            'room_id' => $room3->id,
            'title' => 'Rapat Pleno Jurusan TI',
            'purpose' => 'Evaluasi kurikulum berbasis OBE',
            'attendees' => 45,
            'start_at' => $pastStart,
            'end_at' => $pastEnd,
            'status' => 'completed',
            'approved_by' => $users['admin@undiksha.ac.id']->id,
        ]);

        UsageLog::create([
            'booking_id' => $booking3->id,
            'checked_in_at' => $pastStart->copy()->addMinutes(5),
            'checked_out_at' => $pastEnd->copy()->subMinutes(10),
            'actual_attendees' => 42,
        ]);
    }
}
