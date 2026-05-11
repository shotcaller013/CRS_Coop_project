<?php
// app/Http/Resources/BillResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'bill_no'               => $this->bill_no,
            'status'                => $this->status,
            'status_label'          => $this->status_label,

            // Company
            'company_id'            => $this->company_id,
            'company_name'          => $this->company?->name,

            // Period
            'billing_period_start'  => $this->billing_period_start?->toDateString(),
            'billing_period_end'    => $this->billing_period_end?->toDateString(),
            'period_label'          => $this->billing_period_start?->format('M d')
                . ' – '
                . $this->billing_period_end?->format('M d, Y'),

            // Financials
            'total_amount'          => $this->total_amount,
            'amount_remitted'       => $this->amount_remitted,
            'balance'               => $this->balance,
            'item_count'            => $this->item_count,

            // Timestamps
            'issued_at'             => $this->issued_at?->toIso8601String(),
            'issued_at_human'       => $this->issued_at?->diffForHumans(),
            'settled_at'            => $this->settled_at?->toIso8601String(),
            'created_at'            => $this->created_at?->toIso8601String(),
            'created_at_human'      => $this->created_at?->diffForHumans(),

            // Relations — loaded on demand
            'prepared_by_name'      => $this->preparedBy?->name,
            'notes'                 => $this->notes,

            'items'       => $this->whenLoaded('items', fn() =>
                $this->items->map(fn($item) => [
                    'id'            => $item->id,
                    'member_id'     => $item->member_id,
                    'member_name'   => $item->member
                        ? "{$item->member->first_name} {$item->member->last_name}"
                        : '—',
                    'member_no'     => $item->member?->member_no,
                    'loan_id'       => $item->loan_id,
                    'loan_no'       => $item->loan?->loan_no,
                    'schedule_id'   => $item->schedule_id,
                    'period_no'     => $item->schedule?->period_no,
                    'due_date'      => $item->schedule?->due_date?->toDateString(),
                    'amount_due'    => $item->amount_due,
                    'amount_paid'   => $item->amount_paid,
                    'status'        => $item->status,
                ])
            ),

            'remittances' => $this->whenLoaded('remittances', fn() =>
                $this->remittances->map(fn($r) => [
                    'id'               => $r->id,
                    'or_number'        => $r->or_number,
                    'amount'           => $r->amount,
                    'remittance_date'  => $r->remittance_date?->toDateString(),
                    'notes'            => $r->notes,
                    'has_file'         => !empty($r->file_path),
                    'file_url'         => $r->file_path
                        ? route('api.bills.remittance.file', $r->id)
                        : null,
                    'posted_by_name'   => $r->postedBy?->name,
                    'created_at_human' => $r->created_at?->diffForHumans(),
                ])
            ),
        ];
    }
}
