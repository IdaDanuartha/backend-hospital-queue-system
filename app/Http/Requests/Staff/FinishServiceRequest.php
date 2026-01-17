<?php

namespace App\Http\Requests\Staff;

use App\Http\Requests\BaseRequest;

class FinishServiceRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * Catatan pelayanan
             * @example Pasien selesai dilayani, resep sudah diberikan
             */
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'notes.max' => 'Catatan maksimal 1000 karakter',
        ];
    }
}
