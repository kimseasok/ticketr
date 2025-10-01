<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class UpdateIpRestrictionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'ip_allowlist' => ['nullable', 'array'],
            'ip_allowlist.*' => ['ip'],
            'ip_blocklist' => ['nullable', 'array'],
            'ip_blocklist.*' => ['ip'],
        ];
    }
}
