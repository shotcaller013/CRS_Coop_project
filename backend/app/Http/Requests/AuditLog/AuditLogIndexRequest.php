<?php
// app/Http/Requests/AuditLog/AuditLogIndexRequest.php
namespace App\Http\Requests\AuditLog;

use Illuminate\Foundation\Http\FormRequest;

class AuditLogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', \App\Models\AuditLog::class);
    }

    public function rules(): array
    {
        return [
            'auditable_type' => 'nullable|string|max:100',
            'auditable_id'   => 'nullable|integer',
            'event'          => 'nullable|in:created,updated,deleted,restored',
            'user_id'        => 'nullable|integer|exists:users,id',
            'date_from'      => 'nullable|date',
            'date_to'        => 'nullable|date|after_or_equal:date_from',
            'search'         => 'nullable|string|max:100',
            'per_page'       => 'nullable|integer|min:10|max:100',
        ];
    }
}
