<?php
// app/Http/Requests/Beneficiary/UpdateBeneficiaryRequest.php
namespace App\Http\Requests\Beneficiary;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('beneficiary'));
    }

    public function rules(): array
    {
        $isOrWillBePrimary = $this->input('type', $this->route('beneficiary')?->type) === 'primary';

        return [
            'type'                   => ['sometimes', Rule::in(['primary', 'secondary'])],
            'first_name'             => 'sometimes|string|max:100',
            'last_name'              => 'sometimes|string|max:100',
            'middle_name'            => 'nullable|string|max:100',
            'relationship'           => 'sometimes|string|max:60',
            'birthdate'              => 'nullable|date|before:today',
            'share_percentage'       => [
                $isOrWillBePrimary ? 'required' : 'nullable',
                'numeric', 'min:0.01', 'max:100',
            ],
            'contact_number'         => 'nullable|string|max:20',
            'address'                => 'nullable|string|max:300',
            'guardian_name'          => 'nullable|string|max:200',
            'guardian_contact'       => 'nullable|string|max:20',
            'guardian_relationship'  => 'nullable|string|max:60',
            'sort_order'             => 'nullable|integer|min:0|max:99',
        ];
    }
}
