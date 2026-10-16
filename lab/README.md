# Defensive lab: unsafe upload + webhook SSRF (Laravel, isolated Docker)

Portfolio / training lab for a B2B backoffice sample built on **Laravel 12**. **Not** a production system. No client data. All demos target **localhost** and the compose network only.

## What this lab shows

1. A minimal Laravel backoffice with **file upload** and **webhook callback** (SSRF surface).
2. An intentionally **vulnerable** mode (`FIX=0`, port **8081**) and a **fixed** mode (`FIX=1`, port **8080**, secure-by-default).
3. A **simulated** cloud metadata sidecar returning **FAKE** credentials (labeled as such).
4. **Pest** feature/regression tests that assert secure behavior.
5. Sample detection rules under `detections/`.

## Prerequisites

- Docker + Docker Compose
- For local tests without Docker: PHP 8.2+, Composer, SQLite

```bash
cd lab/app
composer install
cp .env.example .env   # ensure FIX=1, WEBHOOK_* set
php artisan key:generate
touch database/database.sqlite
php artisan migrate
./vendor/bin/pest --filter=LabSecurity
```

`vendor/` is **not** shipped in the portfolio tarball — always run `composer install` first.

## Quick start (Docker)

```bash
cd lab
docker compose build
docker compose up -d
curl -s http://127.0.0.1:8080/health   # fixed
curl -s http://127.0.0.1:8081/health   # vulnerable
```

Run Pest inside Compose:

```bash
docker compose --profile test run --rm tests
```

Stop:

```bash
docker compose down -v
```

## Ports and services

| Service | Host port | Role |
|---------|-----------|------|
| `app-fixed` | 8080 | Laravel `FIX=1` — controls enforced |
| `app-vulnerable` | 8081 | Laravel `FIX=0` — controls skipped |
| `mock-metadata` | *(none)* | FAKE metadata on `172.30.0.169`; vuln app maps `169.254.169.254` via `extra_hosts` |

## Laravel controls (fixed mode)

| Concern | Implementation |
|---------|----------------|
| Upload allowlist + reject PHP content | `StoreUploadRequest` (`extensions` / `mimetypes` + content sniff) |
| Private storage | `Storage::disk('local')` → `storage/app/private` (not under `public/`) |
| Download / no IDOR | `SafePath` basename+realpath jail + `UploadedFilePolicy` owner check (`X-Lab-Owner`) |
| Webhook auth | `VerifyWebhookToken` middleware (`X-Webhook-Token`) |
| SSRF | `SsrfGuard` — DNS resolve, block private/loopback/link-local incl. `169.254.169.254`, HTTPS host allowlist |

Toggle: env `FIX=0` skips the above (vulnerable stage only).

## Intentionally broken vs fixed

| Control | Vulnerable (`FIX=0`) | Fixed (`FIX=1`) |
|---------|----------------------|-----------------|
| Upload | Accepts any file (illustrative `.php` text only) | FormRequest allowlist + PHP-tag sniff |
| Webhook URL | Fetches any URL, including metadata mock | `SsrfGuard` + HTTPS allowlist |
| Webhook auth | Token ignored | Middleware requires `X-Webhook-Token` |
| Storage | Client basename under private disk | Random stored name on `local` disk |
| Download | Raw path join (traversal possible) | Path jail + ownership policy |

## Mitigation matrix

| Phase | Control | ATT&CK | Pest test name |
|-------|---------|--------|----------------|
| Immediate | Reject `.php` / executable uploads | T1505.003 | `test_reject_php_upload`, `test_reject_double_extension_php`, `test_reject_php_content_in_txt`, `test_app_upload_rejects_php` |
| Immediate | Webhook shared token | T1190 | `test_webhook_token_required_null`, `test_webhook_token_required_wrong`, `test_webhook_token_accepts_valid`, `test_app_webhook_requires_token` |
| Immediate | Private `Storage::disk('local')` | T1505.003 | `test_private_storage_not_under_webroot`, `test_private_storage_path_name` |
| Short | SSRF guard (metadata / private / allowlist) | T1552.005 | `test_ssrf_block_metadata_http`, `test_ssrf_block_metadata_https`, `test_ssrf_block_loopback`, `test_ssrf_block_private_rfc1918`, `test_ssrf_allowlist_*`, `test_app_webhook_blocks_metadata` |
| Short | Download path jail + ownership (no IDOR) | T1083 / IDOR | `test_no_path_traversal_download`, `test_no_urlencoded_traversal`, `test_download_allows_safe_basename`, `test_download_idor_blocked_for_other_owner` |
| Long | Detection rules (upload / metadata SSRF / outbound-after-upload) | T1505.003, T1552.005, T1071.001 | Manual validation via `detections/` |
| Long | Server-generated names + audit logs | T1070 | Upload handler + Laravel log events |

## API surface

- `GET /health`
- `POST /upload` — multipart `file` (optional header `X-Lab-Owner`)
- `POST /webhook` — JSON `{"callback_url":"..."}` + `X-Webhook-Token` when fixed
- `GET /download?file=<name>` — private storage; ownership when record exists
- `GET /download/{uuid}` — route-model binding + policy

See [ATTACK_DEMO.md](./ATTACK_DEMO.md).

## Anonymization

- No real company names, client data, or production hostnames.
- Metadata mock credentials are explicitly **FAKE**.
- Payloads are illustrative only (`<?php echo 'lab'; ?>`).

## Layout

```
lab/
  docker-compose.yml
  README.md
  ATTACK_DEMO.md
  app/              # Laravel 12 + Pest (vendor via composer install)
  mock-metadata/
  detections/
  vulnerable/
  fixed/
```
