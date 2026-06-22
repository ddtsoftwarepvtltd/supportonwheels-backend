<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Models\Payment;
use App\Models\Review;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponse;

    // GET /admin/dashboard
    public function index(): JsonResponse
    {
        $today = today();

        // Today's booking stats
        $todayBookings = Booking::whereDate('created_at', $today);

        // Revenue
        $todayRevenue   = Payment::where('status', 'success')->whereDate('created_at', $today)->sum('amount');
        $weekRevenue    = Payment::where('status', 'success')->whereBetween('created_at', [now()->startOfWeek(), now()])->sum('amount');
        $monthRevenue   = Payment::where('status', 'success')->whereMonth('created_at', now()->month)->sum('amount');

        // Provider stats
        $onlineProviders = User::whereHas('providerProfile', function ($q) {
            $q->where('is_online', true);
        })->count();

        return $this->success([
            'bookings' => [
                'today_total'      => (clone $todayBookings)->count(),
                'today_completed'  => (clone $todayBookings)->where('status', 'completed')->count(),
                'today_inprogress' => (clone $todayBookings)->whereIn('status', ['confirmed', 'en_route', 'arrived', 'in_progress'])->count(),
                'today_cancelled'  => (clone $todayBookings)->where('status', 'cancelled')->count(),
            ],
            'revenue' => [
                'today'  => $todayRevenue,
                'week'   => $weekRevenue,
                'month'  => $monthRevenue,
            ],
            'providers' => [
                'online_now'     => $onlineProviders,
                'jobs_inprogress' => Booking::whereIn('status', ['in_progress'])->count(),
                'kyc_pending'     => User::whereHas('providerProfile', function ($q) {
                    $q->where('kyc_status', 'pending');
                })->count(),
            ],
            'customers' => [
                'new_today'   => User::where('user_type', 'customer')->whereDate('created_at', $today)->count(),
                'total'       => User::where('user_type', 'customer')->count(),
            ],
            'alerts' => [
                'pending_disputes' => 0, // TODO: Disputes model
                'flagged_reviews'  => Review::where('is_flagged', true)->count(),
                'kyc_queue'        => User::whereHas('providerProfile', function ($q) {
                    $q->where('kyc_status', 'pending');
                })->count(),
            ],
        ]);
    }

    // GET /admin/dashboard/map
    public function mapData(): JsonResponse
    {
        // Online providers ka location
        $providers = User::with('providerProfile:user_id,last_location_lat,last_location_lng,is_online')
            ->where('user_type', 'provider')
            ->whereHas('providerProfile', fn ($q) => $q->where('is_online', true))
            ->get(['id', 'name'])
            ->map(fn ($p) => [
                'id'   => $p->id,
                'name' => $p->name,
                'lat'  => $p->providerProfile?->last_location_lat,
                'lng'  => $p->providerProfile?->last_location_lng,
                'type' => 'provider',
            ]);

        // Active bookings
        $bookings = Booking::with('address:id,lat,lng')
            ->whereIn('status', ['confirmed', 'en_route', 'arrived', 'in_progress'])
            ->get(['id', 'status', 'address_id'])
            ->map(fn ($b) => [
                'id'     => $b->id,
                'status' => $b->status,
                'lat'    => $b->address?->lat,
                'lng'    => $b->address?->lng,
                'type'   => 'booking',
            ]);

        return $this->success([
            'providers' => $providers,
            'bookings'  => $bookings,
        ]);
    }

    // GET /admin/dashboard/alerts
    public function alerts(): JsonResponse
    {
        return $this->success([
            'kyc_pending' => User::whereHas('providerProfile', fn ($q) => $q->where('kyc_status', 'pending'))
                ->select('id', 'name', 'created_at')
                ->latest()
                ->limit(10)
                ->get(),
            'flagged_reviews' => Review::with(['customer:id,name', 'provider:id,name'])
                ->where('is_flagged', true)
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }
}
