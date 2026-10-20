<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Building\BuildingRequest;
use App\Http\Resources\BuildingResource;
use App\Models\Building;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $buildings = Building::withCount('rooms')
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->query('q');
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%");
            })
            ->get();

        return $this->ok(BuildingResource::collection($buildings), 'Daftar gedung berhasil diambil');
    }

    public function store(BuildingRequest $request): JsonResponse
    {
        $building = Building::create($request->validated());

        return $this->ok(new BuildingResource($building), 'Gedung berhasil ditambahkan', 201);
    }

    public function show(Building $building): JsonResponse
    {
        return $this->ok(new BuildingResource($building->loadCount('rooms')), 'Detail gedung berhasil diambil');
    }

    public function update(BuildingRequest $request, Building $building): JsonResponse
    {
        $building->update($request->validated());

        return $this->ok(new BuildingResource($building), 'Gedung berhasil diperbarui');
    }

    public function destroy(Building $building): JsonResponse
    {
        if ($building->rooms()->exists()) {
            return $this->fail('Gedung tidak dapat dihapus karena masih memiliki data ruangan terkait.', 422);
        }

        $building->delete();

        return $this->ok(null, 'Gedung berhasil dihapus');
    }
}
