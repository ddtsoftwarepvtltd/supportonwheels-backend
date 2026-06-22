<?php

namespace App\Http\Controllers\API\Provider;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    use ApiResponse;

    // GET /provider/availability
    public function show(): JsonResponse
    {
        $profile = auth('api')->user()->providerProfile;

        return $this->success([
            'is_online'       => $profile->is_online ?? false,
            'weekly_schedule' => $profile->weekly_schedule ?? [],
            'leave_dates'     => $profile->leave_dates ?? [],
        ]);
    }

    // PUT /provider/availability
    public function update(Request $request)
    {
        $request->validate([
            'weekly_schedule'            => 'sometimes|array',
            'weekly_schedule.*.day'      => 'required_with:weekly_schedule|in:mon,tue,wed,thu,fri,sat,sun',
            'weekly_schedule.*.start'    => 'required_with:weekly_schedule|date_format:H:i',
            'weekly_schedule.*.end'      => 'required_with:weekly_schedule|date_format:H:i',
            'leave_dates'                => 'sometimes|array',
            'leave_dates.*'              => 'date|after_or_equal:today',
        ]);

        $profile = auth()->user();
        $profile->update($request->only('weekly_schedule', 'leave_dates'));

        return $this->success($profile->fresh(), 'Availability update ho gaya');
    }

    // PUT /provider/status
    public function toggleStatus(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:online,offline',
        ]);

        $user    = auth('api')->user();
        $profile = $user->providerProfile;

        if ($profile->kyc_status !== 'approved') {
            return $this->error('KYC approved nahi hai. Online nahi ho sakte.', 403);
        }

        $profile->update(['is_online' => $request->status === 'online']);

        return $this->success([
            'status'    => $request->status,
            'is_online' => $profile->is_online,
        ], "Status {$request->status} ho gaya");
    }

    // POST /provider/location
    public function updateLocation(Request $request): JsonResponse
    {
        $request->validate([
            'lat'     => 'required|numeric|between:-90,90',
            'lng'     => 'required|numeric|between:-180,180',
            'heading' => 'sometimes|numeric',
            'speed'   => 'sometimes|numeric',
        ]);

        auth('api')->user()->providerProfile()->update([
            'last_location_lat' => $request->lat,
            'last_location_lng' => $request->lng,
            'last_location_at'  => now(),
        ]);

        // TODO: WebSocket broadcast karo (customer ko live tracking ke liye)
        // broadcast(new ProviderLocationUpdated($user->id, $request->lat, $request->lng));

        return response()->json(['success' => true]);
    }
}
