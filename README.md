# 🎓 Bedrijvendag — Student Internship Fair Website (v2.0)

This repository contains Bedrijvendag (v2.0), the web application for organizing and promoting the student internship fair.

The project is built with a modern Laravel backend and a Vue 3 frontend, bundled using Vite and styled with TailwindCSS. This README has been updated for the 2.0 codebase and lists the current requirements, setup steps, and helpful developer commands.

---

## Key technologies (current)

- PHP 8.2+
- Laravel 12
- Vue 3
- Vite (>=7)
- Tailwind CSS (v4+)
- Inertia.js (Vue adapter)
- Filament (admin/CMS)
- TypeScript (dev tooling available)


## Quick features

- Inertia-powered SPA pages using Vue 3
- Filament admin panels and settings
- TailwindCSS UI and utilities
- Vite-powered dev server and fast builds
- Pre-configured dev scripts for a smooth developer experience


## Project layout (important)

Only the most relevant folders you will commonly edit:

- `resources/js/` — Vue components and frontend entry
- `resources/views/` — Blade templates used as Inertia entry points
- `resources/css/` — Tailwind and custom styles
- `routes/web.php` — Main web routes
- `app/Models/` — Eloquent models
- `app/Http/Controllers/` — Laravel controllers


## Requirements

- PHP 8.2 or newer
- Composer
- Node.js (recommended LTS) and npm
- SQLite/MySQL/Postgres (project ships with `database/database.sqlite` used in local setups)


## Setup (development)

1. Clone the repository

```bash
git clone <repo-url> bedrijvendag
cd bedrijvendag
```

2. Install PHP dependencies

```bash
composer install
```

3. Install Node dependencies

```bash
npm install
```

4. Environment

```bash
cp .env.example .env
php artisan key:generate
```

If you use the included SQLite file (convenient for local dev):

```bash
# create sqlite database file if not present
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
```

5. Run migrations and seeders (if any)

```bash
php artisan migrate
```

6. Start development servers

The project includes front- and back-end dev workflows. Two common approaches:

- Start Laravel and Vite separately

```bash
php artisan serve
npm run dev
```

- Or use the composer `dev` script (requires `npx concurrently` available via composer scripts)

```bash
composer run-script dev
```

Visit http://localhost:8000 (or your configured host) after the servers start.


## Useful scripts

From `package.json` (frontend):

- `npm run dev` — Start Vite dev server
- `npm run build` — Build production frontend assets
- `npm run build:ssr` — Build SSR bundles (if used)
- `npm run lint` — Run ESLint and auto-fix
- `npm run format` — Format frontend resources with Prettier

From `composer.json` (project orchestration):

- `composer run-script setup` — Installs PHP & Node deps and runs migrations + build
- `composer run-script dev` — Starts several processes (artisan serve, queue, pail, vite) via concurrently
- `composer run-script test` — Run lint+tests


## Environment notes & tips

- Ensure your local PHP version meets the requirement (PHP 8.2+). Use tools like phpenv, Homebrew, or Docker to manage versions.
- If using Node, a current LTS is recommended (Node 18/20+ at time of writing).
- Tailwind is configured in `resources/css` and `tailwind.config.js`/`pint.json` may contain formatting/linting rules.
- If assets don't reflect changes, try clearing caches:

```bash
php artisan view:clear
php artisan cache:clear
php artisan route:clear
npm run dev # restart vite
```

- If you run into permission issues for `storage` or `bootstrap/cache` folders:

```bash
chmod -R 775 storage bootstrap/cache
```


## Testing

- Run backend tests (Pest/PHPUnit):

```bash
composer test
# or
php artisan test
```

- Linting and formatting:

```bash
npm run lint
npm run format
composer run-script test:lint
```


## Troubleshooting common problems

- Node native dependency errors during `npm install`: install the appropriate optional native binaries for your platform or remove optional dependencies if not needed.
- Database connection errors: double-check `.env` database settings and that the database file exists (for SQLite).
- Filament issues: make sure to run vendor:publish or filament specific upgrade commands if packages were updated.


## Contributing

1. Fork the repo
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Commit: `git commit -m "Add feature"`
4. Push and open a PR


## License

This project is open-source and licensed under the MIT License — see `LICENSE.md`.


## Where to look next

- `routes/web.php` — main app routes
- `resources/js/` — Vue components and Inertia pages
- `app/Filament/` — Filament resources and pages


---

If you'd like, I can also:
- Add small developer scripts (Makefile or a VSCode launch config)
- Create a short CONTRIBUTING.md
- Add a minimal developer checklist to `.github/` for PRs

Tell me which of these you'd like and I'll add it.
