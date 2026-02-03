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
             * Nama pasien
             * @example John Doe
             */
            'patient_name' => 'required|string|max:255',

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

            /**
             * Alamat IP pengguna untuk validasi duplikasi antrian
             * @example 192.168.1.1
             */
            'ip_address' => 'required|ip',
        ];
    }

    public function messages(): array
    {
        return [
            'patient_name.required' => 'Nama pasien harus diisi',
            'patient_name.max' => 'Nama pasien maksimal 255 karakter',
            'queue_type_id.required' => 'Jenis antrian harus dipilih',
            'queue_type_id.exists' => 'Jenis antrian tidak valid',
            'latitude.numeric' => 'Latitude harus berupa angka',
            'longitude.numeric' => 'Longitude harus berupa angka',
            'ip_address.required' => 'Alamat IP harus dikirim',
            'ip_address.ip' => 'Format alamat IP tidak valid',
        ];
    }
}
