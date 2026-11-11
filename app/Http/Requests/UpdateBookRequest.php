<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BookUpdateRequest',
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Laskar Pelangi'),
        new OA\Property(property: 'author', type: 'string', maxLength: 255, example: 'Andrea Hirata'),
        new OA\Property(property: 'publisher', type: 'string', nullable: true, example: 'Bentang Pustaka'),
        new OA\Property(property: 'isbn', type: 'string', maxLength: 20, example: '9789793062792'),
        new OA\Property(property: 'published_year', type: 'integer', minimum: 1000, example: 2005),
        new OA\Property(property: 'stock', type: 'integer', minimum: 0, example: 10),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Novel tentang anak-anak Belitung.'),
    ]
)]
class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'author' => ['sometimes', 'required', 'string', 'max:255'],
            'publisher' => ['sometimes', 'nullable', 'string', 'max:255'],
            'isbn' => [
                'sometimes', 'required', 'string', 'max:20',
                Rule::unique('books', 'isbn')->ignore($this->route('book')),
            ],
            'published_year' => ['sometimes', 'required', 'integer', 'min:1000', 'max:'.date('Y')],
            'stock' => ['sometimes', 'required', 'integer', 'min:0'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
