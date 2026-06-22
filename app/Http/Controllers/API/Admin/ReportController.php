<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use ApiResponse;

    // GET /admin/reports/bookings?from=&to=
    public function bookingFunnel(Request $request): JsonResponse
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to   = $request->get('to', now()->toDateString());

        $stats = [
            'total_created'   => Booking::whereBetween('created_at', [$from, $to])->count(),
            'confirmed'       => Booking::whereBetween('created_at', [$from, $to])->where('status', 'confirmed')->count(),
            'in_progress'     => Booking::whereBetween('created_at', [$from, $to])->where('status', 'in_progress')->count(),
            'completed'       => Booking::whereBetween('created_at', [$from, $to])->where('status', 'completed')->count(),
            'cancelled'       => Booking::whereBetween('created_at', [$from, $to])->where('status', 'cancelled')->count(),
            'repeat_customers' => DB::table('bookings')
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('customer_id')
                ->havingRaw('COUNT(*) > 1')
                ->count(),
        ];

        return $this->success(['period' => compact('from', 'to'), 'stats' => $stats]);
    }

    // GET /admin/reports/providers?sort_by=rating|bookings|completion
    public function providerLeaderboard(Request $request): JsonResponse
    {
        $sortBy = $request->get('sort_by', 'rating');

        $providers = User::with('providerProfile')
            ->where('user_type', 'provider')
            ->whereHas('providerProfile', fn ($q) => $q->where('kyc_status', 'approved'))
            ->withCount(['bookings as completed_jobs' => fn ($q) => $q->where('status', 'completed')])
            ->orderByDesc(match($sortBy) {
                'bookings'   => 'completed_jobs',
                'completion' => 'completed_jobs',
                default      => DB::raw('(SELECT rating FROM provider_profiles WHERE user_id = users.id)'),
            })
            ->limit(50)
            ->get()
            ->map(fn ($p) => [
                'id'             => $p->id,
                'name'          => $p->name,
                'rating'        => $p->providerProfile?->rating ?? 0,
                'total_jobs'    => $p->providerProfile?->total_jobs ?? 0,
                'completed_jobs' => $p->completed_jobs,
                'acceptance_rate' => $p->providerProfile?->acceptance_rate ?? 0,
            ]);

        return $this->success($providers);
    }

    // GET /admin/reports/customers
    public function customerRetention(Request $request): JsonResponse
    {
        $month = $request->get('month', now()->month);
        $year  = $request->get('year', now()->year);

        $new      = User::where('user_type', 'customer')->whereMonth('created_at', $month)->whereYear('created_at', $year)->count();
        $returning = Booking::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) > 1')
            ->distinct('customer_id')
            ->count();

        return $this->success([
            'new_customers'       => $new,
            'returning_customers' => $returning,
            'churn_rate'          => 0, // TODO: calculate properly
        ]);
    }

    // GET /admin/reports/city
    public function cityPerformance(): JsonResponse
    {
        // TODO: Implement city-wise breakdown
        return $this->success([]);
    }

    // GET /admin/reports/revenue
    public function revenueReport(Request $request): JsonResponse
    {
        $by = $request->get('by', 'category'); // category, city, day

        if ($by === 'category') {
            $data = DB::table('bookings')
                ->join('services', 'bookings.service_id', '=', 'services.id')
                ->join('service_categories', 'services.category_id', '=', 'service_categories.id')
                ->where('bookings.payment_status', 'paid')
                ->selectRaw('service_categories.name as category, SUM(bookings.total_amount) as revenue, COUNT(*) as bookings')
                ->groupBy('service_categories.name')
                ->orderByDesc('revenue')
                ->get();

            return $this->success($data);
        }

        return $this->success([]);
    }
}
