<?php

use App\Http\Controllers\Api\V1\AcademicSlotController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\BuildingController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\FacilityController;
use App\Http\Controllers\Api\V1\RoomController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // 1. Public Authentication Endpoints
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    // 2. Public Read-Only Endpoints (Browsing Ruangan, Fasilitas & Academic Slots)
    Route::get('/slots', [AcademicSlotController::class, 'index']);
    Route::get('/rooms', [RoomController::class, 'index']);
    Route::get('/rooms/{room}', [RoomController::class, 'show']);
    Route::get('/rooms/{room}/availability', [RoomController::class, 'availability']);
    Route::get('/buildings', [BuildingController::class, 'index']);
    Route::get('/facilities', [FacilityController::class, 'index']);

    // 3. Protected Endpoints (Bearer Token Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        // Auth User Actions
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);

        // Master Data CRUD (Buildings)
        Route::post('/buildings', [BuildingController::class, 'store']);
        Route::get('/buildings/{building}', [BuildingController::class, 'show']);
        Route::match(['put', 'patch'], '/buildings/{building}', [BuildingController::class, 'update']);
        Route::delete('/buildings/{building}', [BuildingController::class, 'destroy']);

        // Master Data CRUD (Facilities)
        Route::post('/facilities', [FacilityController::class, 'store']);
        Route::get('/facilities/{facility}', [FacilityController::class, 'show']);
        Route::match(['put', 'patch'], '/facilities/{facility}', [FacilityController::class, 'update']);
        Route::delete('/facilities/{facility}', [FacilityController::class, 'destroy']);

        // Master Data CRUD (Rooms with photo storage)
        Route::post('/rooms', [RoomController::class, 'store']);
        Route::post('/rooms/{room}', [RoomController::class, 'update']); // Mendukung multipart form-data upload
        Route::match(['put', 'patch'], '/rooms/{room}', [RoomController::class, 'update']);
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);

        // Bookings Flow
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::get('/bookings/{booking}', [BookingController::class, 'show']);
        Route::patch('/bookings/{booking}/approve', [BookingController::class, 'approve']);
        Route::patch('/bookings/{booking}/reject', [BookingController::class, 'reject']);
        Route::patch('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
        Route::post('/bookings/{booking}/check-in', [BookingController::class, 'checkIn']);
        Route::post('/bookings/{booking}/check-out', [BookingController::class, 'checkOut']);

        // Admin Dashboard Metrics
        Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
        Route::get('/dashboard/utilization', [DashboardController::class, 'utilization']);
    });
});
