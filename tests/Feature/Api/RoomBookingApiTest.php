<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\Building;
use App\Models\Facility;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoomBookingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $mahasiswa;
    private Building $building;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->mahasiswa = User::create([
            'name' => 'Mhs Test',
            'email' => 'mhs@test.com',
            'password' => bcrypt('password'),
            'role' => 'mahasiswa',
        ]);

        $this->building = Building::create([
            'code' => 'GKB',
            'name' => 'Gedung Kuliah Bersama',
        ]);

        $this->room = Room::create([
            'building_id' => $this->building->id,
            'code' => 'GKB-101',
            'name' => 'Ruang Teori 101',
            'floor' => 1,
            'capacity' => 40,
            'type' => 'kelas',
            'status' => 'active',
        ]);
    }

    public function test_user_can_register_via_api(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Calon Dosen',
            'email' => 'calondosen@undiksha.ac.id',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'dosen',
            'nim_nip' => '199501012022011001',
            'phone' => '081299998888',
            'unit' => 'Ilmu Komputer',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                    'user' => ['id', 'name', 'email', 'role', 'profile'],
                ],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'calondosen@undiksha.ac.id', 'role' => 'dosen']);
        $this->assertDatabaseHas('user_profiles', ['nim_nip' => '199501012022011001']);
    }

    public function test_cannot_register_as_admin(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Hacker Admin',
            'email' => 'hacker@undiksha.ac.id',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'admin',
            'nim_nip' => '0000',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_login_and_logout(): void
    {
        $loginResponse = $this->postJson('/api/v1/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $token = $loginResponse->json('data.token');

        // Test profile with token
        $profileResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/profile');

        $profileResponse->assertStatus(200)
            ->assertJsonPath('data.email', 'admin@test.com');

        // Test logout
        $logoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/logout');

        $logoutResponse->assertStatus(200);

        // Reset auth state in test environment
        $this->app['auth']->forgetGuards();

        // After logout, token should be invalid
        $afterLogout = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->getJson('/api/v1/profile');

        $afterLogout->assertStatus(401);
    }

    public function test_storage_lifecycle_upload_update_and_delete_room_photo(): void
    {
        $file1 = UploadedFile::fake()->create('room1.jpg', 500, 'image/jpeg');

        // 1. Upload photo saat create room
        $createResponse = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/rooms', [
                'building_id' => $this->building->id,
                'code' => 'LAB-NEW',
                'name' => 'Lab Baru',
                'floor' => 2,
                'capacity' => 25,
                'type' => 'lab',
                'status' => 'active',
                'photo' => $file1,
            ]);

        $createResponse->assertStatus(201);
        $roomId = $createResponse->json('data.id');
        $photoPath1 = $createResponse->json('data.photo');

        $this->assertNotNull($photoPath1);
        Storage::disk('public')->assertExists($photoPath1);

        // 2. Update photo: upload foto kedua, pastikan foto pertama terhapus
        $file2 = UploadedFile::fake()->create('room2.png', 500, 'image/png');
        $updateResponse = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/rooms/{$roomId}", [
                'name' => 'Lab Baru Diperbarui',
                'photo' => $file2,
            ]);

        $updateResponse->assertStatus(200);
        $photoPath2 = $updateResponse->json('data.photo');

        $this->assertNotEquals($photoPath1, $photoPath2);
        Storage::disk('public')->assertExists($photoPath2);
        Storage::disk('public')->assertMissing($photoPath1);

        // 3. Delete room: pastikan foto fisik ikut terhapus dari storage
        $deleteResponse = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/rooms/{$roomId}");

        $deleteResponse->assertStatus(200);
        Storage::disk('public')->assertMissing($photoPath2);
    }

    public function test_booking_concurrency_and_double_booking_conflict(): void
    {
        $start = Carbon::now()->addDays(3)->setHour(10)->setMinute(0);
        $end = $start->copy()->addHours(2); // 10:00 - 12:00

        // 1. Booking pertama berhasil (201)
        $booking1 = $this->actingAs($this->mahasiswa, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'room_id' => $this->room->id,
                'title' => 'Diskusi Kelompok A',
                'attendees' => 15,
                'start_at' => $start->toDateTimeString(),
                'end_at' => $end->toDateTimeString(),
            ]);

        $booking1->assertStatus(201)
            ->assertJsonPath('success', true);

        // 2. Booking kedua dengan rentang waktu beririsan (11:00 - 13:00) HARUS 409 Conflict
        $overlappingStart = $start->copy()->addHour(); // 11:00
        $overlappingEnd = $overlappingStart->copy()->addHours(2); // 13:00

        $booking2 = $this->actingAs($this->mahasiswa, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'room_id' => $this->room->id,
                'title' => 'Diskusi Kelompok B (Bentrok)',
                'attendees' => 10,
                'start_at' => $overlappingStart->toDateTimeString(),
                'end_at' => $overlappingEnd->toDateTimeString(),
            ]);

        $booking2->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_rbac_approval_and_checkin_flow(): void
    {
        $start = Carbon::now()->addDays(1)->setHour(14)->setMinute(0);
        $end = $start->copy()->addHours(2);

        $booking = Booking::create([
            'user_id' => $this->mahasiswa->id,
            'room_id' => $this->room->id,
            'title' => 'Seminar Proposal Mahasiswa',
            'attendees' => 20,
            'start_at' => $start,
            'end_at' => $end,
            'status' => 'pending',
        ]);

        // Mahasiswa TIDAK boleh approve booking (403 Forbidden)
        $unauthorizedApprove = $this->actingAs($this->mahasiswa, 'sanctum')
            ->patchJson("/api/v1/bookings/{$booking->id}/approve");

        $unauthorizedApprove->assertStatus(403);

        // Admin BISA approve booking (200 OK)
        $adminApprove = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/v1/bookings/{$booking->id}/approve");

        $adminApprove->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        // Check-in
        $checkInResponse = $this->actingAs($this->mahasiswa, 'sanctum')
            ->postJson("/api/v1/bookings/{$booking->id}/check-in", [
                'actual_attendees' => 18,
            ]);

        $checkInResponse->assertStatus(200)
            ->assertJsonPath('data.usage_log.actual_attendees', 18);

        // Check-out
        $checkOutResponse = $this->actingAs($this->mahasiswa, 'sanctum')
            ->postJson("/api/v1/bookings/{$booking->id}/check-out");

        $checkOutResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');
    }
}
