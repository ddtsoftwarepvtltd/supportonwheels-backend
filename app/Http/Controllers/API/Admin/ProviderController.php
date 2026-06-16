<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Provider\{StoreProviderRequest,UpdateProviderRequest};
use App\Http\Resources\Admin\ProviderResource;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProviderController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = User::with('providerProfile')
            ->where('user_type', 'provider');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $providers = $query->latest()->paginate($request->get('per_page', 15));

        return $this->success([
            'items'      => ProviderResource::collection($providers),
            'pagination' => [
                'total'        => $providers->total(),
                'per_page'     => $providers->perPage(),
                'current_page' => $providers->currentPage(),
                'last_page'    => $providers->lastPage(),
            ],
        ], 'Providers fetched successfully');
    }

    public function store(StoreProviderRequest $request)
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'phone'     => $request->phone,
                'user_type' => 'provider',
                'status'    => 'active',
            ]);

            $user->assignRole('provider');

            ProviderProfile::create([
                'user_id'                  => $user->id,
                'organisation_name'        => $request->organisation_name,
                'abn'                      => $request->abn,
                'is_ndis_registered'       => $request->is_ndis_registered ?? false,
                'ndis_registration_number' => $request->ndis_registration_number,
                'address'                  => $request->address,
                'suburb'                   => $request->suburb,
                'state'                    => $request->state,
                'postcode'                 => $request->postcode,
            ]);

            DB::commit();

            return $this->success(
                new ProviderResource($user->load('providerProfile')),
                'Provider created successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create provider', 500, $e->getMessage());
        }
    }

    public function show($id)
    {
        $provider = User::with('providerProfile')
            ->where('user_type', 'provider')
            ->find($id);

        if (!$provider) {
            return $this->error('Provider not found', 404);
        }

        return $this->success(new ProviderResource($provider), 'Provider fetched successfully');
    }

    public function update(UpdateProviderRequest $request, $id)
    {
        $provider = User::where('user_type', 'provider')->find($id);

        if (!$provider) {
            return $this->error('Provider not found', 404);
        }

        DB::beginTransaction();

        try {
            $provider->update($request->only(['name', 'email', 'phone', 'status']));

            $provider->providerProfile()->updateOrCreate(
                ['user_id' => $provider->id],
                $request->only([
                    'organisation_name', 'abn', 'is_ndis_registered',
                    'ndis_registration_number', 'address', 'suburb', 'state', 'postcode',
                ])
            );

            DB::commit();

            return $this->success(
                new ProviderResource($provider->load('providerProfile')),
                'Provider updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update provider', 500, $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $provider = User::where('user_type', 'provider')->find($id);

        if (!$provider) {
            return $this->error('Provider not found', 404);
        }

        $provider->delete();

        return $this->success(null, 'Provider deleted successfully');
    }
}
