<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Booking;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    use ApiResponse;

    // GET /admin/finance/dashboard
    public function financeDashboard(): JsonResponse
    {
        $month = now()->month;
        $year  = now()->year;

        $gross  = Payment::where('status', 'success')->whereMonth('created_at', $month)->sum('amount');
        $commission = $gross * 0.20;
        $net    = $gross - $commission;

        return $this->success([
            'this_month' => [
                'gross_revenue'      => round($gross, 2),
                'platform_commission' => round($commission, 2),
                'net_revenue'        => round($net, 2),
            ],
            'pending_payouts' => Payout::where('status', 'pending')->sum('amount'),
            'pending_refunds' => Payment::where('status', 'refunded')->whereNull('refunded_at')->count(),
        ]);
    }

    // GET /admin/finance/payouts/pending
    public function pendingPayouts(Request $request): JsonResponse
    {
        $payouts = Payout::with('provider:id,name,email')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->paginate($request->get('per_page', 20));

        return $this->paginated($payouts);
    }

    // POST /admin/finance/payouts/batch
    public function batchPayout(Request $request): JsonResponse
    {
        $pending = Payout::where('status', 'pending')->get();
        // TODO: Razorpay bulk payout
        Payout::where('status', 'pending')->update(['status' => 'processing']);
        return $this->success(['count' => $pending->count()], "{$pending->count()} payouts process ho rahe hain");
    }

    // POST /admin/finance/payouts/{id}
    public function manualPayout(Request $request, $id): JsonResponse
    {
        $payout = Payout::find($id);
        if (!$payout) return $this->error('Payout nahi mila', 404);
        $payout->update(['status' => 'processing']);
        return $this->success($payout, 'Payout initiate ho gaya');
    }

    // GET /admin/finance/refunds
    public function refundQueue(Request $request): JsonResponse
    {
        $refunds = Booking::with(['customer:id,name', 'payment'])
            ->where('status', 'cancelled')
            ->whereHas('payment', fn ($q) => $q->where('status', 'success'))
            ->whereDoesntHave('payment', fn ($q) => $q->where('status', 'refunded'))
            ->paginate($request->get('per_page', 20));

        return $this->paginated($refunds);
    }

    // POST /admin/finance/refunds
    public function processRefund(Request $request): JsonResponse
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'amount'     => 'required|numeric|min:1',
            'reason'     => 'required|string|max:500',
        ]);

        $booking = Booking::with('payment')->find($request->booking_id);
        if (!$booking || !$booking->payment) {
            return $this->error('Booking ya payment nahi mila', 404);
        }

        if ($request->amount > $booking->total_amount) {
            return $this->error('Refund amount booking amount se zyada nahi ho sakta', 422);
        }

        // TODO: Razorpay refund API
        $booking->payment->update([
            'refund_amount' => $request->amount,
            'status'        => 'refunded',
            'refunded_at'   => now(),
        ]);

        return $this->success(null, "₹{$request->amount} refund initiate ho gaya");
    }

    // GET /admin/finance/revenue?period=day|week|month
    public function revenueStats(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month');

        $data = Payment::where('status', 'success')
            ->selectRaw('DATE(created_at) as date, SUM(amount) as revenue, COUNT(*) as transactions')
            ->when($period === 'week', fn ($q) => $q->whereBetween('created_at', [now()->startOfWeek(), now()]))
            ->when($period === 'month', fn ($q) => $q->whereMonth('created_at', now()->month))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $this->success($data);
    }
}
