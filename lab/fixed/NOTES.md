# Fixed stage (`FIX=1`, default)

Container: `lab-app-fixed` on host port **8080** (Laravel 12).

Enforced:

- `StoreUploadRequest` allowlist + PHP content sniff
- `VerifyWebhookToken`
- `SsrfGuard`
- `Storage::disk('local')` private root + `SafePath` + `UploadedFilePolicy`

Regression: `./vendor/bin/pest --filter=LabSecurity`
