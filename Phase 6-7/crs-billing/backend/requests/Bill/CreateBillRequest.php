<?php
// app/Http/Requests/Bill/CreateBillRequest.php
namespace App\Http\Requests\Bill;

use Illuminate\Foundation\Http\FormRequest;

class CreateBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Bill::class);
    }

    public function rules(): array
    {
        return [
            'company_id'           => 'required|exists:companies,id',
            'billing_period_start' => 'required|date',
            'billing_period_end'   => 'required|date|after_or_equal:billing_period_start',
            'notes'                => 'nullable|string|max:500',
        ];
    }
}
