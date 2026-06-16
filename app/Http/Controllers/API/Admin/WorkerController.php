<?php

namespace App\Http\Controllers\API\Admin;


use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Worker\{StoreWorkerRequest,UpdateWorkerRequest};
use App\Http\Resources\Admin\WorkerResource;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Services\QueryFilterService;

class WorkerController extends Controller
{
    use ApiResponse;
    public $filter;
    public function __construct(QueryFilterService $service)
    {
        $this->filter = $service;
    }

    public function index(Request $request)
    {
        $query = User::with('workerProfile');
        $result = $this->filter->handle($request, $query, [
                    'with'          => ['workerProfile'],
                    'searchable'    => ['name', 'email', 'phone', 'workerProfile.suburb'],
                    'exact_columns' => ['status'],
                    'filterable'    => ['status', 'state'],
                ]);

        return $this->paginated($result, WorkerResource::class, 'Workers fetched');
    }

    public function store(StoreWorkerRequest $request)
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'phone'     => $request->phone,
                'user_type' => 'worker',
                'status'    => 'active',
            ]);

            $user->assignRole('worker');

            WorkerProfile::create([
                'user_id'               => $user->id,
                'address'               => $request->address,
                'suburb'                => $request->suburb,
                'state'                 => $request->state,
                'postcode'              => $request->postcode,
                'latitude'              => $request->latitude,
                'longitude'             => $request->longitude,
                'ndis_screening_number' => $request->ndis_screening_number,
                'ndis_expiry'           => $request->ndis_expiry,
                'first_aid_cert'        => $request->first_aid_cert,
                'first_aid_expiry'      => $request->first_aid_expiry,
            ]);

            DB::commit();

            return $this->success(
                new WorkerResource($user->load('workerProfile')),
                'Worker created successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create worker', 500, $e->getMessage());
        }
    }

    public function show($id)
    {
        $worker = User::with('workerProfile')
            ->where('user_type', 'worker')
            ->find($id);

        if (!$worker) {
            return $this->error('Worker not found', 404);
        }

        return $this->success(new WorkerResource($worker), 'Worker fetched successfully');
    }

    public function update(UpdateWorkerRequest $request, $id)
    {
        $worker = User::where('user_type', 'worker')->find($id);

        if (!$worker) {
            return $this->error('Worker not found', 404);
        }

        DB::beginTransaction();

        try {
            $worker->update($request->only(['name', 'email', 'phone', 'status']));

            $worker->workerProfile()->updateOrCreate(
                ['user_id' => $worker->id],
                $request->only([
                    'address', 'suburb', 'state', 'postcode',
                    'latitude', 'longitude', 'ndis_screening_number',
                    'ndis_expiry', 'first_aid_cert', 'first_aid_expiry', 'is_online',
                ])
            );

            DB::commit();

            return $this->success(
                new WorkerResource($worker->load('workerProfile')),
                'Worker updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update worker', 500, $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $worker = User::where('user_type', 'worker')->find($id);

        if (!$worker) {
            return $this->error('Worker not found', 404);
        }

        $worker->delete();

        return $this->success(null, 'Worker deleted successfully');
    }
}
