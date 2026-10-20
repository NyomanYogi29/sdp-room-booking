<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AcademicSlotResource;
use App\Models\AcademicSlot;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class AcademicSlotController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of official academic SKS slots (A s.d. O).
     */
    public function index(): JsonResponse
    {
        $slots = AcademicSlot::orderBy('order')->get();

        return $this->ok(
            AcademicSlotResource::collection($slots),
            'Daftar slot waktu akademik SKS resmi Undiksha berhasil diambil'
        );
    }
}
