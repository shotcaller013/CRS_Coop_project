<?php
// app/Http/Resources/ShareCapitalResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShareCapitalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'member_id'        => $this->member_id,

            // Transaction
            'type'             => $this->type,
            'type_label'       => $this->type_label,
            'direction'        => $this->direction,
            'is_debit'         => $this->is_debit,
            'amount'           => $this->amount,
            'signed_amount'    => $this->signed_amount,
            'balance_after'    => $this->balance_after,

            // Meta
            'or_number'        => $this->or_number,
            'transaction_date' => $this->transaction_date?->toDateString(),
            'remarks'          => $this->remarks,
            'reference_no'     => $this->reference_no,

            // Who posted
            'posted_by'        => $this->posted_by,
            'posted_by_name'   => $this->postedBy?->name,

            'created_at'       => $this->created_at?->toIso8601String(),
            'deleted_at'       => $this->deleted_at?->toIso8601String(),
            'is_voided'        => !is_null($this->deleted_at),
        ];
    }
}
