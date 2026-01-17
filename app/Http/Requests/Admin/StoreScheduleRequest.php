<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

class StoreScheduleRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * ID dokter (UUID)
             * @example 9d4e8f12-3456-7890-abcd-ef1234567890
             */
            'doctor_id' => 'required|uuid|exists:doctors,id',

            /**
             * Hari dalam seminggu (0=Minggu, 1=Senin, dst)
             * @example 1
             */
            'day_of_week' => 'required|integer|min:0|max:6',

            /**
             * Waktu mulai (format HH:mm)
             * @example 08:00
             */
            'start_time' => 'required|date_format:H:i',

            /**
             * Waktu selesai (format HH:mm)
             * @example 12:00
             */
            'end_time' => 'required|date_format:H:i|after:start_time',

            /**
             * Kuota maksimal pasien
             * @example 20
             */
            'max_quota' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'doctor_id.required' => 'Dokter harus dipilih',
            'doctor_id.exists' => 'Dokter tidak ditemukan',
            'day_of_week.required' => 'Hari harus dipilih',
            'day_of_week.min' => 'Hari tidak valid',
            'day_of_week.max' => 'Hari tidak valid',
            'start_time.required' => 'Waktu mulai harus diisi',
            'start_time.date_format' => 'Format waktu mulai tidak valid (HH:mm)',
            'end_time.required' => 'Waktu selesai harus diisi',
            'end_time.date_format' => 'Format waktu selesai tidak valid (HH:mm)',
            'end_time.after' => 'Waktu selesai harus setelah waktu mulai',
        ];
    }
}
