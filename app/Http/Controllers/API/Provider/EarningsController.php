<?php

namespace App\Http\Controllers\API\Provider;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payout;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EarningsController extends Controller
{
    use ApiResponse;

    // GET /provider/earnings?period=today|week|month
    public function summary(Request $request): JsonResponse
    {
        $period    = $request->get('period', 'week');
        $providerId = auth('api')->id();

        $query = Booking::where('provider_id', $providerId)
            ->where('status', 'completed')
            ->where('payment_status', 'paid');

        $dateRange = match($period) {
            'today' => [today(), today()->endOfDay()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'lifetime' => [null, null],
            default => [now()->startOfWeek(), now()->endOfWeek()], // week
        };

        if ($dateRange[0]) {
            $query->whereBetween('completed_at', $dateRange);
        }

        $bookings = $query->get();

        $commission = 0.20; // 20% platform commission
        $grossAmount = $bookings->sum('total_amount');
        $netEarning  = round($grossAmount * (1 - $commission), 2);

        $profile = auth('api')->user()->providerProfile;

        return $this->success([
            'period'          => $period,
            'total_jobs'      => $bookings->count(),
            'gross_amount'    => $grossAmount,
            'commission'      => round($grossAmount * $commission, 2),
            'net_earning'     => $netEarning,
            'avg_per_job'     => $bookings->count() > 0 ? round($netEarning / $bookings->count(), 2) : 0,
            'rating'          => $profile->rating ?? 0,
            'acceptance_rate' => $profile->acceptance_rate ?? 0,
            'next_payout_date' => now()->next('Monday')->format('Y-m-d'),
        ]);
    }

    // GET /provider/earnings/history?page=
    public function history(Request $request): JsonResponse
    {
        $jobs = Booking::with('service:id,name')
            ->where('provider_id', auth('api')->id())
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->orderByDesc('completed_at')
            ->paginate($request->get('limit', 20));

        return $this->paginated($jobs);
    }

    // POST /provider/payout/request
    public function requestInstantPayout(): JsonResponse
    {
        $provider = auth('api')->user();
        $profile  = $provider->providerProfile;

        // Minimum balance check
        $pendingAmount = Booking::where('provider_id', $provider->id)
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->where('payout_status', 'pending')
            ->sum('total_amount') * 0.80; // 80% provider ko milega

        if ($pendingAmount < 100) {
            return $this->error('Minimum ₹100 balance chahiye instant payout ke liye', 422);
        }

        // 1.5% instant payout fee
        $fee    = round($pendingAmount * 0.015, 2);
        $netPay = round($pendingAmount - $fee, 2);

        $payout = Payout::create([
            'provider_id'   => $provider->id,
            'amount'        => $netPay,
            'gross_amount'  => $pendingAmount,
            'fee'           => $fee,
            'type'          => 'instant',
            'status'        => 'processing',
            'bank_account'  => $profile->bank_account_no,
            'bank_ifsc'     => $profile->bank_ifsc,
        ]);

        // TODO: Razorpay Payout API se transfer karo
        // RazorpayPayoutService::transfer($payout);

        return $this->success([
            'payout_id'    => $payout->id,
            'gross_amount' => $pendingAmount,
            'fee'          => $fee,
            'net_amount'   => $netPay,
            'status'       => 'processing',
        ], 'Instant payout request ho gaya. 30 minutes mein transfer ho jayega.');
    }

    // GET /provider/payout/history?page=
    public function payoutHistory(Request $request): JsonResponse
    {
        $payouts = Payout::where('provider_id', auth('api')->id())
            ->orderByDesc('created_at')
            ->paginate($request->get('limit', 15));

        return $this->paginated($payouts);
    }
}
