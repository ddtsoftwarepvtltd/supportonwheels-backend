<?php

namespace App\Http\Controllers\API\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    use ApiResponse;

    // GET /customer/addresses
    public function index(): JsonResponse
    {
        $addresses = CustomerAddress::where('user_id', auth('api')->id())
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return $this->success($addresses);
    }

    // POST /customer/addresses
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'label'        => 'required|string|max:50',  // Home, Work, Other
            'full_address' => 'required|string|max:500',
            'lat'          => 'required|numeric|between:-90,90',
            'lng'          => 'required|numeric|between:-180,180',
            'city'         => 'sometimes|string|max:100',
            'pincode'      => 'sometimes|string|max:10',
        ]);

        $userId = auth('api')->id();

        // Default flag — pehla address automatically default hoga
        $isFirst = CustomerAddress::where('user_id', $userId)->count() === 0;

        $address = CustomerAddress::create([
            'user_id'      => $userId,
            'label'        => $request->label,
            'full_address' => $request->full_address,
            'lat'          => $request->lat,
            'lng'          => $request->lng,
            'city'         => $request->city,
            'pincode'      => $request->pincode,
            'is_default'   => $isFirst,
        ]);

        return $this->success($address, 'Address add ho gaya', 201);
    }

    // PUT /customer/addresses/{id}
    public function update(Request $request, $id): JsonResponse
    {
        $address = CustomerAddress::where('id', $id)
            ->where('user_id', auth('api')->id())
            ->first();

        if (!$address) {
            return $this->error('Address nahi mila', 404);
        }

        $request->validate([
            'label'        => 'sometimes|string|max:50',
            'full_address' => 'sometimes|string|max:500',
            'lat'          => 'sometimes|numeric|between:-90,90',
            'lng'          => 'sometimes|numeric|between:-180,180',
            'city'         => 'sometimes|string|max:100',
            'pincode'      => 'sometimes|string|max:10',
        ]);

        $address->update($request->only('label', 'full_address', 'lat', 'lng', 'city', 'pincode'));

        return $this->success($address, 'Address update ho gaya');
    }

    // DELETE /customer/addresses/{id}
    public function destroy($id): JsonResponse
    {
        $address = CustomerAddress::where('id', $id)
            ->where('user_id', auth('api')->id())
            ->first();

        if (!$address) {
            return $this->error('Address nahi mila', 404);
        }

        $address->delete();

        return $this->success(null, 'Address delete ho gaya');
    }

    // PATCH /customer/addresses/{id}/default
    public function setDefault($id): JsonResponse
    {
        $userId = auth('api')->id();

        $address = CustomerAddress::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$address) {
            return $this->error('Address nahi mila', 404);
        }

        // Sabhi addresses ka default hata do
        CustomerAddress::where('user_id', $userId)->update(['is_default' => false]);

        // Is address ko default karo
        $address->update(['is_default' => true]);

        return $this->success($address, 'Default address set ho gaya');
    }
}
