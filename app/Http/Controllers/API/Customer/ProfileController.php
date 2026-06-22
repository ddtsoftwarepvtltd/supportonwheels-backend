<?php

namespace App\Http\Controllers\API\Customer;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    use ApiResponse;

    // GET /customer/profile
    public function show(): JsonResponse
    {
        $user = auth('api')->user();
        $user->load('participantProfile');

        return $this->success([
            'id'            => $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'phone'         => $user->phone,
            'profile_photo' => $user->profile_photo,
            'status'        => $user->status,
            'profile'       => $user->participantProfile,
        ]);
    }

    // PUT /customer/profile
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'name'  => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . auth('api')->id(),
            'phone' => 'sometimes|string|max:15',
        ]);

        $user = auth('api')->user();
        $user->update($request->only('name', 'email', 'phone'));

        return $this->success($user->fresh(), 'Profile update ho gaya');
    }

    // POST /customer/profile/photo
    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $user = auth('api')->user();
        $path = $request->file('photo')->store("profile_photos/{$user->id}", 'public');
        $url  = Storage::url($path);

        $user->update(['profile_photo' => $url]);

        return $this->success(['photo_url' => $url], 'Photo upload ho gaya');
    }

    // DELETE /customer/account
    public function deleteAccount(): JsonResponse
    {
        $user = auth('api')->user();

        // Soft delete — 30 days ke baad permanently delete hoga
        $user->update(['status' => 'deleted', 'deleted_at' => now()]);
        auth('api')->logout();

        return $this->success(null, 'Account delete ho gaya. 30 din tak recover kar sakte ho.');
    }
}
