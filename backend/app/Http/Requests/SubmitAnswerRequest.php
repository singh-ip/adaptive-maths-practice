<?php

namespace App\Http\Requests;

use App\Enums\SessionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SubmitAnswerRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'session_id' => $this->route('sessionId'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'session_id' => [
                'required',
                'integer',
                Rule::exists('sessions', 'id')->where('status', SessionStatus::Active->value),
            ],
            'question_id' => [
                'required',
                'integer',
                Rule::exists('questions', 'id')->where('session_id', $this->input('session_id')),
                Rule::unique('answers', 'question_id'),
            ],
            'answer' => ['required', 'regex:/^-?\d+$/'],
            'past_questions' => ['sometimes', 'array'],
            'past_questions.*' => ['string', 'max:500'],
        ];
    }

    public function getValidatedAnswer(): int
    {
        return (int) trim((string) $this->input('answer'));
    }

    /**
     * @return string[]
     */
    public function getPastQuestions(): array
    {
        return (array) $this->input('past_questions', []);
    }
}
