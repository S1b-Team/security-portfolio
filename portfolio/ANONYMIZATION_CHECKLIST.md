# Anonymization Checklist

Every real identifier found in the source material (incident PDF, `/workspace/SEC/area/`, and the consolidated incident notes used as background) and the generic substitute used in the drafts. I verified none of the real values appear in `CASE_STUDY.md` or `CV_BULLET.md`.

## Companies / organizations
| Real (in sources) | Substitute in drafts |
|---|---|
| Security vendor / report author company | "I" / "my team" / "the security team" (never named) |
| Dealer importer / platform owner company | "the dealer network" / "the business" |
| Cloud-account administrator company | "the team that administers the cloud account" |
| Financing partner company | "a financing partner" |
| Third-party messaging provider | "messaging webhook / messaging provider" |
| Email delivery provider | "email" / "email delivery" |
| File-storage provider | "file storage" |
| E-commerce platform / catalog site | "the e-commerce catalog" / "marketing/catalog site" |
| Observability / monitoring platform | (omitted; referenced generically if at all) |
| Payment-config brand seen in logs | "payment settings" (noted no gateway in use) |
| Source-control / deploy tooling brand | "source control / deploy tooling" |
| OS / language / framework exact EOL versions | "Laravel on AWS" (specific EOL versions omitted) |

## People
| Real | Substitute |
|---|---|
| Report author / CTO (person) | "the cloud-account administrators" / "leadership" (not named) |
| The practitioner's private name | **LiveY** only |
| Attacker self-label | "the actor" / "the attacker" (self-chosen label not reproduced) |
| Staff / dealer individuals | "staff" / "dealer staff" |

## Technical identifiers
| Real (category) | Substitute |
|---|---|
| Company email address | removed entirely |
| Fake attacker emails on a client domain | removed entirely |
| Phone number | removed |
| All public IP addresses (attacker source IPs, host/backoffice IP, office/Power BI IP) | removed; only generic concepts kept |
| Internal host name / private IP | "the production host" |
| Domains (dealer site, importer, vendor, payment, storage, messaging) | removed; described generically |
| Ticket / issue IDs (incident ref, BIT-*, SEC-*, SENTRY issue numbers) | removed |
| Repository names / branch names / commit hashes | removed |
| Config file paths, class/method names, line numbers | removed (kept capability-level only) |
| Artifact/loot file names, upload file name | removed |
| Secrets, API keys, tokens, app key, password hashes | never reproduced; referred to as "secrets"/"credentials" |
| Cloud ARNs, account IDs, instance IDs | none present / none reproduced |
| Database / table / column names | removed |

## Sensitive facts handled carefully (kept, de-identified)
- Customer PII categories (names, national IDs, vehicle IDs, contact, financing) described as categories only — no actual records, counts tied to names, or values.
- The three complete payment documents referenced as a category, not quoted.
- "No payment gateway → no payment diversion" kept because it is reassuring and non-identifying.
- Only one address class retained as a technical concept: the **cloud instance metadata address** (a well-known fixed address, not client-specific). No client IPs retained.

## Leakage check
- Ran a scan of the drafts for the real IPs, the real email/domain strings, ticket IDs, commit hashes, company names, person names, and file/artifact names from the sources: **no matches** in `CASE_STUDY.md` or `CV_BULLET.md`.
- No third-party product or vendor brand mentions in the deliverables.

## Residual risk notes
- The generic framing ("motorcycle dealer backoffice," "Laravel on AWS") is deliberately common; combined with the mid-October timing it is low-risk but non-zero if cross-referenced with an insider who already knows the incident. Year omitted from identifiers per brief.
- `./lab/` is referenced but not inspected by me; whoever finalizes it must apply this same checklist before publication.
