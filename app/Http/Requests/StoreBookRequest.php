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
            'isbn' => ['required', 'string', 'regex:/^\d{10}(\d{3})?$/',  'unique:books,isbn'],
            'published_at' => ['required', 'date'],
            'image_url' => ['nullable', 'url'],
            'description' => ['required', 'string'],
            'genre_ids' => ['required', 'array', 'min:1'],
            'genre_ids.*' => ['integer', 'exists:genres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルを入力してください。',
            'author.required' => '著者名を入力してください。',
            'isbn.required' => 'ISBNを入力してください。',
            'isbn.regex' => 'ISBNは10桁または13桁の数字で入力してください。',
            'isbn.unique' => 'このISBNは既に登録されています。',
            'published_at.required' => '出版日を入力してください。',
            'published_at.date' => '出版日は有効な日付形式で入力してください。',
            'image_url.url' => '画像URLは正しいURL形式で入力してください。',
            'description.required' => '概要を入力してください。',
            'genre_ids.required' => 'ジャンルを1つ以上選択してください。',
            'genre_ids.min' => 'ジャンルを1つ以上選択してください。',
            'genre_ids.*.exists' => '選択されたジャンルが存在しません。',
        ];
    }
}
