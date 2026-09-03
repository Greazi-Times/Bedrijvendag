# Dashboard Page Reference

Administrators manage site content from the Filament dashboard at `/dashboard`.

## Dashboard

Path: `/dashboard`

Shows account information, the upcoming event widget, dashboard statistics, borrel enrollment charts, and company-per-event charts. This page is an overview and does not directly change records.

![Dashboard overview](screenshots/dashboard-overview-1920x1080.png)

## Page Media

Path: `/dashboard/page-media`

Controls shared images and media: site logo, event map image, home page media, YouTube link, about page media, and slide images.

Changes appear in the public header/footer, home page, about page, slides page, public event map, and dashboard marker editor.

![Page media](screenshots/page-media-1920x1080.png)

## Companies

Path: `/dashboard/companies`

Manages company records. It changes company names, logos, website URLs, profile contact emails, descriptions, educations, and sectors.

Changes appear in company listings, event pages, map markers, and stand assignment lists once the company is linked to an event or stand.

![Companies](screenshots/companies-1920x1080.png)

## Company Access Requests

Path: `/dashboard/company-access-requests`

Reviews requests submitted by company contacts. Administrators can view, approve, reject, add review notes, and copy private verification links.

Approving attempts to email the company contact a private profile link.

![Company access requests](screenshots/company-access-requests-1920x1080.png)

## Company Profile Submissions

Path: `/dashboard/company-profile-submissions`

Reviews proposed company profile edits submitted through private company profile links. Approving updates the company profile; rejecting leaves the existing profile unchanged.

![Company profile submissions](screenshots/company-profile-submissions-1920x1080.png)

## Events

Path: `/dashboard/events`

Manages editions/events. It changes event name, date, company stand count, partner stand count, organising partners, Google Photos URL, description, header image, related companies, and borrel enrollments.

Event data controls the home page highlight, edition pages, company list, partners page, public map, and borrel sign-up logic.

![Events](screenshots/events-1920x1080.png)

## Event Map

Path: `/dashboard/event-stands`

Manages stand rows, company/partner assignments, stand marker coordinates, map locations, and stand PDF export.

The available stand rows are synced from the selected event's company and partner stand counts.

![Event Map](screenshots/event-map-1920x1080.png)

The lower `Map Locations` table manages non-stand map markers such as bar, entrance, info, lunch, and other.

![Event Map locations table](screenshots/event-map-locations-table-1920x1080.png)

## Borrel Enrollments

Path: `/dashboard/borrel-enrollments`

Manages borrel registrations by event, name, and email address.

![Borrel enrollments](screenshots/borrel-enrollments-1920x1080.png)

## Partners

Path: `/dashboard/partners`

Manages partner name, website URL, description, related educations, and logo/image.

![Partners](screenshots/partners-1920x1080.png)

## Education

Path: `/dashboard/education`

Manages study programmes, descriptions, website URLs, and display colors.

![Education](screenshots/education-1920x1080.png)

## Sectors

Path: `/dashboard/sectors`

Manages business sector names and descriptions.

![Sectors](screenshots/sectors-1920x1080.png)

## Newsletter Subscribers

Path: `/dashboard/newsletter-subscribers`

Shows newsletter sign-ups. Creating, editing, and deleting are disabled.

![Newsletter subscribers](screenshots/newsletter-subscribers-1920x1080.png)

## Policy Pages

Paths:

- `/dashboard/manage-privacy-policy`
- `/dashboard/manage-terms-of-service`
- `/dashboard/manage-cookie-policy`

These pages change the public privacy policy, terms of service, and cookie policy pages.

![Privacy policy](screenshots/privacy-policy-1920x1080.png)

![Terms of service](screenshots/terms-of-service-1920x1080.png)

![Cookie policy](screenshots/cookie-policy-1920x1080.png)

## My Profile

Lets dashboard users manage their profile, password, browser sessions, and two-factor authentication settings.

![My Profile](screenshots/profile-1920x1080.png)

## Navigation groups

- `Dashboard` - statistics, upcoming event widget, companies per event chart, and borrel enrollments chart.
- `Companies` - company records, logos, descriptions, websites, educations, and sectors.
- `Company access requests` - public access requests from company contacts.
- `Company profile submissions` - proposed profile edits from private company links.
- `Events` - event records, event details, related companies, and related borrel enrollments.
- `Event Map` - stand map and marker placement for event stands.
- `Borrel enrollments` - borrel sign-up records.
- `Partners` - partner records used on the partners page and event stands.
- `Education` - study programmes used for filtering and company/partner metadata.
- `Sectors` - business sectors used for filtering and company metadata.
- `Newsletter subscribers` - read-only list of newsletter sign-ups.
- `Page media` - site logo, event map, home page media, about page media, YouTube link, and slide images.
- `Manage privacy policy` - privacy policy content.
- `Manage terms of service` - terms of service content.
- `Manage cookie policy` - cookie policy content.
- `My Profile` - account profile and two-factor authentication settings.
