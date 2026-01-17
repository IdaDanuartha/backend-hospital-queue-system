<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

class UpdateSystemSettingRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * Array of settings to update
             */
            'settings' => 'required|array',

            /**
             * Setting key
             * @example GEOFENCE_ENABLED
             */
            'settings.*.key' => 'required|string',

            /**
             * Setting value
             * @example true
             */
            'settings.*.value' => 'required|string',

            /**
             * Setting description
             * @example Enable geofencing for queue
             */
            'settings.*.description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'settings.required' => 'Settings harus diisi',
            'settings.array' => 'Settings harus berupa array',
            'settings.*.key.required' => 'Key setting harus diisi',
            'settings.*.value.required' => 'Value setting harus diisi',
        ];
    }
}
