# Contributing to AlFawz Qur'an Institute

Thank you for taking the time to contribute! This document outlines how we collaborate so that changes are easy to review, test, and release.

## Branching Strategy

- **Default branch**: `main` is always deployable. Never commit directly to `main`.
- **Feature work**: Create a branch from `main` using the pattern `feature/<short-description>`.
- **Fixes and chores**: Use `fix/<ticket-id>` or `chore/<scope>` as appropriate.
- **Keep branches up to date**: Rebase on top of the latest `main` before opening a pull request (PR) and after addressing review feedback.
- **Small, focused PRs**: Aim for clear scope with high-signal commit messages describing _why_ the change is needed.

## Coding Standards

### `apps/api` (Laravel API)

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding style for PHP.
- Prefer Laravel facades and helpers only when they improve readability; otherwise rely on dependency injection.
- Keep controllers thin—move business logic into service classes or actions.
- Use request form requests for validation and resources/transformers for responses.
- Write PHPUnit feature or unit tests for new behaviors. Place tests under `apps/api/tests` mirroring the namespace of the code under test.
- Run `composer test` and `composer lint` (or the project equivalent) before submitting changes.

### `apps/web` (Next.js Frontend)

- Use TypeScript for new components and hooks.
- Follow the existing folder structure under `apps/web/src` for feature domains.
- Keep components small and composable. Extract reusable logic into hooks under `apps/web/src/hooks`.
- Enforce consistent styling with the configured CSS framework (Tailwind CSS) and utility classes.
- Write unit tests with the existing tooling (e.g., Jest/React Testing Library). Place tests alongside components with the `.test.tsx` suffix.
- Run `npm run lint`, `npm run test`, and `npm run typecheck` (if available) before submitting.

## Pull Request Checklist

Before marking a PR as ready for review:

1. ✅ Rebase your branch onto the latest `main` and resolve conflicts locally.
2. ✅ Ensure code adheres to the standards above.
3. ✅ Run all required checks:
   - `composer install && composer test` and any static analysis commands (e.g., `composer lint`) inside `apps/api`.
   - `npm install` (or `pnpm`/`yarn` depending on the lockfile) and the lint/test/type-check commands inside `apps/web`.
   - Any repository-wide scripts referenced in `/scripts`.
4. ✅ Update documentation, configuration, or environment samples when behavior changes.
5. ✅ Include screenshots or GIFs for UI-affecting changes when possible.
6. ✅ Reference related issues or tickets in the PR description and summarize the change and its impact.

We appreciate your contribution—thank you for helping improve the AlFawz Qur'an Institute platform!
