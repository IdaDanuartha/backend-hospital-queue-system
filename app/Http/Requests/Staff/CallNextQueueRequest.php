<?php

namespace App\Http\Requests\Staff;

use App\Http\Requests\BaseRequest;

class CallNextQueueRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * ID jenis antrian (UUID)
             * @example 9d4e8f12-3456-7890-abcd-ef1234567890
             */
            'queue_type_id' => 'required|uuid|exists:queue_types,id',
        ];
    }

    public function messages(): array
    {
        return [
            'queue_type_id.required' => 'Jenis antrian harus dipilih',
            'queue_type_id.exists' => 'Jenis antrian tidak ditemukan',
        ];
    }
}
