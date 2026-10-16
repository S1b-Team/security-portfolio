<?php

namespace App\Http\Requests;

use App\Services\SsrfGuard;
use App\Support\FixMode;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class WebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'callback_url' => ['nullable', 'string'],
            'url' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $url = (string) ($this->input('callback_url') ?: $this->input('url') ?: '');
            if ($url === '') {
                $validator->errors()->add('callback_url', 'missing_url');

                return;
            }

            if (! FixMode::enabled()) {
                return;
            }

            $reason = app(SsrfGuard::class)->validate($url);
            if ($reason !== null) {
                $validator->errors()->add('callback_url', $reason);
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        $msg = $validator->errors()->first('callback_url') ?: $validator->errors()->first();
        $code = $msg === 'missing_url' ? 400 : 400;

        throw new HttpResponseException(response()->json([
            'ok' => false,
            'error' => $msg ?: 'validation_failed',
        ], $code));
    }

    public function callbackUrl(): string
    {
        return (string) ($this->input('callback_url') ?: $this->input('url') ?: '');
    }
}
