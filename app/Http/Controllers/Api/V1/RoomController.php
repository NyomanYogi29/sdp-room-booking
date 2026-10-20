<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Room\StoreRoomRequest;
use App\Http\Requests\Room\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Booking;
use App\Models\Room;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RoomController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of rooms with search, filter, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $rooms = Room::with(['building', 'facilities'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->query('q');
                $query->where(function ($subQuery) use ($q) {
                    $subQuery->where('name', 'like', "%{$q}%")
                             ->orWhere('code', 'like', "%{$q}%");
                });
            })
            ->when($request->filled('building_id'), function ($query) use ($request) {
                $query->where('building_id', $request->query('building_id'));
            })
            ->when($request->filled('type'), function ($query) use ($request) {
                $query->where('type', $request->query('type'));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->query('status'));
            })
            ->when($request->filled('min_capacity'), function ($query) use ($request) {
                $query->where('capacity', '>=', (int) $request->query('min_capacity'));
            })
            ->when($request->filled('facility_id'), function ($query) use ($request) {
                $query->whereHas('facilities', function ($sub) use ($request) {
                    $sub->where('facilities.id', $request->query('facility_id'));
                });
            })
            ->paginate($request->query('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'Daftar ruangan berhasil diambil',
            'data' => RoomResource::collection($rooms->items()),
            'meta' => [
                'current_page' => $rooms->currentPage(),
                'per_page' => $rooms->perPage(),
                'total' => $rooms->total(),
                'last_page' => $rooms->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created room with photo upload to Storage.
     */
    public function store(StoreRoomRequest $request): JsonResponse
    {
        $data = $request->validated();
        $facilityIds = $data['facility_ids'] ?? [];
        unset($data['facility_ids']);

        // Upload photo to storage/app/public/rooms/
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('rooms', 'public');
        }

        $room = Room::create($data);

        if (! empty($facilityIds)) {
            $room->facilities()->sync($facilityIds);
        }

        return $this->ok(
            new RoomResource($room->load(['building', 'facilities'])),
            'Ruangan berhasil ditambahkan',
            201
        );
    }

    /**
     * Display the specified room.
     */
    public function show(Room $room): JsonResponse
    {
        return $this->ok(
            new RoomResource($room->load(['building', 'facilities'])),
            'Detail ruangan berhasil diambil'
        );
    }

    /**
     * Update the specified room and manage storage lifecycle (delete old photo).
     */
    public function update(UpdateRoomRequest $request, Room $room): JsonResponse
    {
        $data = $request->validated();
        $facilityIds = $data['facility_ids'] ?? null;
        unset($data['facility_ids']);

        // Lifecycle: jika upload foto baru, hapus foto lama dari storage
        if ($request->hasFile('photo')) {
            if ($room->photo && Storage::disk('public')->exists($room->photo)) {
                Storage::disk('public')->delete($room->photo);
            }
            $data['photo'] = $request->file('photo')->store('rooms', 'public');
        }

        $room->update($data);

        if ($facilityIds !== null) {
            $room->facilities()->sync($facilityIds);
        }

        return $this->ok(
            new RoomResource($room->fresh(['building', 'facilities'])),
            'Ruangan berhasil diperbarui'
        );
    }

    /**
     * Remove the specified room and its photo file from storage.
     */
    public function destroy(Room $room): JsonResponse
    {
        if ($room->bookings()->whereIn('status', ['pending', 'approved'])->exists()) {
            return $this->fail('Ruangan tidak dapat dihapus karena memiliki pemesanan aktif.', 422);
        }

        // Hapus berkas fisik dari storage jika ada
        if ($room->photo && Storage::disk('public')->exists($room->photo)) {
            Storage::disk('public')->delete($room->photo);
        }

        $room->facilities()->detach();
        $room->delete();

        return $this->ok(null, 'Ruangan dan berkas fotonya berhasil dihapus');
    }

    /**
     * Check room availability on a specific date.
     */
    public function availability(Request $request, Room $room): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::parse($request->query('date'));

        $bookings = Booking::where('room_id', $room->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_at', $date)
            ->orderBy('start_at')
            ->get(['id', 'title', 'start_at', 'end_at', 'status']);

        return $this->ok([
            'room' => [
                'id' => $room->id,
                'name' => $room->name,
                'code' => $room->code,
                'status' => $room->status,
            ],
            'date' => $date->toDateString(),
            'occupied_slots' => $bookings,
        ], 'Ketersediaan ruangan berhasil diambil');
    }
}
