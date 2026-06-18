<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Booking;
use App\Models\ParticipantProfile;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{


    // =====================
    // REGISTER
    // =====================
    public function register(RegisterRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            // User banao
            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'phone'     => $request->phone,
                'user_type' => $request->user_type ?? 'worker',
                'status'    => 'active',
            ]);

            // Role assign karo
            $user->assignRole($user->user_type);

            // Profile banao role ke hisab se
            match ($user->user_type) {
                'worker'      => WorkerProfile::create(['user_id' => $user->id]),
                'provider'    => ProviderProfile::create(['user_id' => $user->id]),
                'participant' => ParticipantProfile::create(['user_id' => $user->id]),
            };

            // Token generate karo
            $token = auth('api')->login($user);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data'    => [
                    'user'         => $this->userResponse($user),
                    'access_token' => $token,
                    'token_type'   => 'bearer',
                    'expires_in'   => auth('api')->factory()->getTTL() * 60,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // =====================
    // LOGIN
    // =====================
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Email ya password galat hai',
            ], 401);
        }

        $user = auth('api')->user();

        // Blocked user check
        if ($user->status === 'blocked') {
            auth('api')->logout();
            return response()->json([
                'success' => false,
                'message' => 'Aapka account block kar diya gaya hai. Admin se contact karo.',
            ], 403);
        }

        // Inactive user check
        if ($user->status === 'inactive') {
            auth('api')->logout();
            return response()->json([
                'success' => false,
                'message' => 'Aapka account inactive hai.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data'    => [
                'user'         => $this->userResponse($user),
                'access_token' => $token,
                'token_type'   => 'bearer',
                'expires_in'   => auth('api')->factory()->getTTL() * 60,
            ],
        ]);
    }

    // =====================
    // ME (Profile)
    // =====================
    public function me(): JsonResponse
    {
        $user = auth('api')->user();
        $user->load(['workerProfile', 'providerProfile', 'participantProfile']);

        return response()->json([
            'success' => true,
            'data'    => $this->userResponse($user, true),
        ]);
    }

    // =====================
    // LOGOUT
    // =====================
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out',
        ]);
    }

    // =====================
    // REFRESH TOKEN
    // =====================
    public function refresh(): JsonResponse
    {
        try {
            $token = auth('api')->refresh();

            return response()->json([
                'success'      => true,
                'access_token' => $token,
                'token_type'   => 'bearer',
                'expires_in'   => auth('api')->factory()->getTTL() * 60,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed. Please login again.',
            ], 401);
        }
    }

    // =====================
    // UPDATE PROFILE
    // =====================
    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name'  => 'sometimes|string|max:100',
            'phone' => 'sometimes|nullable|string|max:15',
        ]);

        $user = auth('api')->user();
        $user->update($request->only('name', 'phone'));

        return response()->json([
            'success' => true,
            'message' => 'Profile update ho gaya',
            'data'    => $this->userResponse($user),
        ]);
    }

    // =====================
    // USER STATS
    // =====================
    public function stats(): JsonResponse
    {
        $userId = auth('api')->id();

        $active    = Booking::where('user_id', $userId)->whereIn('status', ['pending','active'])->count();
        $completed = Booking::where('user_id', $userId)->where('status', 'completed')->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'active_bookings'    => $active,
                'completed_bookings' => $completed,
                'rating'             => '4.8',
            ],
        ]);
    }

    // =====================
    // HELPER — User Response Format
    // =====================
    private function userResponse(User $user, bool $withProfile = false): array
    {
        $data = [
            'id'        => $user->id,
            'name'      => $user->name,
            'email'     => $user->email,
            'phone'     => $user->phone,
            'user_type' => $user->user_type,
            'status'    => $user->status,
            'roles'     => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ];

        if ($withProfile) {
            $data['profile'] = $user->profile;
        }

        return $data;
    }
}
