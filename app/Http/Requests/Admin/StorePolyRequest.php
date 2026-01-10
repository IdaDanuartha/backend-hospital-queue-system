<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

class StorePolyRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * Kode unik poliklinik
             * @example POLI-001
             */
            'code' => 'required|string|max:50|unique:polys,code',

            /**
             * Nama poliklinik
             * @example Poliklinik Umum
             */
            'name' => 'required|string|max:255',

            /**
             * Lokasi poliklinik
             * @example Gedung A Lantai 2
             */
            'location' => 'nullable|string|max:255',

            /**
             * Status aktif poliklinik
             * @example true
             */
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode poli harus diisi',
            'code.unique' => 'Kode poli sudah digunakan',
            'name.required' => 'Nama poli harus diisi',
        ];
    }
}
