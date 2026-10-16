<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUploadRequest;
use App\Http\Requests\WebhookRequest;
use App\Models\UploadedFile;
use App\Policies\UploadedFilePolicy;
use App\Services\SafePath;
use App\Support\FixMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LabController extends Controller
{
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'fix' => FixMode::enabled(),
            'lab' => 'livey-defensive-upload-ssrf',
            'stack' => 'laravel',
            'note' => 'Isolated Docker lab — not a production system',
        ]);
    }

    public function upload(StoreUploadRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $ownerKey = (string) ($request->header('X-Lab-Owner') ?: $request->input('owner_key') ?: 'lab-default-owner');

        $original = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension());

        if (FixMode::enabled()) {
            $stored = Str::random(32).($ext !== '' ? '.'.$ext : '');
            $diskPath = $file->storeAs('uploads', $stored, 'local');
        } else {
            $stored = basename($original);
            $diskPath = $file->storeAs('uploads', $stored, 'local');
        }

        $record = UploadedFile::query()->create([
            'original_name' => $original,
            'stored_name' => $stored,
            'disk_path' => $diskPath,
            'owner_key' => $ownerKey,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize() ?: 0,
        ]);

        Log::info('upload_accepted', ['stored' => $stored, 'uuid' => $record->uuid]);

        return response()->json([
            'ok' => true,
            'stored_as' => $stored,
            'uuid' => $record->uuid,
            'download' => '/download?file='.rawurlencode($stored),
            'download_by_uuid' => '/download/'.$record->uuid,
            'public_url' => null,
        ]);
    }

    public function webhook(WebhookRequest $request): JsonResponse
    {
        $url = $request->callbackUrl();

        try {
            $response = Http::timeout(5)->withOptions(['allow_redirects' => false])->get($url);
            $code = $response->status();
            $body = $response->body();
        } catch (\Throwable $e) {
            $code = 0;
            $body = $e->getMessage();
        }

        Log::info('webhook_fetch', ['url' => $url, 'http_code' => $code]);

        return response()->json([
            'ok' => true,
            'fetched' => $url,
            'http_code' => $code,
            'body_preview' => substr((string) $body, 0, 200),
        ]);
    }

    public function download(Request $request, SafePath $safePath): JsonResponse|StreamedResponse
    {
        $requested = (string) $request->query('file', '');
        if ($requested === '') {
            return response()->json(['ok' => false, 'error' => 'missing_file'], 400);
        }

        // Ownership / IDOR: if a DB record exists for stored_name, enforce policy
        $record = UploadedFile::query()->where('stored_name', basename(str_replace(["\\", "\0"], ['/', ''], $requested)))->first();
        if ($record && FixMode::enabled()) {
            $ownerKey = (string) ($request->header('X-Lab-Owner') ?: $request->query('owner_key', ''));
            $allowed = app(UploadedFilePolicy::class)->download(null, $record, $ownerKey);
            if (! $allowed) {
                Log::warning('download_idor_blocked', ['uuid' => $record->uuid]);

                return response()->json(['ok' => false, 'error' => 'forbidden_owner'], 403);
            }
        }

        [$path, $err] = $safePath->resolve($requested);
        if ($err !== null || $path === null) {
            Log::warning('download_blocked', ['requested' => $requested, 'reason' => $err]);

            return response()->json(['ok' => false, 'error' => $err ?? 'bad_path'], 400);
        }

        if (! is_file($path)) {
            return response()->json(['ok' => false, 'error' => 'not_found'], 404);
        }

        return response()->streamDownload(function () use ($path): void {
            readfile($path);
        }, basename($path), [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function downloadByUuid(Request $request, UploadedFile $file): JsonResponse|StreamedResponse
    {
        $ownerKey = (string) ($request->header('X-Lab-Owner') ?: $request->query('owner_key', ''));
        if (FixMode::enabled()) {
            $allowed = app(UploadedFilePolicy::class)->download(null, $file, $ownerKey);
            if (! $allowed) {
                return response()->json(['ok' => false, 'error' => 'forbidden_owner'], 403);
            }
        }

        if (! Storage::disk('local')->exists($file->disk_path)) {
            return response()->json(['ok' => false, 'error' => 'not_found'], 404);
        }

        return Storage::disk('local')->download($file->disk_path, $file->original_name, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
