# Anonymous feedback

Every edition has exactly two anonymous surveys: **students** and **companies**. They are created automatically with the
edition (inside the same database transaction; if that fails the edition is not saved), as drafts, from independent
copies of the two global templates.

## How it fits together

| Concern | Where |
| --- | --- |
| Questions, versions, validation | the existing form builder: `Form` / `FormVersion` / `FormEngine` (kinds `feedback_student`, `feedback_company`, new field type `rating`) |
| Survey (on/off switch, schedule, public link) | `FeedbackSurvey`, unique per `(event_id, audience)`; status from `App\Support\Availability`, which enrollment uses too |
| Answers | `FeedbackResponse` (survey, form version, **day**) and `FeedbackAnswer` (one row per answered question) |
| Provisioning, reset, switching on/off | `App\Services\FeedbackSurveys` (idempotent); `php artisan feedback:provision` backfills |
| Submission | `App\Services\FeedbackSubmissions` |
| Statistics | `App\Services\Analytics\FeedbackStats` (SQL aggregation) |
| Admin | Edition → **Feedback** tab; Forms → **Feedback templates**; Edition → **Feedback results** |
| Public | `GET/POST /feedback/{edition id}/students` and `/companies` |

Global templates are the two `Form` rows with `system_key` `default_student_feedback` / `default_company_feedback`.
## Switching on and off

Like enrollment, each survey has a manual **switch** (`enabled`, off for new surveys) and optional **opens at / closes at**
moments, edited under Edition → Feedback → *Settings* (shown and entered in Europe/Amsterdam, stored in UTC). The
effective status is calculated in one place, `Availability::state()`:

1. switched off → **Disabled** (always wins over the dates; the public link is a 404 and submissions are rejected);
2. opening moment in the future → **Scheduled**;
3. closing moment passed → **Closed**;
4. otherwise **Open**.

A closing moment must be after the opening moment. Switching or scheduling never creates a questionnaire version;
versions only come from publishing questions in the form editor.

## QR codes

`App\Support\Feedback\QrCode` draws the code from the real QR matrix (error correction H) in ATIx orange with rounded
modules, softly rounded finder patterns and the gear of `public/favicon.svg` in the centre (about 5% of the area; the
modules under it are left out). SVG is the print format; PNG needs the `imagick` extension. The code only depends on the
public link, so it is the same whether the form is on, off or closed. Finder rounding is deliberately mild: strongly
rounded finders stopped an OpenCV decoder from locking on.

Publishing a template only affects editions created afterwards. "Reset to default template" on an edition survey
publishes the current template as a **new version** of that survey's form; older versions and responses stay.

## Privacy

Stored with a response: the survey, the form version and the **date** (no time). Not stored: user, company, enrollment,
email, IP address, user agent, fingerprint, invitation token, session id. A rejected submission never flashes the typed
answers into the session. The public page has no analytics or third-party scripts.

What this does **not** cover (do not claim otherwise):

* The web server, Cloudflare and any reverse proxy keep their own access/security logs, which contain IP addresses and
  timestamps for a limited time. They are outside the application database.
* The throttle (`throttle:feedback`, 120 requests/minute per IP) keeps a counter in the cache for one minute.
* Free-text answers may contain personal data typed by the respondent. Administrators can **redact** (erase the text) or
  **remove** a written answer under *Feedback results*. The form tells respondents not to include personal data.
* Anonymous, link-based collection cannot prevent one person from answering more than once, and cannot verify that a
  respondent is a student or represents a participating company. Spam is limited by a honeypot field, the throttle,
  server-side validation and a 64 KB request limit.

Retention: `config/privacy.php` → `retention.feedback_days_after_event` (`PRIVACY_FEEDBACK_DAYS`). Empty (default) means
responses are kept until deleted by hand; **the period still has to be decided with the data controller.** When set,
`privacy:cleanup` deletes the responses of editions that ended more than that many days ago.

## Statistics rules

Answers are only added together when a question has the same stable key **and** the same signature (type, rating scale,
answer option values). Rewording keeps the signature; widening a 1–5 scale to 1–10 creates a separate result. Percentages
use the number of people who answered the question; "not applicable" is counted separately from skipped questions, and
averages never include it.

## Permissions

`feedback.view` (results, QR code, link), `feedback.update` (publish, schedule, reset), `feedback.delete` (redact/remove
written answers). Editing the questions needs `forms.update`.
