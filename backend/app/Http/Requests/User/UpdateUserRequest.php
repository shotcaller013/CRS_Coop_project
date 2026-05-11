<?php
// app/Http/Requests/User/UpdateUserRequest.php
namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('userAccount'));
    }

    public function rules(): array
    {
        $userId = $this->route('userAccount')?->id;

        return [
            'name'  => 'sometimes|string|max:200',
            'email' => "sometimes|email|max:200|unique:users,email,{$userId}",
            'role'  => 'sometimes|string|in:super-admin,manager,loan-officer,staff,board',
        ];
    }
}
