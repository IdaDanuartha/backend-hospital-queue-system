<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

class UpdateStaffRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $staffId = $this->route('staff');
        $staff = \App\Models\Staff::find($staffId);
        $userId = $staff?->user_id;

        return [
            /**
             * Nama lengkap staff
             * @example John Doe
             */
            'name' => 'required|string|max:255',

            /**
             * Username untuk login
             * @example staff001
             */
            'username' => 'required|string|max:255|unique:users,username,' . $userId,

            /**
             * Email staff
             * @example staff@hospital.com
             */
            'email' => 'required|email|unique:users,email,' . $userId,

            /**
             * Password (opsional untuk update)
             * @example newpassword123
             */
            'password' => 'nullable|string|min:8',

            /**
             * ID poliklinik (UUID)
             * @example 9d4e8f12-3456-7890-abcd-ef1234567890
             */
            'poly_id' => 'required|uuid|exists:polys,id',

            /**
             * Kode unik staff
             * @example STF-001
             */
            'code' => 'required|string|unique:staff,code,' . $staffId,

            /**
             * Status aktif staff
             * @example true
             */
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama harus diisi',
            'username.required' => 'Username harus diisi',
            'username.unique' => 'Username sudah digunakan',
            'email.required' => 'Email harus diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'password.min' => 'Password minimal 8 karakter',
            'poly_id.required' => 'Poli harus dipilih',
            'code.required' => 'Kode staff harus diisi',
            'code.unique' => 'Kode staff sudah digunakan',
        ];
    }
}
