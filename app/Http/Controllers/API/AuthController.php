<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\OtpCode;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponse;

    // ============================================================
    // SEND OTP (Customer / Provider)
    // ============================================================
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone'        => 'required|string|max:15',
            'country_code' => 'required|string|max:5',
        ]);

        $phone = $request->country_code . $request->phone;

        // 6-digit OTP generate karo
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Cache mein store karo 10 minutes ke liye
        Cache::put("otp:{$phone}", $otp, now()->addMinutes(10));

        // TODO: Twilio se OTP bhejo
        // TwilioService::sendOtp($phone, $otp);

        return $this->success([
            'phone'      => $phone,
            'expires_in' => 600,
            // Dev mode mein otp return karo — production mein hata do
            'otp_dev'    => app()->isLocal() ? $otp : null,
        ], 'OTP bhej diya gaya hai');
    }

    // ============================================================
    // VERIFY OTP (Customer / Provider)
    // ============================================================
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone'        => 'required|string|max:15',
            'country_code' => 'required|string|max:5',
            'otp'          => 'required|string|size:6',
            'user_type'    => 'sometimes|in:customer,provider',
        ]);

        $phone = $request->country_code . $request->phone;
        $cachedOtp = Cache::get("otp:{$phone}");

        if (!$cachedOtp || $cachedOtp !== $request->otp) {
            return $this->error('OTP galat hai ya expire ho gaya', 401);
        }

        // OTP use ho gaya — delete karo
        Cache::forget("otp:{$phone}");

        // User dhundo ya banao (first time registration)
        $user = User::where('phone', $phone)->first();
        $isNew = false;

        if (!$user) {
            $userType = $request->user_type ?? 'customer';
            $user = User::create([
                'phone'     => $phone,
                'user_type' => $userType,
                'status'    => 'active',
                'name'      => 'User_' . substr($phone, -4),
            ]);
            $user->assignRole($userType);
            $isNew = true;
        }

        if ($user->status === 'blocked') {
            return $this->error('Aapka account block hai. Admin se contact karein.', 403);
        }

        $token = auth('api')->login($user);

        return $this->success([
            'is_new_user'  => $isNew,
            'user'         => $this->formatUser($user),
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
        ], $isNew ? 'Registration successful' : 'Login successful');
    }

    // ============================================================
    // ADMIN LOGIN (Email + Password)
    // ============================================================
    public function adminLogin(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Email ya password galat hai', 401);
        }

        if ($user->status !== 'active') {
            return $this->error('Account active nahi hai', 403);
        }

        // 2FA ke liye temp token banao
        $tempToken = Str::random(64);
        Cache::put("2fa_temp:{$tempToken}", $user->id, now()->addMinutes(5));

        return $this->success([
            'temp_token'    => $tempToken,
            'requires_2fa'  => true,
            'message'       => '2FA code enter karein',
        ]);
    }

    // ============================================================
    // VERIFY 2FA (Admin)
    // ============================================================
    public function verify2FA(Request $request): JsonResponse
    {
        $request->validate([
            'temp_token' => 'required|string',
            'totp_code'  => 'required|string|size:6',
        ]);

        $userId = Cache::get("2fa_temp:{$request->temp_token}");

        if (!$userId) {
            return $this->error('Temp token expire ho gaya. Dobara login karein.', 401);
        }

        $user = User::find($userId);

        // TODO: TOTP verify karo (google2fa library se)
        // $valid = Google2FA::verifyKey($user->two_factor_secret, $request->totp_code);
        // Abhi ke liye dev mode mein bypass
        $valid = app()->isLocal() || $request->totp_code === '000000';

        if (!$valid) {
            return $this->error('2FA code galat hai', 401);
        }

        Cache::forget("2fa_temp:{$request->temp_token}");

        $token = auth('api')->login($user);

        return $this->success([
            'user'         => $this->formatUser($user),
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
        ], 'Admin login successful');
    }

    // ============================================================
    // REFRESH TOKEN
    // ============================================================
    public function refresh(): JsonResponse
    {
        try {
            $token = auth('api')->refresh();
            return $this->success([
                'access_token' => $token,
                'token_type'   => 'bearer',
                'expires_in'   => auth('api')->factory()->getTTL() * 60,
            ]);
        } catch (\Exception $e) {
            return $this->error('Token expire ho gaya. Dobara login karein.', 401);
        }
    }

    // ============================================================
    // LOGOUT
    // ============================================================
    public function logout(): JsonResponse
    {
        auth('api')->logout();
        return $this->success(null, 'Successfully logged out');
    }

    // ============================================================
    // ME (Current user)
    // ============================================================
    public function me(): JsonResponse
    {
        $user = auth('api')->user();
        $user->load(['workerProfile', 'providerProfile', 'participantProfile']);
        return $this->success($this->formatUser($user, true));
    }

    // ============================================================
    // HELPER — Format User Response
    // ============================================================
    private function formatUser(User $user, bool $withProfile = false): array
    {
        $data = [
            'id'             => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'phone'          => $user->phone,
            'user_type'      => $user->user_type,
            'status'         => $user->status,
            'profile_photo'  => $user->profile_photo,
            'roles'          => $user->getRoleNames(),
            'permissions'    => $user->getAllPermissions()->pluck('name'),
        ];

        if ($withProfile) {
            $data['profile'] = $user->profile;
        }

        return $data;
    }
}
