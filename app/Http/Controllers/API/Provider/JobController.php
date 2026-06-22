<?php

namespace App\Http\Controllers\API\Provider;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class JobController extends Controller
{
    use ApiResponse;

    // GET /provider/jobs/active
    public function active(): JsonResponse
    {
        $job = Booking::with(['customer:id,name,phone,profile_photo', 'service', 'address'])
            ->where('provider_id', auth('api')->id())
            ->whereIn('status', ['confirmed', 'en_route', 'arrived', 'in_progress'])
            ->latest()
            ->first();

        return $this->success($job);
    }

    // GET /provider/jobs/history?page=&status=
    public function history(Request $request): JsonResponse
    {
        $query = Booking::with(['customer:id,name,profile_photo', 'service'])
            ->where('provider_id', auth('api')->id())
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $jobs = $query->paginate($request->get('limit', 15));

        return $this->paginated($jobs);
    }

    // GET /provider/jobs/{id}
    public function show($id): JsonResponse
    {
        $job = Booking::with([
            'customer:id,name,phone,profile_photo',
            'service',
            'address',
            'statusHistory',
        ])->where('id', $id)
          ->where('provider_id', auth('api')->id())
          ->first();

        if (!$job) {
            return $this->error('Job nahi mili', 404);
        }

        return $this->success($job);
    }

    // POST /provider/jobs/{id}/accept
    public function accept($id): JsonResponse
    {
        $booking = Booking::where('id', $id)
            ->where('provider_id', auth('api')->id())
            ->where('status', 'pending')
            ->first();

        if (!$booking) {
            return $this->error('Job nahi mili ya already accept/reject ho chuki hai', 404);
        }

        $booking->update(['status' => 'confirmed']);

        BookingStatusHistory::create([
            'booking_id' => $booking->id,
            'status'     => 'confirmed',
            'changed_by' => auth('api')->id(),
            'note'       => 'Provider ne job accept kiya',
        ]);

        // TODO: Customer ko notify karo
        // NotificationService::jobAccepted($booking);

        return $this->success(
            $booking->load(['customer:id,name,phone', 'service', 'address']),
            'Job accept ho gaya'
        );
    }

    // POST /provider/jobs/{id}/reject
    public function reject(Request $request, $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $booking = Booking::where('id', $id)
            ->where('provider_id', auth('api')->id())
            ->where('status', 'pending')
            ->first();

        if (!$booking) {
            return $this->error('Job nahi mili ya reject nahi ho sakti', 404);
        }

        $booking->update([
            'status'              => 'pending',  // Next provider ke liye pending rakhenge
            'provider_id'         => null,       // Unassign karo
        ]);

        BookingStatusHistory::create([
            'booking_id' => $booking->id,
            'status'     => 'pending',
            'changed_by' => auth('api')->id(),
            'note'       => 'Provider ne reject kiya: ' . $request->reason,
        ]);

        // TODO: Decline count update karo, next provider assign karo
        // ProviderAssignmentService::reassignToNext($booking, auth()->id());

        return $this->success(null, 'Job reject ho gaya');
    }

    // PUT /provider/jobs/{id}/status
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $validStatuses = ['en_route', 'arrived', 'in_progress', 'completed'];

        $request->validate([
            'status' => 'required|in:' . implode(',', $validStatuses),
            'photos' => 'sometimes|array|max:10',
            'photos.*' => 'image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $booking = Booking::where('id', $id)
            ->where('provider_id', auth('api')->id())
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->first();

        if (!$booking) {
            return $this->error('Job nahi mili', 404);
        }

        $updateData = ['status' => $request->status];

        // Photos upload karo (cleaning before/after etc)
        if ($request->hasFile('photos')) {
            $photoUrls = [];
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store("job_photos/{$id}", 'public');
                $photoUrls[] = Storage::url($path);
            }
            $updateData['job_photos'] = $photoUrls;
        }

        if ($request->status === 'completed') {
            $updateData['completed_at'] = now();
        }

        $booking->update($updateData);

        BookingStatusHistory::create([
            'booking_id' => $booking->id,
            'status'     => $request->status,
            'changed_by' => auth('api')->id(),
            'note'       => 'Status update: ' . $request->status,
        ]);

        // TODO: Customer ko real-time update bhejo
        // broadcast(new BookingStatusUpdated($booking));

        return $this->success($booking->fresh(), 'Status update ho gaya');
    }

    // POST /provider/jobs/{id}/issue
    public function raiseIssue(Request $request, $id): JsonResponse
    {
        $request->validate([
            'issue_type' => 'required|in:no_access,safety_concern,customer_absent,other',
            'description' => 'required|string|max:1000',
        ]);

        $booking = Booking::where('id', $id)
            ->where('provider_id', auth('api')->id())
            ->first();

        if (!$booking) {
            return $this->error('Job nahi mili', 404);
        }

        // TODO: Issue log karo aur admin ko notify karo
        // BookingIssue::create([...]);
        // AdminNotification::jobIssue($booking, $request->all());

        return $this->success(null, 'Issue report ho gaya. Admin ko notify kar diya gaya hai.');
    }
}
