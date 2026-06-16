<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'status'     => $this->status,
            'user_type'  => $this->user_type,
            'profile'    => $this->whenLoaded('workerProfile', fn () => [
                'address'               => $this->workerProfile->address,
                'suburb'                => $this->workerProfile->suburb,
                'state'                 => $this->workerProfile->state,
                'postcode'              => $this->workerProfile->postcode,
                'latitude'              => $this->workerProfile->latitude,
                'longitude'             => $this->workerProfile->longitude,
                'ndis_screening_number' => $this->workerProfile->ndis_screening_number,
                'ndis_expiry'           => $this->workerProfile->ndis_expiry,
                'first_aid_cert'        => $this->workerProfile->first_aid_cert,
                'first_aid_expiry'      => $this->workerProfile->first_aid_expiry,
                'is_online'             => $this->workerProfile->is_online,
                'rating'                => $this->workerProfile->rating,
                'total_shifts'          => $this->workerProfile->total_shifts,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
