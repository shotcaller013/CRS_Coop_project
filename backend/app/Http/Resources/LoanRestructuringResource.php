<?php
// app/Http/Resources/LoanRestructuringResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanRestructuringResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'loan_id'                 => $this->loan_id,
            'restructuring_no'        => $this->restructuring_no,
            'effective_date'          => $this->effective_date?->toDateString(),

            // Old terms
            'old_remaining_balance'   => $this->old_remaining_balance,
            'old_term_months'         => $this->old_term_months,
            'old_annual_rate'         => $this->old_annual_rate,
            'old_frequency'           => $this->old_frequency,
            'old_periods_remaining'   => $this->old_periods_remaining,

            // New terms
            'new_amount'              => $this->new_amount,
            'new_term_months'         => $this->new_term_months,
            'new_annual_rate'         => $this->new_annual_rate,
            'new_frequency'           => $this->new_frequency,
            'new_first_due_date'      => $this->new_first_due_date?->toDateString(),
            'new_n_periods'           => $this->new_n_periods,
            'new_total_payment'       => $this->new_total_payment,
            'new_total_interest'      => $this->new_total_interest,
            'frequency_label'         => $this->frequency_label,

            // What changed
            'periods_voided'          => $this->periods_voided,
            'reason'                  => $this->reason,

            // Who
            'approved_by'             => $this->approved_by,
            'approved_by_name'        => $this->approved_by_name,

            'created_at'              => $this->created_at?->toIso8601String(),
            'created_at_human'        => $this->created_at?->diffForHumans(),
        ];
    }
}
