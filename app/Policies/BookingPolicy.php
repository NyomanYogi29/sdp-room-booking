<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->isAdmin() || $booking->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return ($booking->user_id === $user->id || $user->isAdmin()) &&
            in_array($booking->status, ['pending', 'approved']) &&
            now()->lt($booking->start_at);
    }

    public function approve(User $user): bool
    {
        return $user->isAdmin();
    }

    public function reject(User $user): bool
    {
        return $user->isAdmin();
    }

    public function checkIn(User $user, Booking $booking): bool
    {
        return ($user->isAdmin() || $booking->user_id === $user->id) &&
            $booking->status === 'approved';
    }

    public function checkOut(User $user, Booking $booking): bool
    {
        return ($user->isAdmin() || $booking->user_id === $user->id) &&
            $booking->status === 'approved';
    }
}
