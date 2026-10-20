<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Policies\BookingPolicy;
use App\Policies\RoomPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Room::class, RoomPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);

        Gate::define('manage-master-data', fn (User $user) => $user->isAdmin());
        Gate::define('approve-booking', fn (User $user) => $user->isAdmin());
        Gate::define('view-dashboard', fn (User $user) => $user->isAdmin());
    }
}
