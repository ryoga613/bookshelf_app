<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'regex:/^\d{13}$/',  'unique:books,isbn'],
            'published_at' => ['nullable', 'date'],
            'image_url' => ['nullable', 'url'],
            'description' => ['nullable', 'string'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['integer', 'exists:genres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルは必須です。',
            'title.string' => 'タイトルは文字列で入力してください',
            'author.required' => '著者名は必須です。',
            'isbn.regex' => 'ISBNは13桁の数字で入力してください。',
            'isbn.unique' => 'このISBNは既に使用されています。',
            'published_at.date' => '出版日は有効な日付形式で入力してください。',
            'image_url.url' => '画像URLは有効なURL形式で入力してください。',
            'genres.required' => 'ジャンルは1つ以上選択してください。',
            'genres.min' => 'ジャンルは1つ以上選択してください。',
            'genres.*.exists' => '選択されたジャンルは存在しません。',
        ];
    }
}
