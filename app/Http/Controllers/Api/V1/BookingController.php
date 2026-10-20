<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CheckInRequest;
use App\Http\Requests\Booking\CheckOutRequest;
use App\Http\Requests\Booking\RejectBookingRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\UsageLog;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of bookings.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Booking::with(['user.profile', 'room.building', 'approver', 'usageLog'])
            ->when(! $user->isAdmin(), function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->query('status'));
            })
            ->when($request->filled('room_id'), function ($q) use ($request) {
                $q->where('room_id', $request->query('room_id'));
            })
            ->when($request->filled('date_from'), function ($q) use ($request) {
                $q->where('start_at', '>=', Carbon::parse($request->query('date_from'))->startOfDay());
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                $q->where('end_at', '<=', Carbon::parse($request->query('date_to'))->endOfDay());
            })
            ->when($request->filled('q'), function ($q) use ($request) {
                $searchTerm = $request->query('q');
                $q->where(function ($sub) use ($searchTerm) {
                    $sub->where('title', 'like', "%{$searchTerm}%")
                        ->orWhere('purpose', 'like', "%{$searchTerm}%");
                });
            })
            ->orderBy('start_at', 'desc');

        $bookings = $query->paginate($request->query('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'Daftar pemesanan berhasil diambil',
            'data' => BookingResource::collection($bookings->items()),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
                'last_page' => $bookings->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created booking with concurrency transaction & conflict locking.
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $startAt = Carbon::parse($validated['start_at']);
        $endAt = Carbon::parse($validated['end_at']);

        // Jalankan pengecekan bentrok jadwal di dalam database transaction dengan pessimistic lock
        $result = DB::transaction(function () use ($validated, $user, $startAt, $endAt) {
            $conflictBooking = Booking::where('room_id', $validated['room_id'])
                ->whereIn('status', ['pending', 'approved'])
                ->where(function ($query) use ($startAt, $endAt) {
                    $query->where('start_at', '<', $endAt)
                        ->where('end_at', '>', $startAt);
                })
                ->lockForUpdate()
                ->first();

            if ($conflictBooking) {
                return [
                    'conflict' => true,
                    'booking' => $conflictBooking,
                ];
            }

            $booking = Booking::create([
                'user_id' => $user->id,
                'room_id' => $validated['room_id'],
                'title' => $validated['title'],
                'purpose' => $validated['purpose'] ?? null,
                'attendees' => $validated['attendees'],
                'start_at' => $startAt,
                'end_at' => $endAt,
                'slot_start' => $validated['slot_start'] ?? null,
                'slot_end' => $validated['slot_end'] ?? null,
                'status' => 'pending',
            ]);

            return [
                'conflict' => false,
                'booking' => $booking,
            ];
        });

        if ($result['conflict']) {
            $conflict = $result['booking'];

            return response()->json([
                'success' => false,
                'message' => 'Ruangan telah dipesan atau diajukan pada rentang waktu yang dipilih.',
                'conflict' => [
                    'existing_booking_id' => $conflict->id,
                    'title' => $conflict->title,
                    'start_at' => $conflict->start_at->toISOString(),
                    'end_at' => $conflict->end_at->toISOString(),
                    'status' => $conflict->status,
                ],
            ], 409); // 409 Conflict
        }

        return $this->ok(
            new BookingResource($result['booking']->load(['room.building', 'user.profile'])),
            'Pemesanan ruangan berhasil diajukan dan menunggu persetujuan admin',
            201
        );
    }

    /**
     * Display the specified booking.
     */
    public function show(Booking $booking): JsonResponse
    {
        Gate::authorize('view', $booking);

        return $this->ok(
            new BookingResource($booking->load(['user.profile', 'room.building', 'approver', 'usageLog'])),
            'Detail pemesanan berhasil diambil'
        );
    }

    /**
     * Approve booking (admin only) with transaction re-check.
     */
    public function approve(Request $request, Booking $booking): JsonResponse
    {
        Gate::authorize('approve', Booking::class);

        if ($booking->status !== 'pending') {
            return $this->fail("Pemesanan tidak dapat disetujui karena status saat ini adalah '{$booking->status}'.", 422);
        }

        $result = DB::transaction(function () use ($booking, $request) {
            // Cek apakah ada booking lain yang sudah approved pada jadwal tsb
            $conflict = Booking::where('room_id', $booking->room_id)
                ->where('id', '!=', $booking->id)
                ->where('status', 'approved')
                ->where(function ($query) use ($booking) {
                    $query->where('start_at', '<', $booking->end_at)
                        ->where('end_at', '>', $booking->start_at);
                })
                ->lockForUpdate()
                ->first();

            if ($conflict) {
                return false;
            }

            $booking->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'rejection_reason' => null,
            ]);

            return true;
        });

        if (! $result) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyetujui: Terdapat jadwal lain yang telah disetujui pada waktu yang sama.',
            ], 409);
        }

        return $this->ok(
            new BookingResource($booking->fresh(['user.profile', 'room.building', 'approver'])),
            'Pemesanan ruangan berhasil disetujui'
        );
    }

    /**
     * Reject booking (admin only).
     */
    public function reject(RejectBookingRequest $request, Booking $booking): JsonResponse
    {
        Gate::authorize('reject', Booking::class);

        if (! in_array($booking->status, ['pending', 'approved'])) {
            return $this->fail("Pemesanan dengan status '{$booking->status}' tidak dapat ditolak.", 422);
        }

        $booking->update([
            'status' => 'rejected',
            'rejection_reason' => $request->validated()['rejection_reason'],
            'approved_by' => $request->user()->id,
        ]);

        return $this->ok(
            new BookingResource($booking->fresh(['user.profile', 'room.building', 'approver'])),
            'Pemesanan ruangan berhasil ditolak'
        );
    }

    /**
     * Cancel booking (owner only).
     */
    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        Gate::authorize('cancel', $booking);

        $booking->update([
            'status' => 'cancelled',
        ]);

        return $this->ok(
            new BookingResource($booking->fresh(['user.profile', 'room.building'])),
            'Pemesanan ruangan berhasil dibatalkan'
        );
    }

    /**
     * Record check-in for an approved booking.
     */
    public function checkIn(CheckInRequest $request, Booking $booking): JsonResponse
    {
        Gate::authorize('checkIn', $booking);

        if ($booking->status !== 'approved') {
            return $this->fail("Hanya pemesanan berstatus 'approved' yang dapat melakukan check-in.", 422);
        }

        $validated = $request->validated();

        $log = UsageLog::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'checked_in_at' => Carbon::now(),
                'actual_attendees' => $validated['actual_attendees'] ?? $booking->attendees,
            ]
        );

        return $this->ok(
            new BookingResource($booking->fresh(['user.profile', 'room.building', 'usageLog'])),
            'Check-in pemakaian ruangan berhasil dicatat'
        );
    }

    /**
     * Record check-out for an approved booking and mark as completed.
     */
    public function checkOut(CheckOutRequest $request, Booking $booking): JsonResponse
    {
        Gate::authorize('checkOut', $booking);

        if ($booking->status !== 'approved') {
            return $this->fail("Hanya pemesanan berstatus 'approved' yang dapat melakukan check-out.", 422);
        }

        $validated = $request->validated();

        $log = UsageLog::firstOrCreate(['booking_id' => $booking->id]);
        $log->update([
            'checked_out_at' => Carbon::now(),
            'actual_attendees' => $validated['actual_attendees'] ?? $log->actual_attendees ?? $booking->attendees,
        ]);

        $booking->update(['status' => 'completed']);

        return $this->ok(
            new BookingResource($booking->fresh(['user.profile', 'room.building', 'usageLog'])),
            'Check-out berhasil dicatat dan status pemesanan telah selesai (completed)'
        );
    }
}
