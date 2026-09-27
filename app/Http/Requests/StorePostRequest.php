<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation.
     */
    protected function prepareForValidation(): void
    {
        $mergeData = [];

        if (! $this->has('idempotency_key')) {
            $headerKey = $this->header('Idempotency-Key') ?? $this->header('X-Idempotency-Key');
            if ($headerKey) {
                $mergeData['idempotency_key'] = $headerKey;
            }
        }

        if (! $this->has('account_ids') && $this->has('target_account_ids')) {
            $mergeData['account_ids'] = $this->input('target_account_ids');
        }

        if (! empty($mergeData)) {
            $this->merge($mergeData);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string'],
            'account_ids' => ['required', 'array', 'min:1'],
            'account_ids.*' => ['required', 'uuid', 'exists:social_accounts,id'],
            'scheduled_at' => ['nullable', 'date'],
            'idempotency_key' => ['required', 'string'],
            'platform_overrides' => ['nullable', 'array'],
            'media_url' => ['nullable', 'url'],
            'link_metadata' => ['nullable', 'array'],
        ];
    }
}
