<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ApiLoginRequest;
use App\Http\Requests\Auth\ApiRegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserProfile;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Handle user registration.
     */
    public function register(ApiRegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
            ]);

            UserProfile::create([
                'user_id' => $user->id,
                'nim_nip' => $validated['nim_nip'],
                'phone' => $validated['phone'] ?? null,
                'unit' => $validated['unit'] ?? null,
            ]);

            return $user;
        });

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->ok([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->load('profile')),
        ], 'Registrasi berhasil', 201);
    }

    /**
     * Handle user login.
     */
    public function login(ApiLoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan tidak cocok dengan data kami.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->ok([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->load('profile')),
        ], 'Login berhasil');
    }

    /**
     * Handle user logout (revoke token).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->ok(null, 'Logout berhasil, token telah dicabut');
    }

    /**
     * Get authenticated user profile.
     */
    public function profile(Request $request): JsonResponse
    {
        return $this->ok(
            new UserResource($request->user()->load('profile')),
            'Data profil berhasil diambil'
        );
    }
}
