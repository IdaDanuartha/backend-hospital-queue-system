<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\BaseRequest;

class TakeQueueRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * Nama pasien (opsional)
             * @example John Doe
             */
            'patient_name' => 'nullable|string|max:255',

            /**
             * Nomor telepon pasien untuk validasi duplikasi antrian
             * @example 081234567890
             */
            'phone_number' => 'required|string|max:20',

            /**
             * ID jenis antrian (UUID)
             * @example 9d4e8f12-3456-7890-abcd-ef1234567890
             */
            'queue_type_id' => 'required|uuid|exists:queue_types,id',

            /**
             * Latitude lokasi pengguna (opsional, untuk geofencing)
             * @example -8.68159129117202
             */
            'latitude' => 'nullable|numeric|between:-90,90',

            /**
             * Longitude lokasi pengguna (opsional, untuk geofencing)
             * @example 115.23986166248717
             */
            'longitude' => 'nullable|numeric|between:-180,180',
        ];
    }

    public function messages(): array
    {
        return [
            'patient_name.max' => 'Nama pasien maksimal 255 karakter',
            'phone_number.required' => 'Nomor telepon harus diisi',
            'phone_number.max' => 'Nomor telepon maksimal 20 karakter',
            'queue_type_id.required' => 'Jenis antrian harus dipilih',
            'queue_type_id.exists' => 'Jenis antrian tidak valid',
            'latitude.numeric' => 'Latitude harus berupa angka',
            'longitude.numeric' => 'Longitude harus berupa angka',
        ];
    }
}
