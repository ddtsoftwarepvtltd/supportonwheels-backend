<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'email'     => $this->email,
            'phone'     => $this->phone,
            'status'    => $this->status,
            'user_type' => $this->user_type,
            'profile'   => $this->whenLoaded('participantProfile', fn () => [
                'ndis_number'          => $this->participantProfile->ndis_number,
                'plan_management_type' => $this->participantProfile->plan_management_type,
                'address'              => $this->participantProfile->address,
                'suburb'               => $this->participantProfile->suburb,
                'state'                => $this->participantProfile->state,
                'postcode'             => $this->participantProfile->postcode,
                'latitude'             => $this->participantProfile->latitude,
                'longitude'            => $this->participantProfile->longitude,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
