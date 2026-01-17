<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

class UpdateQueueTypeRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * ID poliklinik (opsional)
             * @example 9d4e8f12-3456-7890-abcd-ef1234567890
             */
            'poly_id' => 'nullable|uuid|exists:polys,id',

            /**
             * Nama jenis antrian
             * @example Antrian Umum
             */
            'name' => 'required|string|max:255',

            /**
             * Kode prefix antrian (max 5 karakter)
             * @example A
             */
            'code_prefix' => 'required|string|max:5',

            /**
             * Unit pelayanan
             * @example Loket 1
             */
            'service_unit' => 'nullable|string|max:255',

            /**
             * Rata-rata waktu pelayanan (menit)
             * @example 15
             */
            'avg_service_minutes' => 'nullable|integer|min:1',

            /**
             * Status aktif
             * @example true
             */
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama jenis antrian harus diisi',
            'code_prefix.required' => 'Kode prefix harus diisi',
            'code_prefix.max' => 'Kode prefix maksimal 5 karakter',
            'avg_service_minutes.min' => 'Waktu pelayanan minimal 1 menit',
        ];
    }
}
