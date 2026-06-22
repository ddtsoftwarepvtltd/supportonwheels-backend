<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingNote;
use App\Models\BookingStatusHistory;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    use ApiResponse;

    // GET /admin/bookings?status=&date_from=&date_to=&service_type=&city=&provider=&search=&page=
    public function index(Request $request): JsonResponse
    {
        $query = Booking::with([
            'customer:id,name,phone',
            'provider:id,name',
            'service:id,name',
        ])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('scheduled_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('scheduled_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('id', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn ($q2) => $q2->where('name', 'ilike', "%{$s}%"))
                  ->orWhereHas('provider', fn ($q2) => $q2->where('name', 'ilike', "%{$s}%"));
            });
        }

        $bookings = $query->paginate($request->get('per_page', 20));

        return $this->paginated($bookings);
    }

    // GET /admin/bookings/{id}
    public function show($id): JsonResponse
    {
        $booking = Booking::with([
            'customer',
            'provider',
            'service',
            'address',
            'payment',
            'review',
            'statusHistory',
            'notes',
        ])->find($id);

        if (!$booking) {
            return $this->error('Booking nahi mili', 404);
        }

        return $this->success($booking);
    }

    // POST /admin/bookings/{id}/reassign
    public function reassign(Request $request, $id): JsonResponse
    {
        $request->validate([
            'provider_id' => 'required|exists:users,id',
            'reason'      => 'required|string|max:500',
        ]);

        $booking = Booking::find($id);
        if (!$booking) {
            return $this->error('Booking nahi mili', 404);
        }

        $oldProviderId = $booking->provider_id;
        $booking->update(['provider_id' => $request->provider_id]);

        BookingNote::create([
            'booking_id' => $booking->id,
            'admin_id'   => auth('api')->id(),
            'note'       => "Reassigned from provider #{$oldProviderId} to #{$request->provider_id}. Reason: {$request->reason}",
        ]);

        // TODO: New provider ko notify karo
        return $this->success($booking->fresh(), 'Booking reassign ho gaya');
    }

    // POST /admin/bookings/{id}/cancel
    public function forceCancel(Request $request, $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $booking = Booking::whereNotIn('status', ['completed', 'cancelled'])->find($id);
        if (!$booking) {
            return $this->error('Booking nahi mili ya cancel nahi ho sakti', 404);
        }

        DB::beginTransaction();
        try {
            $booking->update([
                'status'              => 'cancelled',
                'cancelled_by'        => 'admin',
                'cancellation_reason' => $request->reason,
            ]);

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'status'     => 'cancelled',
                'changed_by' => auth('api')->id(),
                'note'       => 'Admin force cancel: ' . $request->reason,
            ]);

            // Auto refund trigger karo
            // RefundService::processAutoRefund($booking);

            DB::commit();
            return $this->success($booking, 'Booking cancel ho gaya. Refund process ho raha hai.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Cancel fail ho gaya', 500);
        }
    }

    // POST /admin/bookings/{id}/note
    public function addNote(Request $request, $id): JsonResponse
    {
        $request->validate(['note' => 'required|string|max:2000']);

        $booking = Booking::find($id);
        if (!$booking) {
            return $this->error('Booking nahi mili', 404);
        }

        $note = BookingNote::create([
            'booking_id' => $booking->id,
            'admin_id'   => auth('api')->id(),
            'note'       => $request->note,
        ]);

        return $this->success($note, 'Note add ho gaya', 201);
    }

    // GET /admin/bookings/{id}/chat
    public function chatTranscript($id): JsonResponse
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return $this->error('Booking nahi mili', 404);
        }

        // TODO: Chat messages fetch karo
        // $messages = ChatMessage::where('booking_id', $id)->with('sender:id,name,user_type')->get();

        return $this->success([], 'Chat transcript');
    }

    // GET /admin/bookings/export?format=csv|excel
    public function export(Request $request): \Illuminate\Http\Response
    {
        // TODO: Export implement karo (Laravel Excel)
        return response()->json(['message' => 'Export feature coming soon']);
    }
}
