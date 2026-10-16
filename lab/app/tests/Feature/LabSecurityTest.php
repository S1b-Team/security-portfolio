<?php

use App\Models\UploadedFile;
use App\Services\SafePath;
use App\Services\SsrfGuard;
use Illuminate\Http\UploadedFile as HttpUpload;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    // Storage::fake replaces disk; re-bind root for SafePath realpath checks via real disk for path tests
    config(['lab.fix_enabled' => true]);
    config(['lab.webhook_token' => 'lab-webhook-token-change-me']);
    config(['lab.webhook_allow_hosts' => ['example.com', 'httpbin.org']]);
});

function makePhpUpload(string $name = 'shell.php'): HttpUpload
{
    return HttpUpload::fake()->createWithContent($name, "<?php echo 'lab'; ?>");
}

// --- Upload controls ---

test('test_reject_php_upload', function () {
    $response = $this->post('/upload', [
        'file' => makePhpUpload('shell.php'),
    ]);
    $response->assertStatus(415);
    expect($response->json('ok'))->toBeFalse();
    expect($response->json('error'))->toBeIn([
        'disallowed_extension',
        'extension_not_allowlisted',
        'php_content_detected',
        'mime_not_allowlisted',
    ]);
});

test('test_reject_double_extension_php', function () {
    $file = HttpUpload::fake()->createWithContent('invoice.pdf.php', 'not php text');
    $response = $this->post('/upload', ['file' => $file]);
    $response->assertStatus(415);
    expect($response->json('ok'))->toBeFalse();
});

test('test_allow_txt_upload', function () {
    Storage::fake('local');
    $file = HttpUpload::fake()->createWithContent('notes.txt', 'hello lab');
    $response = $this->withHeaders(['X-Lab-Owner' => 'owner-a'])->post('/upload', ['file' => $file]);
    $response->assertOk();
    expect($response->json('ok'))->toBeTrue();
    expect($response->json('stored_as'))->not->toBeEmpty();
});

test('test_reject_php_content_in_txt', function () {
    $file = HttpUpload::fake()->createWithContent('notes.txt', "<?php echo 'lab'; ?>");
    $response = $this->post('/upload', ['file' => $file]);
    $response->assertStatus(415);
    expect($response->json('error'))->toBeIn(['php_content_detected', 'mime_not_allowlisted']);
});

test('test_app_upload_rejects_php', function () {
    $response = $this->post('/upload', ['file' => makePhpUpload('x.php')]);
    expect($response->json('ok') ?? true)->toBeFalse();
});

// --- SSRF guard ---

test('test_ssrf_block_metadata_http', function () {
    $r = app(SsrfGuard::class)->validate('http://169.254.169.254/latest/meta-data/iam/security-credentials/');
    expect($r)->not->toBeNull();
});

test('test_ssrf_block_metadata_https', function () {
    $r = app(SsrfGuard::class)->validate('https://169.254.169.254/latest/meta-data/');
    expect($r)->not->toBeNull();
});

test('test_ssrf_block_loopback', function () {
    expect(app(SsrfGuard::class)->validate('https://127.0.0.1/'))->not->toBeNull();
});

test('test_ssrf_block_private_rfc1918', function () {
    expect(app(SsrfGuard::class)->validate('https://192.168.1.10/hook'))->not->toBeNull();
});

test('test_ssrf_allowlist_rejects_unknown_host', function () {
    expect(app(SsrfGuard::class)->validate('https://evil.example.net/x'))->toBe('host_not_allowlisted');
});

test('test_ssrf_allowlist_example_com', function () {
    $r = app(SsrfGuard::class)->validate('https://example.com/callback');
    expect($r === null || $r === 'internal_ip_blocked')->toBeTrue();
});

test('test_app_webhook_blocks_metadata', function () {
    $response = $this->postJson('/webhook', [
        'callback_url' => 'http://169.254.169.254/latest/meta-data/',
    ], [
        'X-Webhook-Token' => 'lab-webhook-token-change-me',
    ]);
    expect($response->json('ok') ?? true)->toBeFalse();
});

// --- Webhook token ---

test('test_webhook_token_required_null', function () {
    $response = $this->postJson('/webhook', [
        'callback_url' => 'https://example.com/',
    ]);
    $response->assertStatus(401);
    expect($response->json('error'))->toBe('missing_or_invalid_token');
});

test('test_webhook_token_required_wrong', function () {
    $response = $this->postJson('/webhook', [
        'callback_url' => 'https://example.com/',
    ], [
        'X-Webhook-Token' => 'wrong',
    ]);
    $response->assertStatus(401);
});

test('test_webhook_token_accepts_valid', function () {
    // Token middleware passes; SSRF allowlist may still allow example.com
    \Illuminate\Support\Facades\Http::fake([
        'example.com/*' => \Illuminate\Support\Facades\Http::response('ok', 200),
    ]);
    $response = $this->postJson('/webhook', [
        'callback_url' => 'https://example.com/callback',
    ], [
        'X-Webhook-Token' => 'lab-webhook-token-change-me',
    ]);
    // Either ok fetch or SSRF block if DNS odd — token itself must not 401
    expect($response->status())->not->toBe(401);
});

test('test_app_webhook_requires_token', function () {
    $response = $this->postJson('/webhook', [
        'callback_url' => 'https://example.com/',
    ], [
        'X-Webhook-Token' => 'bad-token',
    ]);
    expect($response->json('ok') ?? true)->toBeFalse();
});

// --- Private storage ---

test('test_private_storage_not_under_webroot', function () {
    $root = Storage::disk('local')->path('');
    $public = public_path();
    $rootReal = realpath($root) ?: $root;
    $publicReal = realpath($public) ?: $public;
    expect(str_starts_with($rootReal, $publicReal))->toBeFalse();
});

test('test_private_storage_path_name', function () {
    $root = Storage::disk('local')->path('');
    expect(
        str_contains($root, 'storage') || str_contains($root, 'private') || str_contains($root, 'local')
    )->toBeTrue();
});

// --- Download / path traversal / IDOR ---

test('test_no_path_traversal_download', function () {
    // Use real local disk path (not fake) for SafePath realpath jail
    Storage::disk('local')->makeDirectory('uploads');
    $guard = app(SafePath::class);
    [$p, $err] = $guard->resolve('../etc/passwd');
    expect($err !== null || ($p !== null && ! str_contains($p, 'etc/passwd')))->toBeTrue();
});

test('test_no_urlencoded_traversal', function () {
    Storage::disk('local')->makeDirectory('uploads');
    [$p, $err] = app(SafePath::class)->resolve('..%2F..%2Fetc%2Fpasswd');
    $root = realpath(Storage::disk('local')->path('uploads')) ?: Storage::disk('local')->path('uploads');
    expect(
        $err !== null || ($p !== null && str_starts_with(realpath(dirname($p)) ?: dirname($p), $root))
    )->toBeTrue();
});

test('test_download_allows_safe_basename', function () {
    Storage::fake('local');
    $file = HttpUpload::fake()->createWithContent('notes.txt', 'hello lab');
    $up = $this->withHeaders(['X-Lab-Owner' => 'owner-a'])->post('/upload', ['file' => $file]);
    $up->assertOk();
    $stored = $up->json('stored_as');

    $response = $this->withHeaders(['X-Lab-Owner' => 'owner-a'])->get('/download?file='.urlencode($stored));
    $response->assertOk();
});

test('test_download_idor_blocked_for_other_owner', function () {
    Storage::fake('local');
    $file = HttpUpload::fake()->createWithContent('notes.txt', 'secret');
    $up = $this->withHeaders(['X-Lab-Owner' => 'owner-a'])->post('/upload', ['file' => $file]);
    $uuid = $up->json('uuid');

    $response = $this->withHeaders(['X-Lab-Owner' => 'owner-b'])->get('/download/'.$uuid);
    $response->assertStatus(403);
    expect($response->json('error'))->toBe('forbidden_owner');
});
