# Troubleshooting

## Cannot access the dashboard

Check that the user exists in the application database and is using the correct email address and password.

The dashboard is available at:

`/dashboard/login`

Two-factor authentication is required. If a user is blocked during first login, verify that they can complete 2FA setup.

## Company did not receive the private profile link

Approving a company access request attempts to send an email with the private profile link. If email sending fails, the dashboard shows a warning.

Check:

- Mail settings in `.env`
- Mail service credentials
- Application logs
- The company access request status
- The contact email address entered in the request

## Private company profile link is expired

Expired profile tokens return `410 Gone`. Approve a new access request or regenerate/resend a valid profile link through the application workflow.

## Company profile changes are not visible

Company profile edits submitted through `/bedrijf-profiel/{token}` are saved as pending submissions. They become visible only after an administrator approves them in `Company profile submissions`.

## Event or company is missing from the public website

Check:

- The event exists and has the correct date.
- The company is linked to the event.
- The company has the expected logo, website, educations, sectors, and description.
- The current event selection logic is choosing the expected event. The site prefers the next upcoming event and falls back to the latest past event.

## Map image or markers look wrong

Check:

- `Page media` has the correct shared event map image.
- Stands have `x_percent` and `y_percent` coordinates.
- Map points have coordinates and belong to the expected event.
- The public `/plattegrond` page is showing the expected event.

## Screenshots in the wiki do not show

Make sure the GitHub wiki repository contains:

- Markdown pages from `docs/wiki/`
- PNG files copied into a `screenshots/` folder in the wiki repository

The wiki pages reference images as `screenshots/name.png`.
