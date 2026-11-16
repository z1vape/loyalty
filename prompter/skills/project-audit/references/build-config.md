# Build & Config Reference

How to detect a stack, find its build/start commands, and check that its build and runtime configuration is
production-grade. Use the detection table first, then the checks.

## Contents
- [Stack detection](#stack-detection)
- [Reproducible builds](#reproducible-builds)
- [Environment configuration](#environment-configuration)
- [Runtime behavior](#runtime-behavior)
- [Observability](#observability)

## Stack detection

| Signal file | Stack | Build command | Production start |
|---|---|---|---|
| `next.config.*` + `package.json` | Next.js | `npm run build` | `next start` / `node server.js` (standalone) |
| `package.json` (no Next) | Node | `npm run build` (if present) | `node dist/index.js` / `npm start` |
| `vite.config.*` | Vite SPA | `npm run build` | static files served by Caddy directly |
| `pyproject.toml` / `requirements.txt` | Python | `pip install` | `gunicorn`/`uvicorn` (not `flask run`/`runserver`) |
| `manage.py` | Django | `collectstatic`, `migrate` | `gunicorn app.wsgi` |
| `go.mod` | Go | `go build` | the compiled binary |
| `Gemfile` | Ruby | `bundle install` | `puma` |
| `pom.xml` / `build.gradle` | Java | `mvn package` | `java -jar app.jar` |

For a Vite/CRA-style SPA there's no app server — the build output is static files Caddy can serve with
`root * /srv` + `file_server`. Note this; it changes the Docker/Caddy shape (no `app:port` to proxy).

## Reproducible builds

- **Lockfile committed and used** — `npm ci` (not `npm install`), `poetry install`, `go mod download`.
  Without a respected lockfile, prod can resolve different versions than were tested. → *Critical*.
- **Pinned runtime version** — `.nvmrc`, `engines` in package.json, `python_requires`, `go` directive in
  go.mod. Prevents "works on my machine" drift.
- **Build actually runs clean** — if you can, run the build command and confirm it succeeds without errors
  or unexpected warnings before declaring it production-ready.

## Environment configuration

- **Env vars validated at startup** — the app fails fast with a clear message when a required var is
  missing, rather than crashing deep in a request or silently using `undefined`. Libraries: `zod`/`envsafe`
  (Node), `pydantic-settings` (Python). → *Warning* if missing.
- **`.env.example` documents required vars** — so a deployer knows what to set. Confirm it lists everything
  the code reads.
- **Config separated from code** — no hardcoded hostnames, ports, or URLs that should be env-driven.
- **Sensible defaults vs required** — distinguish optional config (has a default) from required secrets
  (must be provided, no default).

## Runtime behavior

- **HEALTHCHECK / health endpoint** — a `/health` or `/healthz` that returns 200 when the app can serve
  (including its dependencies if cheap to check). Used by Docker `HEALTHCHECK` and orchestrators. → *Warning*.
- **Graceful shutdown** — handles `SIGTERM` to drain in-flight requests before exiting, so deploys don't
  drop connections. → *Warning*.
- **Resource awareness** — for memory-bound runtimes, limits set in compose (`deploy.resources.limits`) so
  one container can't OOM the host.
- **Restart policy** — `restart: unless-stopped` (or orchestrator equivalent) so a crash self-heals.

## Observability

- **Structured logging to stdout/stderr** — not to files inside the container (they vanish with the
  container and don't reach the platform's log aggregator). JSON logs in prod are easier to query.
  → *Warning*.
- **Log level configurable** — `info`/`warn` in prod, not `debug` (which is noisy and can leak data).
- **No `console.log`/`print` debugging left in hot paths** — minor, but worth a note if pervasive.
