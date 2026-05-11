<?php
// app/Http/Resources/BeneficiaryResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BeneficiaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'member_id'              => $this->member_id,
            'type'                   => $this->type,
            'is_primary'             => $this->is_primary,

            // Name
            'first_name'             => $this->first_name,
            'last_name'              => $this->last_name,
            'middle_name'            => $this->middle_name,
            'full_name'              => $this->full_name,

            // Details
            'relationship'           => $this->relationship,
            'birthdate'              => $this->birthdate?->toDateString(),
            'age'                    => $this->age,
            'is_minor'               => $this->is_minor,
            'share_percentage'       => $this->share_percentage,
            'contact_number'         => $this->contact_number,
            'address'                => $this->address,

            // Guardian (populated for minors)
            'guardian_name'          => $this->guardian_name,
            'guardian_contact'       => $this->guardian_contact,
            'guardian_relationship'  => $this->guardian_relationship,

            'sort_order'             => $this->sort_order,
            'created_at'             => $this->created_at?->toIso8601String(),
            'updated_at'             => $this->updated_at?->toIso8601String(),
        ];
    }
}
