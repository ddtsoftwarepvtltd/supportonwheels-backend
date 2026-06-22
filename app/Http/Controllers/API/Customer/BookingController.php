<?php

namespace App\Http\Controllers\API\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\Service;
use App\Models\CustomerAddress;
use App\Models\ServiceSlot;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    use ApiResponse;

    // GET /customer/bookings?status=&page=&limit=
    public function index(Request $request): JsonResponse
    {
        $query = Booking::with(['service', 'provider:id,name,profile_photo'])
            ->where('customer_id', auth('api')->id())
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->paginate($request->get('limit', 15));

        return $this->paginated($bookings);
    }

    // POST /customer/bookings
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'service_id'  => 'required|exists:services,id',
            'address_id'  => 'required|exists:customer_addresses,id',
            'slot_id'     => 'required|exists:service_slots,id',
            'provider_id' => 'sometimes|nullable|exists:users,id',
            'coupon_code' => 'sometimes|nullable|string',
            'notes'       => 'sometimes|nullable|string|max:1000',
        ]);

        // Address ownership check
        $address = CustomerAddress::where('id', $request->address_id)
            ->where('user_id', auth('api')->id())
            ->first();

        if (!$address) {
            return $this->error('Address nahi mila ya tumhara nahi hai', 404);
        }

        // Slot availability check
        $slot = ServiceSlot::where('id', $request->slot_id)
            ->where('service_id', $request->service_id)
            ->where('is_available', true)
            ->lockForUpdate()
            ->first();

        if (!$slot || $slot->booked_count >= $slot->max_bookings) {
            return $this->error('Ye slot available nahi hai. Dusra slot choose karein.', 409);
        }

        $service = Service::find($request->service_id);

        DB::beginTransaction();
        try {
            // Price calculation
            $baseAmount     = $service->base_price;
            $discountAmount = 0;

            if ($request->filled('coupon_code')) {
                // TODO: Coupon validate karo
                // $discount = CouponService::apply($request->coupon_code, $baseAmount, auth()->id());
                // $discountAmount = $discount->amount;
            }

            $taxAmount   = round(($baseAmount - $discountAmount) * 0.18, 2); // 18% GST
            $totalAmount = $baseAmount - $discountAmount + $taxAmount;

            // Provider assign karo
            $providerId = $request->provider_id;
            if (!$providerId) {
                // Nearest available provider auto-assign
                $nearbyProvider = User::with('providerProfile')
                    ->where('user_type', 'provider')
                    ->where('status', 'active')
                    ->whereHas('providerProfile', function ($q) use ($request) {
                        $q->where('kyc_status', 'approved')
                          ->where('is_online', true)
                          ->whereJsonContains('service_ids', (int)$request->service_id);
                    })
                    ->inRandomOrder()
                    ->first();

                $providerId = $nearbyProvider?->id;
            }

            // Booking create karo
            $booking = Booking::create([
                'customer_id'     => auth('api')->id(),
                'provider_id'     => $providerId,
                'service_id'      => $request->service_id,
                'address_id'      => $request->address_id,
                'slot_id'         => $request->slot_id,
                'scheduled_at'    => $slot->date . ' ' . $slot->time_start,
                'notes'           => $request->notes,
                'status'          => 'pending',
                'base_amount'     => $baseAmount,
                'discount_amount' => $discountAmount,
                'tax_amount'      => $taxAmount,
                'total_amount'    => $totalAmount,
                'payment_status'  => 'pending',
                'coupon_code'     => $request->coupon_code,
            ]);

            // Status history
            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'status'     => 'pending',
                'changed_by' => auth('api')->id(),
                'note'       => 'Booking created by customer',
            ]);

            // Slot count update karo
            $slot->increment('booked_count');

            // TODO: Provider ko notification bhejo
            // NotificationService::newJobRequest($providerId, $booking);

            DB::commit();

            return $this->success(
                $booking->load(['service', 'provider:id,name,profile_photo', 'address']),
                'Booking ho gaya!',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Booking fail ho gaya: ' . $e->getMessage(), 500);
        }
    }

    // GET /customer/bookings/{id}
    public function show($id): JsonResponse
    {
        $booking = Booking::with([
            'service',
            'provider:id,name,profile_photo,phone',
            'address',
            'statusHistory',
            'payment',
            'review',
        ])->where('id', $id)
          ->where('customer_id', auth('api')->id())
          ->first();

        if (!$booking) {
            return $this->error('Booking nahi mili', 404);
        }

        return $this->success($booking);
    }

    // POST /customer/bookings/{id}/cancel
    public function cancel(Request $request, $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $booking = Booking::where('id', $id)
            ->where('customer_id', auth('api')->id())
            ->first();

        if (!$booking) {
            return $this->error('Booking nahi mili', 404);
        }

        if (in_array($booking->status, ['completed', 'cancelled'])) {
            return $this->error('Ye booking cancel nahi ho sakti', 422);
        }

        // 2 ghante pehle cancel kar sakte ho
        if ($booking->scheduled_at && now()->diffInMinutes($booking->scheduled_at, false) < 120) {
            return $this->error('Booking 2 ghante se kam samay mein cancel nahi ho sakti', 422);
        }

        DB::beginTransaction();
        try {
            $booking->update([
                'status'              => 'cancelled',
                'cancelled_by'        => 'customer',
                'cancellation_reason' => $request->reason,
            ]);

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'status'     => 'cancelled',
                'changed_by' => auth('api')->id(),
                'note'       => 'Customer ne cancel kiya: ' . $request->reason,
            ]);

            // Slot free karo
            if ($booking->slot_id) {
                ServiceSlot::where('id', $booking->slot_id)->decrement('booked_count');
            }

            // TODO: Refund process karo
            // RefundService::processAutoRefund($booking);

            // TODO: Provider ko notify karo
            // NotificationService::bookingCancelled($booking);

            DB::commit();
            return $this->success($booking, 'Booking cancel ho gaya');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Cancel fail ho gaya', 500);
        }
    }

    // PUT /customer/bookings/{id}/reschedule
    public function reschedule(Request $request, $id): JsonResponse
    {
        $request->validate([
            'new_slot_id' => 'required|exists:service_slots,id',
        ]);

        $booking = Booking::where('id', $id)
            ->where('customer_id', auth('api')->id())
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        if (!$booking) {
            return $this->error('Booking nahi mili ya reschedule nahi ho sakti', 404);
        }

        $newSlot = ServiceSlot::where('id', $request->new_slot_id)
            ->where('service_id', $booking->service_id)
            ->where('is_available', true)
            ->first();

        if (!$newSlot || $newSlot->booked_count >= $newSlot->max_bookings) {
            return $this->error('Ye slot available nahi hai', 409);
        }

        DB::transaction(function () use ($booking, $newSlot) {
            // Old slot free karo
            if ($booking->slot_id) {
                ServiceSlot::where('id', $booking->slot_id)->decrement('booked_count');
            }

            $booking->update([
                'slot_id'      => $newSlot->id,
                'scheduled_at' => $newSlot->date . ' ' . $newSlot->time_start,
            ]);

            $newSlot->increment('booked_count');

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'status'     => $booking->status,
                'changed_by' => auth('api')->id(),
                'note'       => 'Customer ne reschedule kiya',
            ]);
        });

        return $this->success($booking->fresh(), 'Booking reschedule ho gaya');
    }

    // GET /customer/bookings/{id}/track
    public function track($id): JsonResponse
    {
        $booking = Booking::with('provider')
            ->where('id', $id)
            ->where('customer_id', auth('api')->id())
            ->whereIn('status', ['confirmed', 'en_route', 'arrived', 'in_progress'])
            ->first();

        if (!$booking) {
            return $this->error('Booking nahi mili ya track nahi ho sakti', 404);
        }

        $provider = $booking->provider;
        $providerProfile = $provider->providerProfile;

        return $this->success([
            'booking_status' => $booking->status,
            'provider' => [
                'id'     => $provider->id,
                'name'   => $provider->name,
                'photo'  => $provider->profile_photo,
                'rating' => $providerProfile?->rating,
            ],
            'location' => [
                'lat'      => $providerProfile?->last_location_lat,
                'lng'      => $providerProfile?->last_location_lng,
                'updated_at' => $providerProfile?->last_location_at,
            ],
            'eta_minutes' => 15, // TODO: Google Maps Directions API se real ETA
        ]);
    }
}
