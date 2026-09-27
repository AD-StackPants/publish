<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ConnectSocialAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'provider'     => ['required', 'string', 'in:linkedin,facebook,twitter'],
            'name'         => ['sometimes', 'string', 'max:255'],
            'account_id'   => ['nullable', 'string', 'max:255'],
            'avatar_url'   => ['nullable', 'url', 'max:2048'],
            'access_token' => ['nullable', 'string'],
        ];
    }
}
