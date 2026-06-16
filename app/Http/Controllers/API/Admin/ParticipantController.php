<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Participant\{StoreParticipantRequest,UpdateParticipantRequest};
use App\Http\Resources\ParticipantResource;
use App\Models\ParticipantProfile;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ParticipantController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = User::with('participantProfile')
            ->where('user_type', 'participant');

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

        $participants = $query->latest()->paginate($request->get('per_page', 15));

        return $this->success([
            'items'      => ParticipantResource::collection($participants),
            'pagination' => [
                'total'        => $participants->total(),
                'per_page'     => $participants->perPage(),
                'current_page' => $participants->currentPage(),
                'last_page'    => $participants->lastPage(),
            ],
        ], 'Participants fetched successfully');
    }

    public function store(StoreParticipantRequest $request)
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'phone'     => $request->phone,
                'user_type' => 'participant',
                'status'    => 'active',
            ]);

            $user->assignRole('participant');

            ParticipantProfile::create([
                'user_id'              => $user->id,
                'ndis_number'          => $request->ndis_number,
                'plan_management_type' => $request->plan_management_type,
                'address'              => $request->address,
                'suburb'               => $request->suburb,
                'state'                => $request->state,
                'postcode'             => $request->postcode,
                'latitude'             => $request->latitude,
                'longitude'            => $request->longitude,
            ]);

            DB::commit();

            return $this->success(
                new ParticipantResource($user->load('participantProfile')),
                'Participant created successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create participant', 500, $e->getMessage());
        }
    }

    public function show($id)
    {
        $participant = User::with('participantProfile')
            ->where('user_type', 'participant')
            ->find($id);

        if (!$participant) {
            return $this->error('Participant not found', 404);
        }

        return $this->success(new ParticipantResource($participant), 'Participant fetched successfully');
    }

    public function update(UpdateParticipantRequest $request, $id)
    {
        $participant = User::where('user_type', 'participant')->find($id);

        if (!$participant) {
            return $this->error('Participant not found', 404);
        }

        DB::beginTransaction();

        try {
            $participant->update($request->only(['name', 'email', 'phone', 'status']));

            $participant->participantProfile()->updateOrCreate(
                ['user_id' => $participant->id],
                $request->only([
                    'ndis_number', 'plan_management_type', 'address',
                    'suburb', 'state', 'postcode', 'latitude', 'longitude',
                ])
            );

            DB::commit();

            return $this->success(
                new ParticipantResource($participant->load('participantProfile')),
                'Participant updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update participant', 500, $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $participant = User::where('user_type', 'participant')->find($id);

        if (!$participant) {
            return $this->error('Participant not found', 404);
        }

        $participant->delete();

        return $this->success(null, 'Participant deleted successfully');
    }
}
