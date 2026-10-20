<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Facility\FacilityRequest;
use App\Http\Resources\FacilityResource;
use App\Models\Facility;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $facilities = Facility::when($request->filled('q'), function ($query) use ($request) {
            $query->where('name', 'like', "%{$request->query('q')}%");
        })->get();

        return $this->ok(FacilityResource::collection($facilities), 'Daftar fasilitas berhasil diambil');
    }

    public function store(FacilityRequest $request): JsonResponse
    {
        $facility = Facility::create($request->validated());

        return $this->ok(new FacilityResource($facility), 'Fasilitas berhasil ditambahkan', 201);
    }

    public function show(Facility $facility): JsonResponse
    {
        return $this->ok(new FacilityResource($facility), 'Detail fasilitas berhasil diambil');
    }

    public function update(FacilityRequest $request, Facility $facility): JsonResponse
    {
        $facility->update($request->validated());

        return $this->ok(new FacilityResource($facility), 'Fasilitas berhasil diperbarui');
    }

    public function destroy(Facility $facility): JsonResponse
    {
        $facility->rooms()->detach();
        $facility->delete();

        return $this->ok(null, 'Fasilitas berhasil dihapus');
    }
}
