<?php

namespace App\Http\Controllers\API\Customer;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceSlot;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    use ApiResponse;

    // GET /customer/services/categories
    public function categories(): JsonResponse
    {
        $categories = ServiceCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return $this->success($categories);
    }

    // GET /customer/services?category_id=&lat=&lng=&page=&limit=
    public function index(Request $request): JsonResponse
    {
        $query = Service::with('category')
            ->where('is_active', true);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('tag')) {
            $query->where('tag', $request->tag);
        }

        // Price range filter
        if ($request->filled('min_price')) {
            $query->where('base_price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('base_price', '<=', $request->max_price);
        }

        $services = $query->paginate($request->get('limit', 20));

        return $this->paginated($services);
    }

    // GET /customer/services/search?q=&lat=&lng=&filters=
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q'   => 'required|string|min:2|max:100',
            'lat' => 'sometimes|numeric',
            'lng' => 'sometimes|numeric',
        ]);

        $q = $request->q;

        $services = Service::with('category')
            ->where('is_active', true)
            ->where(function ($query) use ($q) {
                $query->where('name', 'ilike', "%{$q}%")
                      ->orWhere('description', 'ilike', "%{$q}%")
                      ->orWhere('tag', 'ilike', "%{$q}%")
                      ->orWhereHas('category', function ($q2) use ($q) {
                          $q2->where('name', 'ilike', "%{$q}%");
                      });
            })
            ->limit(20)
            ->get();

        return $this->success($services);
    }

    // GET /customer/services/{id}
    public function show($id): JsonResponse
    {
        $service = Service::with(['category', 'reviews' => function ($q) {
            $q->with('customer:id,name,profile_photo')
              ->latest()
              ->limit(10);
        }])->find($id);

        if (!$service) {
            return $this->error('Service nahi mili', 404);
        }

        // Rating stats calculate karo
        $ratingStats = [
            'average'      => $service->avg_rating ?? 0,
            'total_reviews' => $service->reviews()->count(),
            'distribution' => [
                5 => $service->reviews()->where('rating', 5)->count(),
                4 => $service->reviews()->where('rating', 4)->count(),
                3 => $service->reviews()->where('rating', 3)->count(),
                2 => $service->reviews()->where('rating', 2)->count(),
                1 => $service->reviews()->where('rating', 1)->count(),
            ],
        ];

        return $this->success(array_merge($service->toArray(), [
            'rating_stats' => $ratingStats,
        ]));
    }

    // GET /customer/services/{id}/slots?date=&lat=&lng=
    public function availableSlots(Request $request, $id): JsonResponse
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'lat'  => 'sometimes|numeric',
            'lng'  => 'sometimes|numeric',
        ]);

        $service = Service::find($id);
        if (!$service) {
            return $this->error('Service nahi mili', 404);
        }

        // Available time slots fetch karo
        $slots = ServiceSlot::where('service_id', $id)
            ->where('date', $request->date)
            ->where('is_available', true)
            ->whereColumn('booked_count', '<', 'max_bookings')
            ->get(['id', 'time_start', 'time_end', 'booked_count', 'max_bookings']);

        return $this->success([
            'date'    => $request->date,
            'service' => $service->name,
            'slots'   => $slots,
        ]);
    }

    // GET /customer/services/{id}/providers?lat=&lng=
    public function providers(Request $request, $id): JsonResponse
    {
        $service = Service::find($id);
        if (!$service) {
            return $this->error('Service nahi mili', 404);
        }

        $providers = User::with('providerProfile')
            ->where('user_type', 'provider')
            ->where('status', 'active')
            ->whereHas('providerProfile', function ($q) use ($id) {
                $q->where('kyc_status', 'approved')
                  ->where('is_online', true)
                  ->whereJsonContains('service_ids', (int)$id);
            })
            ->orderByDesc('providerProfile.rating')
            ->limit(10)
            ->get()
            ->map(function ($p) {
                return [
                    'id'          => $p->id,
                    'name'        => $p->name,
                    'photo'       => $p->profile_photo,
                    'rating'      => $p->providerProfile->rating ?? 0,
                    'total_jobs'  => $p->providerProfile->total_jobs ?? 0,
                    'is_featured' => $p->providerProfile->is_featured ?? false,
                ];
            });

        return $this->success($providers);
    }
}
