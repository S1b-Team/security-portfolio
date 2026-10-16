# LiveY — Security Findings & Case Studies

[![Lab tests](https://github.com/S1b-Team/security-portfolio/actions/workflows/lab-tests.yml/badge.svg)](https://github.com/S1b-Team/security-portfolio/actions/workflows/lab-tests.yml)

## About me

I'm LiveY, an Open Intelligence Engineer and Security Manager. I lead incident response end to end, from triage and containment to the remediation roadmap and stakeholder communication. I review code with both a purple-team and a red-team mindset, and I don't call a fix done until a test fails before it and passes after. More about me: https://ind4skylivey.github.io/about/

## Index

| # | Title | Type | Severity | ATT&CK / CWE | Status | Link |
|---|---|---|---|---|---|---|
| 1 | Backoffice compromise: web shell → cloud credentials (Laravel on AWS) | Incident case study + response leadership | Critical | T1190, T1505.003, T1059, T1136, T1552.001/.005, T1550.004, T1528, T1003, T1213 | Contained; remediation roadmap in progress | [case-studies/backoffice-compromise.md](portfolio/case-studies/backoffice-compromise.md) |
| 2 | Hardening capture storage encryption & key handling in int3rceptor | Source-code security review + fix (my own OSS, `S1b-Team/int3rceptor`) | Medium (CVSS 6.1; CA key exposure High on multi-user hosts) | T1552.004, T1557, T1552.001; CWE-311, CWE-636, CWE-732, CWE-1188, CWE-540, CWE-248 | Fixed — PR #26 merged Oct 16, 2026 (incl. review hardening) | [findings/int3rceptor-storage-encryption.md](portfolio/findings/int3rceptor-storage-encryption.md) |
| 3 | Unbounded `/dev/urandom` read in CSRF manager → memory-exhaustion DoS in int3rceptor | Source-code security review + fix (my own OSS, `S1b-Team/int3rceptor`) | Medium (CVSS 5.5, availability only) | T1499; CWE-400, CWE-770 | Fixed — PR #27 merged Oct 16, 2026 | [findings/int3rceptor-csrf-urandom-dos.md](portfolio/findings/int3rceptor-csrf-urandom-dos.md) |

Entry 1 is fully anonymized. Entries 2 and 3 cover my own open-source project and link to the upstream PRs. More detail is in [portfolio/README.md](portfolio/README.md).

## Run the lab

The lab is an isolated Laravel 12 backoffice with vulnerable (`FIX=0`, :8081) and fixed (`FIX=1`, :8080) modes, plus a **fake** metadata sidecar. It only talks to localhost and the compose network.

With Docker:

```bash
cd lab
make up     # build and start fixed, vulnerable, and fake-metadata services
make test   # run the Pest security regressions in a container
make down   # stop everything and remove volumes
```

Without Docker (PHP 8.2+ and Composer):

```bash
cd lab/app
composer install
FIX=1 vendor/bin/pest --filter=LabSecurity
```

CI runs the same Pest suite on every push and pull request (`.github/workflows/lab-tests.yml`). See [lab/README.md](lab/README.md) and [lab/ATTACK_DEMO.md](lab/ATTACK_DEMO.md) for details.

## Repository layout

- `portfolio/`: case studies and findings
- `lab/`: reproducible Docker lab and detections
- `SECURITY.md`: how to report a vulnerability
- `LICENSE`: GPL-3.0
