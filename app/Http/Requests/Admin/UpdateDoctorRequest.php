<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

class UpdateDoctorRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $doctorId = $this->route('doctor');

        return [
            /**
             * ID poliklinik (UUID)
             * @example 9d4e8f12-3456-7890-abcd-ef1234567890
             */
            'poly_id' => 'required|uuid|exists:polys,id',

            /**
             * Nomor SIP dokter
             * @example SIP.123.456.789
             */
            'sip_number' => 'required|string|unique:doctors,sip_number,' . $doctorId,

            /**
             * Nama lengkap dokter
             * @example Dr. John Doe, Sp.PD
             */
            'name' => 'required|string|max:255',

            /**
             * Spesialisasi dokter
             * @example Spesialis Penyakit Dalam
             */
            'specialization' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'poly_id.required' => 'Poli harus dipilih',
            'sip_number.required' => 'Nomor SIP harus diisi',
            'sip_number.unique' => 'Nomor SIP sudah terdaftar',
            'name.required' => 'Nama dokter harus diisi',
        ];
    }
}
