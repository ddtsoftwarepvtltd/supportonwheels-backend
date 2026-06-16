<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderResource extends JsonResource
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
            'profile'   => $this->whenLoaded('providerProfile', fn () => [
                'organisation_name'        => $this->providerProfile->organisation_name,
                'abn'                      => $this->providerProfile->abn,
                'is_ndis_registered'       => $this->providerProfile->is_ndis_registered,
                'ndis_registration_number' => $this->providerProfile->ndis_registration_number,
                'address'                  => $this->providerProfile->address,
                'suburb'                   => $this->providerProfile->suburb,
                'state'                    => $this->providerProfile->state,
                'postcode'                 => $this->providerProfile->postcode,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
