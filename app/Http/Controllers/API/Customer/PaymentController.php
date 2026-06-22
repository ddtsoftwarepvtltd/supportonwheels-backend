<?php

namespace App\Http\Controllers\API\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Coupon;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponse;

    // POST /customer/payments/initiate
    public function initiate(Request $request): JsonResponse
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'method'     => 'required|in:upi,card,netbanking,wallet,cash',
        ]);

        $booking = Booking::where('id', $request->booking_id)
            ->where('customer_id', auth('api')->id())
            ->first();

        if (!$booking) {
            return $this->error('Booking nahi mili', 404);
        }

        if ($booking->payment_status === 'paid') {
            return $this->error('Payment pehle se ho chuki hai', 409);
        }

        // Payment record banao
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount'     => $booking->total_amount,
            'currency'   => 'INR',
            'method'     => $request->method,
            'status'     => 'initiated',
        ]);

        // TODO: Razorpay se order banao
        // $razorpay = new RazorpayService();
        // $order = $razorpay->createOrder($booking->total_amount, $payment->id);

        return $this->success([
            'payment_id'    => $payment->id,
            'amount'        => $booking->total_amount,
            'currency'      => 'INR',
            'method'        => $request->method,
            // Razorpay fields — uncomment when integrated
            // 'razorpay_order_id'  => $order->id,
            // 'razorpay_key'       => config('services.razorpay.key'),
        ], 'Payment initiate ho gaya');
    }

    // POST /customer/payments/verify
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'payment_id'        => 'required|exists:payments,id',
            'gateway_order_id'  => 'required|string',
            'gateway_payment_id' => 'required|string',
            'signature'         => 'required|string',
        ]);

        $payment = Payment::where('id', $request->payment_id)
            ->whereHas('booking', function ($q) {
                $q->where('customer_id', auth('api')->id());
            })
            ->first();

        if (!$payment) {
            return $this->error('Payment nahi mila', 404);
        }

        // TODO: Razorpay signature verify karo
        // $verified = RazorpayService::verifySignature(
        //     $request->gateway_order_id,
        //     $request->gateway_payment_id,
        //     $request->signature
        // );
        $verified = true; // dev bypass

        if (!$verified) {
            $payment->update(['status' => 'failed']);
            return $this->error('Payment verification fail ho gaya', 400);
        }

        $payment->update([
            'gateway_order_id'   => $request->gateway_order_id,
            'gateway_payment_id' => $request->gateway_payment_id,
            'status'             => 'success',
        ]);

        // Booking payment status update karo
        $payment->booking->update(['payment_status' => 'paid']);

        return $this->success($payment, 'Payment successful!');
    }

    // GET /customer/payments/history?page=&limit=
    public function history(Request $request): JsonResponse
    {
        $payments = Payment::with('booking:id,service_name,scheduled_at')
            ->whereHas('booking', function ($q) {
                $q->where('customer_id', auth('api')->id());
            })
            ->orderByDesc('created_at')
            ->paginate($request->get('limit', 15));

        return $this->paginated($payments);
    }

    // POST /customer/coupons/validate
    public function validateCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'coupon_code' => 'required|string',
            'service_id'  => 'required|exists:services,id',
            'amount'      => 'required|numeric|min:1',
        ]);

        $coupon = Coupon::where('code', strtoupper($request->coupon_code))
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();

        if (!$coupon) {
            return $this->error('Coupon code galat hai ya expire ho gaya', 404);
        }

        // Usage limit check
        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return $this->error('Is coupon ki limit khatam ho gayi', 422);
        }

        // Per user limit check
        $userUsage = $coupon->usages()->where('user_id', auth('api')->id())->count();
        if ($coupon->per_user_limit && $userUsage >= $coupon->per_user_limit) {
            return $this->error('Aap is coupon ka use pehle kar chuke hain', 422);
        }

        // Min order amount check
        if ($coupon->min_order_amount && $request->amount < $coupon->min_order_amount) {
            return $this->error("Minimum ₹{$coupon->min_order_amount} ka order chahiye is coupon ke liye", 422);
        }

        // Discount calculate karo
        $discount = 0;
        if ($coupon->discount_type === 'percentage') {
            $discount = ($request->amount * $coupon->discount_value) / 100;
            if ($coupon->max_discount_cap) {
                $discount = min($discount, $coupon->max_discount_cap);
            }
        } else {
            $discount = min($coupon->discount_value, $request->amount);
        }

        return $this->success([
            'coupon_code'     => $coupon->code,
            'discount_type'   => $coupon->discount_type,
            'discount_value'  => $coupon->discount_value,
            'discount_amount' => round($discount, 2),
            'final_amount'    => round($request->amount - $discount, 2),
        ], 'Coupon valid hai!');
    }
}
