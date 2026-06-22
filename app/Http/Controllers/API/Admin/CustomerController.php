<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ApiResponse;

    // GET /admin/customers?search=&city=&page=
    public function index(Request $request): JsonResponse
    {
        $query = User::with('participantProfile')
            ->where('user_type', 'customer')
            ->withCount('bookings');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('name', 'ilike', "%{$s}%")
                ->orWhere('email', 'ilike', "%{$s}%")
                ->orWhere('phone', 'ilike', "%{$s}%"));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $customers = $query->latest()->paginate($request->get('per_page', 20));

        return $this->paginated($customers);
    }

    // GET /admin/customers/{id}
    public function show($id): JsonResponse
    {
        $customer = User::with([
            'participantProfile',
            'bookings' => fn ($q) => $q->latest()->limit(20),
        ])->where('user_type', 'customer')->find($id);

        if (!$customer) {
            return $this->error('Customer nahi mila', 404);
        }

        $totalSpend = $customer->bookings()->where('payment_status', 'paid')->sum('total_amount');

        return $this->success(array_merge($customer->toArray(), ['total_spend' => $totalSpend]));
    }

    // PATCH /admin/customers/{id}/suspend
    public function suspend(Request $request, $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $customer = User::where('user_type', 'customer')->find($id);
        if (!$customer) return $this->error('Customer nahi mila', 404);
        $customer->update(['status' => 'blocked']);
        return $this->success(null, 'Customer suspend ho gaya');
    }

    // PATCH /admin/customers/{id}/activate
    public function activate($id): JsonResponse
    {
        $customer = User::where('user_type', 'customer')->find($id);
        if (!$customer) return $this->error('Customer nahi mila', 404);
        $customer->update(['status' => 'active']);
        return $this->success(null, 'Customer activate ho gaya');
    }

    // POST /admin/customers/{id}/credit
    public function addCredit(Request $request, $id): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:10000',
            'reason' => 'required|string|max:500',
        ]);

        $customer = User::where('user_type', 'customer')->find($id);
        if (!$customer) return $this->error('Customer nahi mila', 404);

        // TODO: Wallet credit implement karo
        return $this->success(null, "₹{$request->amount} wallet credit add ho gaya");
    }

    // GET /admin/customers/{id}/tickets
    public function tickets($id): JsonResponse
    {
        // TODO: Support tickets implement karo
        return $this->success([], 'Tickets');
    }
}
