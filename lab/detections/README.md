# Sample detection rules

Illustrative Sigma / correlation YAML for the local defensive lab.

| File | Purpose | ATT&CK |
|------|---------|--------|
| `php_upload_attempt.yml` | Rejected or attempted PHP upload | T1505.003 |
| `ssrf_metadata_access.yml` | Webhook SSRF to link-local metadata | T1552.005 |
| `unusual_outbound_after_upload.yml` | Upload then unusual outbound fetch | T1105 / T1071.001 |

These rules are **not** production-tuned. Map fields to your SIEM before any real deployment.
