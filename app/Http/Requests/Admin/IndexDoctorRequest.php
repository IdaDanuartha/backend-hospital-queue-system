<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

class IndexDoctorRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * Filter berdasarkan ID poliklinik
             * @query
             * @example 9d4e8f12-3456-7890-abcd-ef1234567890
             */
            'poly_id' => 'nullable|uuid|exists:polys,id',
        ];
    }

    public function messages(): array
    {
        return [
            'poly_id.uuid' => 'Format ID poli tidak valid',
            'poly_id.exists' => 'Poli tidak ditemukan',
        ];
    }
}
