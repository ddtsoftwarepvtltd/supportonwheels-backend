<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $coupons = Coupon::withCount('usages')->latest()->paginate($request->get('per_page', 20));
        return $this->paginated($coupons);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code'              => 'required|string|max:20|unique:coupons,code',
            'discount_type'     => 'required|in:percentage,flat',
            'discount_value'    => 'required|numeric|min:1',
            'max_discount_cap'  => 'sometimes|nullable|numeric',
            'min_order_amount'  => 'sometimes|nullable|numeric',
            'usage_limit'       => 'sometimes|nullable|integer',
            'per_user_limit'    => 'sometimes|nullable|integer|min:1',
            'expires_at'        => 'required|date|after:today',
            'is_new_user_only'  => 'sometimes|boolean',
        ]);

        $coupon = Coupon::create($request->all() + ['is_active' => true, 'used_count' => 0]);
        return $this->success($coupon, 'Coupon create ho gaya', 201);
    }

    public function show($id): JsonResponse
    {
        $coupon = Coupon::withCount('usages')->find($id);
        return $coupon ? $this->success($coupon) : $this->error('Coupon nahi mila', 404);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $coupon = Coupon::find($id);
        if (!$coupon) return $this->error('Coupon nahi mila', 404);
        $coupon->update($request->all());
        return $this->success($coupon, 'Coupon update ho gaya');
    }

    public function destroy($id): JsonResponse
    {
        $coupon = Coupon::find($id);
        if (!$coupon) return $this->error('Coupon nahi mila', 404);
        $coupon->update(['is_active' => false]);
        return $this->success(null, 'Coupon deactivate ho gaya');
    }

    // GET /admin/coupons/{id}/stats
    public function stats($id): JsonResponse
    {
        $coupon = Coupon::withCount('usages')->find($id);
        if (!$coupon) return $this->error('Coupon nahi mila', 404);

        $totalDiscount = $coupon->usages()->sum('discount_amount');

        return $this->success([
            'code'            => $coupon->code,
            'total_uses'      => $coupon->usages_count,
            'usage_limit'     => $coupon->usage_limit,
            'total_discount'  => $totalDiscount,
            'is_active'       => $coupon->is_active,
            'expires_at'      => $coupon->expires_at,
        ]);
    }
}
