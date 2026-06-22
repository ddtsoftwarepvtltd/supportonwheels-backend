<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ProviderProfile;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProviderController extends Controller
{
    use ApiResponse;

    // GET /admin/providers?search=&status=&kyc_status=&page=
    public function index(Request $request): JsonResponse
    {
        $query = User::with('providerProfile')
            ->where('user_type', 'provider');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('name', 'ilike', "%{$s}%")
                ->orWhere('email', 'ilike', "%{$s}%")
                ->orWhere('phone', 'ilike', "%{$s}%"));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('kyc_status')) {
            $query->whereHas('providerProfile', fn ($q) => $q->where('kyc_status', $request->kyc_status));
        }

        $providers = $query->latest()->paginate($request->get('per_page', 20));

        return $this->paginated($providers);
    }

    // GET /admin/providers/kyc-queue
    public function kycQueue(): JsonResponse
    {
        $providers = User::with('providerProfile')
            ->where('user_type', 'provider')
            ->whereHas('providerProfile', fn ($q) => $q->where('kyc_status', 'pending'))
            ->orderBy('created_at')
            ->paginate(20);

        return $this->paginated($providers);
    }

    // GET /admin/providers/{id}
    public function show($id): JsonResponse
    {
        $provider = User::with([
            'providerProfile',
            'bookings' => fn ($q) => $q->latest()->limit(10),
        ])->where('user_type', 'provider')->find($id);

        if (!$provider) {
            return $this->error('Provider nahi mila', 404);
        }

        return $this->success($provider);
    }

    // PATCH /admin/providers/{id}/approve
    public function approve(Request $request, $id): JsonResponse
    {
        $request->validate([
            'service_ids' => 'sometimes|array',
            'service_ids.*' => 'exists:services,id',
        ]);

        $provider = User::where('user_type', 'provider')->find($id);
        if (!$provider) {
            return $this->error('Provider nahi mila', 404);
        }

        $updateData = ['kyc_status' => 'approved'];
        if ($request->filled('service_ids')) {
            $updateData['service_ids'] = $request->service_ids;
        }

        $provider->providerProfile()->update($updateData);
        $provider->update(['status' => 'active']);

        // TODO: Provider ko approval notification bhejo
        return $this->success($provider->fresh(['providerProfile']), 'Provider approve ho gaya');
    }

    // PATCH /admin/providers/{id}/reject
    public function reject(Request $request, $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $provider = User::where('user_type', 'provider')->find($id);
        if (!$provider) {
            return $this->error('Provider nahi mila', 404);
        }

        $provider->providerProfile()->update([
            'kyc_status'        => 'rejected',
            'rejection_reason'  => $request->reason,
        ]);

        // TODO: Rejection notification bhejo
        return $this->success(null, 'Provider reject ho gaya');
    }

    // PATCH /admin/providers/{id}/suspend
    public function suspend(Request $request, $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $provider = User::where('user_type', 'provider')->find($id);
        if (!$provider) {
            return $this->error('Provider nahi mila', 404);
        }

        $provider->update(['status' => 'inactive']);
        $provider->providerProfile()->update(['is_online' => false]);

        return $this->success(null, 'Provider suspend ho gaya');
    }

    // PATCH /admin/providers/{id}/activate
    public function activate($id): JsonResponse
    {
        $provider = User::where('user_type', 'provider')->find($id);
        if (!$provider) {
            return $this->error('Provider nahi mila', 404);
        }

        $provider->update(['status' => 'active']);
        return $this->success(null, 'Provider activate ho gaya');
    }

    // DELETE /admin/providers/{id}
    public function destroy($id): JsonResponse
    {
        // Super admin only (middleware se handle hoga)
        $provider = User::where('user_type', 'provider')->find($id);
        if (!$provider) {
            return $this->error('Provider nahi mila', 404);
        }

        $provider->delete();
        return $this->success(null, 'Provider permanently delete ho gaya');
    }

    // POST /admin/providers/{id}/payout-override
    public function manualPayout(Request $request, $id): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string|max:500',
        ]);

        $provider = User::where('user_type', 'provider')->find($id);
        if (!$provider) {
            return $this->error('Provider nahi mila', 404);
        }

        // TODO: Manual payout create karo
        return $this->success(null, "₹{$request->amount} ka manual payout initiate ho gaya");
    }

    // PATCH /admin/providers/{id}/featured
    public function toggleFeatured($id): JsonResponse
    {
        $provider = User::with('providerProfile')->where('user_type', 'provider')->find($id);
        if (!$provider) {
            return $this->error('Provider nahi mila', 404);
        }

        $current = $provider->providerProfile->is_featured ?? false;
        $provider->providerProfile()->update(['is_featured' => !$current]);

        return $this->success(null, 'Featured status ' . (!$current ? 'enable' : 'disable') . ' ho gaya');
    }
}
