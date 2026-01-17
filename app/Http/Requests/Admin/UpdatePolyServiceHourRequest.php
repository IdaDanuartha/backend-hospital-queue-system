<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

class UpdatePolyServiceHourRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * Hari dalam seminggu (0=Minggu, 1=Senin, dst)
             * @example 1
             */
            'day_of_week' => 'required|integer|min:0|max:6',

            /**
             * Waktu buka (format HH:mm)
             * @example 08:00
             */
            'open_time' => 'required|date_format:H:i',

            /**
             * Waktu tutup (format HH:mm)
             * @example 16:00
             */
            'close_time' => 'required|date_format:H:i|after:open_time',

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
            'day_of_week.required' => 'Hari harus dipilih',
            'open_time.required' => 'Waktu buka harus diisi',
            'open_time.date_format' => 'Format waktu buka tidak valid (HH:mm)',
            'close_time.required' => 'Waktu tutup harus diisi',
            'close_time.after' => 'Waktu tutup harus setelah waktu buka',
        ];
    }
}
