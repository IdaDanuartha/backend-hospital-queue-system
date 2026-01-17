<?php

namespace App\Http\Requests\Staff;

use App\Http\Requests\BaseRequest;

class SkipQueueRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * Catatan/alasan skip
             * @example Pasien tidak hadir
             */
            'remark' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'remark.max' => 'Catatan maksimal 500 karakter',
        ];
    }
}
