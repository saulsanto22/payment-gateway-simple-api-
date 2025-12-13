<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Hanya user dengan role ADMIN yang boleh mengubah produk.
        return auth()->user() && auth()->user()->role === UserRole::ADMIN;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // 'sometimes' berarti: validasi hanya jika field ini ada di dalam request.
            // Ini cocok untuk operasi UPDATE dimana tidak semua field harus diisi ulang.
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|integer|min:0',
            'stock' => 'sometimes|required|integer|min:0',
        ];
    }
}
