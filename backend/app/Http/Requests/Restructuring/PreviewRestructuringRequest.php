<?php
// app/Http/Requests/Restructuring/PreviewRestructuringRequest.php
namespace App\Http\Requests\Restructuring;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewRestructuringRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('preview', LoanRestructuring::class);
    }

    public function rules(): array
    {
        return [
            'new_amount'        => 'required|numeric|min:1',
            'new_term_months'   => 'required|integer|min:1|max:360',
            'new_annual_rate'   => 'required|numeric|min:0.01|max:100',
            'new_frequency'     => ['required', Rule::in(['MONTHLY','BI-MONTHLY','WEEKLY'])],
            'new_first_due_date'=> 'required|date|after:today',
        ];
    }
}
