# Web Frontend (Next.js)

This package contains the AlFawz Qur'an Institute web client. It is a static-exportable
Next.js 15 application that talks to the Laravel API living in `apps/api`.

## Prerequisites

- Node.js 20+ and npm (matching the versions defined in the root `.nvmrc` if present)
- The Laravel API running locally (`php artisan serve`) or deployed elsewhere
- A configured `.env.local` file based on `.env.example`

## Local development against the Laravel API

1. Start the Laravel backend from the repository root:
   ```bash
   cd apps/api
   composer install
   php artisan serve --host=127.0.0.1 --port=8000
   ```
2. Copy the sample environment file and update it with your API URL and feature toggles:
   ```bash
   cd ../web
   cp .env.example .env.local
   # edit .env.local
   ```
3. Install dependencies and boot the Next.js development server:
   ```bash
   npm install
   npm run dev
   ```
4. Visit [http://localhost:3000](http://localhost:3000) to sign in through the Laravel API.

> **Tip:** The `NEXT_PUBLIC_API_BASE` variable must include the `/api` suffix because the
> Laravel routes are namespaced under that prefix by default.

### Environment variables

All variables in `.env.local` must be prefixed with `NEXT_PUBLIC_` so that the browser can
access them. The sample file documents the most common values:

| Variable | Description | Example |
| --- | --- | --- |
| `NEXT_PUBLIC_API_BASE` | Base URL for the Laravel API routes. | `http://localhost:8000/api` |
| `NEXT_PUBLIC_FEATURE_*` | Opt-in UI feature switches (analytics dashboards, celebratory effects, offline flows, etc.). | `true` / `false` |
| `NEXT_PUBLIC_ENABLE_ANALYTICS` | Enables loading of analytics scripts in production builds. | `false` |
| `NEXT_PUBLIC_ANALYTICS_TOKEN` | Provider-specific key such as a GA4 Measurement ID. | `G-XXXXXXX` |
| `NEXT_PUBLIC_RELEASE_VERSION` | Release identifier used for cache busting. | `2024.05.01` |
| `NEXT_PUBLIC_MAINTENANCE_MODE` | Shows the maintenance banner and blocks mutations. | `false` |

When new public environment variables are added, mirror them in `.env.example` to keep the
team aligned.

## Translation workflow

The app uses [`next-intl`](https://next-intl-docs.vercel.app/) for localisation.

1. Messages live in `messages/en.json` and `messages/ar.json`.
2. Add keys to the English file first, using dot-notation namespaces.
3. Mirror the same keys in the Arabic file and update `src/i18n.ts` if a new locale is added.
4. Run `npm run dev` to verify that the correct copy appears in the UI (the development server
   reloads automatically).

## Building and exporting

The frontend ships as static assets that Laravel serves from `apps/api/public/app`.

```bash
npm run build       # Compile the Next.js app
npm run export      # Generate the static /out directory
```

The repository includes a helper script that automates this from the repository root:

```powershell
./scripts/build-web.ps1
```

The script installs dependencies, builds, exports, and then mirrors the contents of
`apps/web/out` into `apps/api/public/app` using `robocopy`. Deployments that run on Linux can
replicate this behaviour with `rsync -a --delete apps/web/out/ apps/api/public/app/`.

After exporting, commit or deploy the `apps/api/public/app` directory so that Laravel can serve
`/app/index.html` and related static assets.

## Additional scripts

- `npm run lint` – Lints the project using the Next.js shared config.
- `npm run start` – Serves the production build (requires `npm run build`).
