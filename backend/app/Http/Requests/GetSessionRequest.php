<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GetSessionRequest extends FormRequest
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
            'session_id' => 'required|integer|exists:sessions,id',
        ];
    }
}
