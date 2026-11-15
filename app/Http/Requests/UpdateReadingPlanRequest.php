<?php

namespace App\Http\Requests;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateReadingPlanRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'target_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    /**
     * 編集時は book_id を受け取らないため、編集対象の計画の書籍で重複をチェックする
     * （自身は除外）
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var ReadingPlan $plan */
                $plan = $this->route('plan');

                $exists = ReadingPlan::where('user_id', $plan->user_id)
                    ->where('book_id', $plan->book_id)
                    ->where('status', '!=', ReadingPlanStatus::Completed)
                    ->whereKeyNot($plan->id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('book_id', 'この書籍は既に進行中の読書計画が存在します。');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'target_date.required' => '期日は必須です。',
            'target_date.date' => '期日は有効な日付形式で入力してください。',
            'target_date.after_or_equal' => '期日は今日以降の日付を指定してください。',
        ];
    }
}
