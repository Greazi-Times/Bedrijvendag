# Legal and operational review register

Status key: **V** resolved and verified · **I** implemented, unverified · **O** awaiting organisational approval · **L** awaiting legal review · **P** awaiting production configuration. B-pub = blocks publication, B-dep = blocks deployment.

| # | Item | Policy section | Status | Implementation | Decision needed | Blocks |
|---|---|---|---|---|---|---|
| 1 | Legal controller, KvK, joint control | PP 2, ToS 2 | O/L | placeholders in drafts | name the entity/entities | B-pub |
| 2 | DPO assessment | PP 2 | L | none | written art. 37 assessment | B-pub |
| 3 | Lawful bases and LIAs | PP 5 | L | `docs/privacy/lia.md` drafts | sign off | B-pub |
| 4 | Art. 9 basis for dietary data | PP 12 | L/O | stored by stable key, purged after 7 days, never public | decide who consents or reduce the question | B-pub, data-collection |
| 5 | Retention of other categories | PP 13 | O | access requests 3 months after handling (implemented, proposed); contact form not stored by the site; newsletter while subscribed; invoices proposed 7 years; email delivery logs and server logs undecided | approve | B-pub |
| 6 | Enrolment/borrel retention | PP 13 | O | **default now 0 days (day after the event)**; set `PRIVACY_ENROLLMENT_CONTACT_DAYS` / `PRIVACY_BORREL_DAYS` if a documented purpose supports a grace period | confirm 0 or document purpose | B-dep (first cleanup) |
| 7 | Rejected applications: company name of sole traders | PP 13 | O | company name kept | decide | no |
| 8 | Processors and transfers | PP 15-16 | O | `docs/privacy/processors.md` | collect DPAs, locations | B-pub |
| 9 | Backup retention 28 days | PP 13 | P | app does not manage backups | verify Proxmox/PBS prune policy | B-pub |
| 10 | Production email encryption backfill | PP 14 | P | code + commands tested on SQLite | run on staging then production | B-pub for the claim |
| 11 | Event photography | PP 18, ToS 14 | L/O | practice written as confirmed (notices, objection route); no consent system exists | decide portrait/promotional consent, on-site contact, photographer role | B-pub |
| 12 | Marketing and unsubscribe | PP 10 | I | consent (version, text, source, time) is recorded; signed two-step unsubscribe page exists; `NewsletterSubscriber::unsubscribeUrl()` must be in every promotional mail; the app still sends no newsletters | choose the sending tool and use the link; retention of suppression rows | B-pub for newsletters |
| 13 | Production cookies | Cookie 4, 6 | P | derived from code only | inspect production; assess preference cookies | B-pub |
| 14 | Payment, cancellation, refund | ToS 7, 15 | O | none invented | provide actual terms | B-pub |
| 15 | Liability, court | ToS 18, 22 | L | wording without cap | decide | B-pub |
| 16 | Terms acceptance | ToS 3 | I | enrolment form has a `terms_accept` field (unchecked by default) and stores version, text and time of the **edition** terms; website ToS itself is not separately versioned | decide if website ToS needs its own acceptance | no |
| 17 | MariaDB/MySQL | n/a | **not tested** | no engine available; migration reviewed only | run full migration and tests on a disposable MariaDB | B-dep |
| 18 | Erasure ledger recovery and key rotation | docs | I | ledger keeps ≥35 days (≥ backups + 7), stable `PRIVACY_ERASURE_KEY`, replay refuses without ledger file | place ledger outside restore scope; back up erasure key | B-dep |
| 19 | Production logging settings | PP 13 | P | defaults daily/90; production `.env` may override | check `LOG_STACK`, `LOG_DAILY_DAYS`, `AUDIT_RETENTION_SCHEDULED` | B-dep |
| 20 | Scheduler / cron for cleanup | PP 13 | P | scheduled 03:15; needs `schedule:run` cron | verify | B-dep |
| 21 | Legacy subscribers without consent record | PP 10 | O | rows from before have no text/version | re-confirm or accept | B-pub for newsletters |
| 22 | `users.email` not encrypted | PP 14 | I | documented; see README for recommendation | none now | no |
