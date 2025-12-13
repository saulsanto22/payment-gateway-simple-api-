<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            // Aturan untuk field produk dasar
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|integer|min:0',
            'stock' => 'sometimes|required|integer|min:0',

            // Aturan untuk gambar baru yang di-upload
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048', // Validasi setiap file dalam array

            // Aturan untuk gambar yang akan dihapus
            'images_to_delete' => 'nullable|array',
            // Pastikan setiap ID dalam array ada di tabel product_images dan benar-benar milik produk ini
            'images_to_delete.*' => [
                'integer',
                Rule::exists('product_images', 'id')->where(function ($query) {
                    // Dapatkan produk dari route model binding
                    $product = $this->route('product');
                    if ($product) {
                        $query->where('product_id', $product->id);
                    }
                }),
            ],
        ];
    }
}
