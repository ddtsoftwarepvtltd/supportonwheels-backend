<?php

namespace App\Http\Controllers\API\Provider;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    use ApiResponse;

    // GET /provider/profile
    public function show(): JsonResponse
    {
        $user = auth('api')->user();
        $user->load('providerProfile');

        return $this->success([
            'id'             => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'phone'          => $user->phone,
            'profile_photo'  => $user->profile_photo,
            'status'         => $user->status,
            'profile'        => $user->providerProfile,
        ]);
    }

    // PUT /provider/profile
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'name'              => 'sometimes|string|max:100',
            'email'             => 'sometimes|email|unique:users,email,' . auth('api')->id(),
            'bio'               => 'sometimes|string|max:1000',
            'service_ids'       => 'sometimes|array',
            'service_ids.*'     => 'exists:services,id',
            'bank_account_no'   => 'sometimes|string|max:50',
            'bank_ifsc'         => 'sometimes|string|max:11',
        ]);

        $user = auth('api')->user();
        $user->update($request->only('name', 'email'));

        $profileData = $request->only('bio', 'service_ids', 'bank_account_no', 'bank_ifsc');
        if (!empty($profileData)) {
            $user->providerProfile()->update($profileData);
        }

        return $this->success($user->fresh(['providerProfile']), 'Profile update ho gaya');
    }

    // POST /provider/profile/photo
    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $user = auth('api')->user();
        $path = $request->file('photo')->store("provider_photos/{$user->id}", 'public');
        $url  = Storage::url($path);

        $user->update(['profile_photo' => $url]);

        return $this->success(['photo_url' => $url], 'Photo upload ho gaya');
    }

    // POST /provider/kyc/documents
    public function uploadKyc(Request $request): JsonResponse
    {
        $request->validate([
            'government_id'             => 'sometimes|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'police_verification'       => 'sometimes|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'skills_certificate'        => 'sometimes|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $user    = auth('api')->user();
        $profile = $user->providerProfile;
        $uploads = [];

        foreach (['government_id', 'police_verification', 'skills_certificate'] as $doc) {
            if ($request->hasFile($doc)) {
                $path = $request->file($doc)->store("kyc/{$user->id}", 'public');
                $uploads[$doc . '_url'] = Storage::url($path);
            }
        }

        if (!empty($uploads)) {
            $profile->update($uploads);
        }

        // KYC pending mein set karo
        $profile->update(['kyc_status' => 'pending']);

        return $this->success($profile->fresh(), 'Documents upload ho gaye. Admin review karega.');
    }
}
