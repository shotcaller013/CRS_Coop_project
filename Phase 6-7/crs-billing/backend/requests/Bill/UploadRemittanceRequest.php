<?php
// app/Http/Requests/Bill/UploadRemittanceRequest.php
namespace App\Http\Requests\Bill;

use Illuminate\Foundation\Http\FormRequest;

class UploadRemittanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('uploadRemittance', $this->route('bill'));
    }

    public function rules(): array
    {
        return [
            'amount'           => 'required|numeric|min:0.01',
            'or_number'        => 'nullable|string|max:100',
            'remittance_date'  => 'required|date',
            'notes'            => 'nullable|string|max:500',
            'file'             => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
        ];
    }
}
