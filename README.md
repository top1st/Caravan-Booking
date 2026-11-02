# Workshop Bookings

Service bay bookings for a caravan dealer: a Laravel + SQLite JSON API (`api/`) and a Next.js App Router frontend (`web/`). No auth, by design of the exercise.

## Setup

Requires PHP 8.3+ with `pdo_sqlite`, Composer, and Node 22+.

```bash
cd api
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --force
php artisan db:seed --force   # optional: 10 example bookings, skipped if bookings already exist
php artisan serve --host=127.0.0.1 --port=8000
```

In a second terminal:

```bash
cd web
npm ci
cp .env.local.example .env.local
npm run dev
```

Open http://localhost:3000/bookings. `LARAVEL_API_URL` is server-only; the browser never calls Laravel, so no CORS is needed.

## Commands

| Where | Command | What it does |
|---|---|---|
| `api/` | `php artisan test` | Pest suite, including the multi-process race test |
| `web/` | `npx next typegen && npx tsc --noEmit` | Typecheck |
| `web/` | `npm run lint` | ESLint |
| `web/` | `npm run build && npm start` | Production build and server |

## API contract

| Method | Route | Result |
|---|---|---|
| GET | `/api/bookings?status=confirmed&page=2` | `200` Laravel paginator, 15 per page, newest created first, `id` as tiebreaker. Invalid `status` or `page` is `422`. |
| POST | `/api/bookings` | `201` booking |
| GET | `/api/bookings/{id}` | `200` booking, or `404 {message}` |
| PUT | `/api/bookings/{id}` | `200` booking; all fields required |
| PATCH | `/api/bookings/{id}` | `200` booking; only the fields sent are validated and changed |
| DELETE | `/api/bookings/{id}` | `204`; physical delete |

Validation and overlap errors are `422 {message, errors: {field: [message]}}`. An overlap is reported on both `starts_at` and `service_bay`. If the write lock still cannot be obtained after retries, the response is `503` and nothing is written.

**Overlap:** same bay, both non-cancelled, and `existing.starts_at < new.ends_at AND existing.ends_at > new.starts_at`. Adjacent slots are allowed. Bay names are compared case-insensitively and trimmed, so `bay 1` and `Bay 1` are one bay.

**Time policy:** the API accepts ISO-8601 with a required offset (`2026-12-01T09:00:00Z` or `+10:00`), rejects anything without one, stores UTC, and always returns `YYYY-MM-DDTHH:MM:SSZ`. In the browser, `datetime-local` values are read as the browser's timezone and converted to UTC by the client before submitting, because the server cannot know the viewer's zone. Times are displayed in the browser's zone with the zone named. Server-rendered HTML shows UTC, labelled, until hydration.

## Senior write-up

**Caching/revalidation:** Bookings and bay availability change frequently, so the server fetches list and detail with `cache: 'no-store'`. This favours correctness and avoids stale availability in a small module. After a Server Action mutation, we revalidate the affected paths and redirect through the App Router, so updated data appears without a full browser reload. There are no optimistic updates. At greater scale I'd use scoped tags and explicit invalidation where safe.

**Simultaneous bookings:** Every create and update first takes SQLite's write lock by updating a singleton `booking_write_locks` row inside the transaction, *then* checks for overlaps and writes. SQLite serializes writers, so two requests cannot both pass the check and commit the same bay and time. A plain SELECT-then-INSERT races, confirmed by running the race test against one: all 8 simultaneous requests returned `201`. Removing only the lock did not double-book but caused spurious `503`s for valid requests. This protects only writes that use this path; raw SQL from elsewhere bypasses it. A future PostgreSQL version could use an exclusion constraint so the database enforces the rule itself.

**200 dealers / multi-tenancy:** Add `dealer_id` to bookings and bays, derive the tenant from the authenticated user (never a client-supplied ID), and scope every query and overlap check to it. Introduce dealer-owned `service_bays` with authorization and tenant-isolation tests, then move from SQLite to PostgreSQL, because one global writer lock would serialize all dealers. Index `(dealer_id, service_bay, starts_at)` and monitor the hot booking paths.

## AI tools

Built with Claude Code (Claude Opus 5.5), which wrote the code and tests from a written plan and ran the checks.

**Where the AI got it wrong:** its first race test started 8 processes at once, and it passed. Re-running it with the lock and transaction deleted also passed, so the test was proving nothing: the gap between check and insert was too small to hit. The fix was a 200ms pause after the overlap query, in the test worker only. The same test now fails against unprotected code, with all 8 requests returning `201`, and passes against the real code.

## Decisions worth reviewing

- **Real 404 versus a loading state.** A `loading.tsx` above a page starts streaming, which makes `notFound()` respond with HTTP 200. So `loading.tsx` covers only the list (via the `(list)` route group), and detail and edit have none: they return a true 404, but show no loading indicator during navigation. `cacheComponents` is off for the same reason.
- **Delete takes no write lock.** Removing a booking can only free capacity, so it cannot create an overlap.
- **The race test widens the race window.** Its worker sleeps 200ms after the overlap query, in the test only. Without that the window is too small to hit, and the test passed even with no protection at all.

## Known gaps

- `service_bay` is free text, so a typo creates a new bay. Only case and surrounding whitespace are normalized.
- Any two simultaneous PUTs to one booking are last-write-wins; there is no optimistic version check.
- The frontend has no automated tests. It was checked by hand in a browser against the production build.
- No screen reader testing. Labels, `aria-invalid`, `aria-describedby` and `role="alert"` are present and confirmed in the DOM only.
- The race test was run on Windows only, and takes about 13 seconds.
