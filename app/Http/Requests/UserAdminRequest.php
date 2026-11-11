<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Tạo/sửa người dùng bởi Admin. Quyền truy cập do middleware role:Admin ở routes đảm nhiệm. */
class UserAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $creating = $user === null;

        return [
            'employee_code' => [$creating ? 'required' : 'prohibited', 'string', 'max:50', Rule::unique('users', 'employee_code')],
            'name' => ['required', 'string', 'max:255'],
            'email' => [$creating ? 'required' : 'prohibited', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:20'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'is_active' => ['nullable', 'boolean'],
            'password' => [$creating ? 'required' : 'nullable', 'string', Password::min(8)->letters()->numbers()],
        ];
    }
}
