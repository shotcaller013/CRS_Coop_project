<?php
// app/Http/Requests/ShareCapital/PostTransactionRequest.php
namespace App\Http\Requests\ShareCapital;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\ShareCapitalTransaction::class);
    }

    public function rules(): array
    {
        return [
            'type'             => ['required', Rule::in(['opening','deposit','withdrawal','dividend','adjustment'])],
            'amount'           => 'required|numeric|min:0.01|max:9999999.99',
            'or_number'        => 'nullable|string|max:50',
            'transaction_date' => 'required|date|before_or_equal:today',
            'remarks'          => [
                Rule::requiredIf(fn() => $this->input('type') === 'adjustment'),
                'nullable', 'string', 'max:500',
            ],
            'reference_no'     => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'remarks.required' => 'Remarks are required for adjustment transactions.',
        ];
    }

    /**
     * Inject direction based on type — controller calls this after validation.
     */
    public function resolvedDirection(): string
    {
        return match ($this->input('type')) {
            'withdrawal'            => 'debit',
            'adjustment'            => $this->input('direction', 'credit'), // allow explicit override
            default                 => 'credit',
        };
    }
}
