# AlFawz Qur'an Institute

> A comprehensive Qur'an learning platform with interactive assignments, progress tracking, and teacher-student collaboration.

## Project Structure

```
├── apps/
│   ├── api/          # Laravel API backend
│   └── web/          # Next.js frontend
├── scripts/          # Build and deployment scripts
└── .github/          # GitHub workflows
```

## Quick Start

1. Set up Laravel API: `cd apps/api && composer install`
2. Set up Next.js web: `cd apps/web && npm install`
3. Build and deploy: `node scripts/alfawz.mjs deploy`

## Cross-platform scripts

The repository now includes a Node-based helper in `scripts/alfawz.mjs` so the
automation works the same way on macOS, Linux, and Windows. Invoke it with
Node.js from the project root:

```bash
node scripts/alfawz.mjs <command> [options]
```

Available commands:

- `build-web` – install dependencies for `apps/web`, build the Next.js project,
  export it, and mirror the static files to `apps/api/public/app`.
- `deploy` – run the full deployment pipeline (Composer install, optional
  migrations and seeders, Laravel optimisation, build-web export, and basic
  health checks). Supports flags such as `--fresh`, `--seed`, `--skip-web`,
  `--skip-migrations`, `--production`, and `--verbose`.
- `migrate` – run `php artisan migrate --force` in `apps/api`.

The previous PowerShell-only entry points remain in `scripts/` for reference,
but the `alfawz.mjs` commands should be used for all new automation tasks.

## Features

- 🎯 Interactive Qur'an assignments with hotspots
- 📊 Progress tracking and hasanat calculation
- 👥 Class management for teachers and students
- 🎵 Audio feedback and recitation submissions
- 🏆 Leaderboards and achievement system
- 💳 Payment integration with Paystack

---

*Built with Laravel + Next.js for AlFawz Qur'an Institute*