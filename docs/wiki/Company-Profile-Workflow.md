# Company Profile Workflow

## Requesting access

1. A company contact goes to `/bedrijf-toegang`.
2. They choose whether they represent an existing company or a new company.
3. They enter the requested company and contact information.
4. They submit the form.

The request is stored as pending and appears in the dashboard under `Company access requests`.

## Reviewing access requests

1. An administrator logs in at `/dashboard/login`.
2. They open `Company access requests`.
3. They review the request.
4. They approve or reject it.

Approving sends the requester a private company profile link when mail settings are working.

## Editing a profile

1. The company contact opens the private `/bedrijf-profiel/{token}` link.
2. They update company name, logo, website, description, educations, and sectors.
3. They submit the changes.

The changes create a pending company profile submission. They are not immediately visible on the public website.

## Reviewing profile submissions

1. An administrator opens `Company profile submissions`.
2. They inspect the proposed profile changes.
3. They choose `Approve changes` or `Reject`.
4. They optionally add a review note.
5. They confirm the action.

Approving applies the proposed company fields and emails the company contact when mail settings are working.
