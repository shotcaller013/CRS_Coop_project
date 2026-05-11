<?php
// app/Http/Requests/Beneficiary/StoreBeneficiaryRequest.php
namespace App\Http\Requests\Beneficiary;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Beneficiary::class);
    }

    public function rules(): array
    {
        return [
            'member_id'              => 'required|exists:members,id',
            'type'                   => ['required', Rule::in(['primary', 'secondary'])],
            'first_name'             => 'required|string|max:100',
            'last_name'              => 'required|string|max:100',
            'middle_name'            => 'nullable|string|max:100',
            'relationship'           => 'required|string|max:60',
            'birthdate'              => 'nullable|date|before:today',
            'share_percentage'       => [
                Rule::requiredIf(fn () => $this->input('type') === 'primary'),
                'nullable', 'numeric', 'min:0.01', 'max:100',
            ],
            'contact_number'         => 'nullable|string|max:20',
            'address'                => 'nullable|string|max:300',
            'guardian_name'          => 'nullable|string|max:200',
            'guardian_contact'       => 'nullable|string|max:20',
            'guardian_relationship'  => 'nullable|string|max:60',
            'sort_order'             => 'nullable|integer|min:0|max:99',
        ];
    }

    public function messages(): array
    {
        return [
            'share_percentage.required' => 'Percentage share is required for primary beneficiaries.',
        ];
    }
}
