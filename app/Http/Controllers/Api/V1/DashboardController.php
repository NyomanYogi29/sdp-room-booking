<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Building;
use App\Models\Room;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Get summary metrics for admin dashboard.
     */
    public function summary(Request $request): JsonResponse
    {
        Gate::authorize('view-dashboard');

        $now = Carbon::now();
        $today = $now->copy()->startOfDay();

        $totalRooms = Room::count();
        $activeRooms = Room::where('status', 'active')->count();
        $maintenanceRooms = Room::where('status', 'maintenance')->count();

        $bookingsToday = Booking::whereDate('start_at', $today)->count();
        $pendingBookings = Booking::where('status', 'pending')->count();
        $approvedBookings = Booking::where('status', 'approved')->count();

        $ongoingNow = Booking::where('status', 'approved')
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->count();

        return $this->ok([
            'rooms' => [
                'total' => $totalRooms,
                'active' => $activeRooms,
                'maintenance' => $maintenanceRooms,
            ],
            'bookings' => [
                'today' => $bookingsToday,
                'pending' => $pendingBookings,
                'approved' => $approvedBookings,
                'ongoing_now' => $ongoingNow,
            ],
        ], 'Ringkasan dashboard berhasil diambil');
    }

    /**
     * Get room utilization statistics.
     */
    public function utilization(Request $request): JsonResponse
    {
        Gate::authorize('view-dashboard');

        $mostPopularRooms = Room::withCount('bookings')
            ->orderBy('bookings_count', 'desc')
            ->take(5)
            ->get(['id', 'code', 'name', 'type', 'capacity', 'bookings_count']);

        $buildingStats = Building::withCount('rooms')
            ->get(['id', 'code', 'name', 'rooms_count']);

        return $this->ok([
            'most_popular_rooms' => $mostPopularRooms,
            'buildings' => $buildingStats,
        ], 'Statistik utilisasi ruangan berhasil diambil');
    }
}
