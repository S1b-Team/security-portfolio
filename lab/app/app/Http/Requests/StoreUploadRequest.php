<?php

namespace App\Http\Requests;

use App\Support\FixMode;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if (! FixMode::enabled()) {
            return [
                'file' => ['required', 'file'],
            ];
        }

        $ext = implode(',', config('lab.upload_allowed_extensions', []));
        $mimes = implode(',', config('lab.upload_allowed_mimes', []));

        return [
            'file' => [
                'required',
                'file',
                'max:5120',
                'extensions:'.$ext,
                'mimetypes:'.$mimes,
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if (! FixMode::enabled()) {
            return;
        }

        $validator->after(function (Validator $validator): void {
            $file = $this->file('file');
            if (! $file) {
                return;
            }

            $original = $file->getClientOriginalName();
            if (preg_match('/\.(php|phtml|phar|php[0-9]|cgi|exe|sh|bash|pl|py)(\.|$)/i', $original)) {
                $validator->errors()->add('file', 'disallowed_extension');
            }

            $path = $file->getRealPath() ?: $file->getPathname();
            $head = (string) @file_get_contents($path, false, null, 0, 4096);
            if (preg_match('/<\?php|<\?=|<script\s+language\s*=\s*["\']php/i', $head)) {
                $validator->errors()->add('file', 'php_content_detected');
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors();
        $reason = 'validation_failed';

        foreach ($errors->get('file') as $msg) {
            if (in_array($msg, ['disallowed_extension', 'php_content_detected'], true)) {
                $reason = $msg;
                break;
            }
        }

        if ($reason === 'validation_failed') {
            $first = strtolower((string) $errors->first('file'));
            if (str_contains($first, 'type') || str_contains($first, 'mime')) {
                $reason = 'mime_not_allowlisted';
            } elseif (str_contains($first, 'extension') || str_contains($first, 'must have') || str_contains($first, 'extensions')) {
                $reason = 'extension_not_allowlisted';
            } elseif (str_contains($first, 'required')) {
                $reason = 'no_file';
            }
        }

        throw new HttpResponseException(response()->json([
            'ok' => false,
            'error' => $reason,
        ], $reason === 'no_file' ? 400 : 415));
    }
}
