# Security findings & case studies — LiveY

[![Lab tests](https://github.com/S1b-Team/security-portfolio/actions/workflows/lab-tests.yml/badge.svg)](https://github.com/S1b-Team/security-portfolio/actions/workflows/lab-tests.yml)

Open Intelligence Engineer / Security Manager. A running portfolio of my risk-level security findings and incident case studies. Each entry is anonymized where it involves client systems; my own open-source projects are named. Drafts.

| # | Title | Type | Severity | ATT&CK / CWE | Status | Link |
|---|---|---|---|---|---|---|
| 1 | Backoffice compromise: web shell → cloud credentials (Laravel on AWS) | Incident case study + response leadership | Critical | T1190, T1505.003, T1059, T1136, T1552.001/.005, T1550.004, T1528, T1003, T1213 | Contained; remediation roadmap in progress | [case-studies/backoffice-compromise.md](case-studies/backoffice-compromise.md) |
| 2 | Hardening capture storage encryption & key handling in int3rceptor | Source-code security review + fix (my own OSS, `S1b-Team/int3rceptor`) | Medium (CVSS 6.1; CA key exposure High on multi-user hosts) | T1552.004, T1557, T1552.001; CWE-311, CWE-636, CWE-732, CWE-1188, CWE-540, CWE-248 | Fixed — PR #26 merged Oct 16, 2026 (incl. review hardening) | [findings/int3rceptor-storage-encryption.md](findings/int3rceptor-storage-encryption.md) |
| 3 | Unbounded `/dev/urandom` read in CSRF manager → memory-exhaustion DoS in int3rceptor | Source-code security review + fix (my own OSS, `S1b-Team/int3rceptor`) | Medium (CVSS 5.5, availability only) | T1499; CWE-400, CWE-770 | Fixed — PR #27 merged Oct 16, 2026 | [findings/int3rceptor-csrf-urandom-dos.md](findings/int3rceptor-csrf-urandom-dos.md) |

## Notes
- Entry 1 is fully anonymized (no company, client, person, IP, domain, or secret) and ships with a reproducible Docker lab — see `../lab/` and the anonymization checklist at [ANONYMIZATION_CHECKLIST.md](ANONYMIZATION_CHECKLIST.md).
- Entries 2 and 3 concern my own public project, so they are named and link to the upstream PRs.
- Draft only — nothing here is pushed or published.

## Legend
- **Type:** incident case study, source-code review, or finding.
- **Severity:** honest, context-aware (environment matters — e.g. the CA-key finding is High on shared hosts, Medium otherwise).
- **Status:** current state of remediation (contained / in progress / PR open / fixed & merged).
