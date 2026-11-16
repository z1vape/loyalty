# Security Reference

Production security checks. For each, the goal is to catch the thing that turns into an incident — a leaked
secret, a root container, a publicly exposed internal service. Report findings with a clear "why it matters"
so the developer can judge the risk, not just see a checkbox.

## Contents
- [Secrets](#secrets)
- [Container & process privileges](#container--process-privileges)
- [Network exposure](#network-exposure)
- [Dependencies](#dependencies)
- [Application hardening](#application-hardening)

## Secrets

The highest-frequency, highest-damage class of finding. Check:

- **Secrets committed to the repo** — scan tracked files for `.env`, API keys, tokens, private keys,
  connection strings with passwords. Check git history too if feasible (`git log -p` for added secrets) —
  a secret removed in a later commit is still in history and must be rotated, not just deleted.
  → *Critical.* A committed secret is a leaked secret; recommend rotating it, not just removing it.
- **`.env` in `.gitignore` and `.dockerignore`** — both, not just one. Missing from `.dockerignore` means
  it gets baked into the image even if git-ignored.
- **Secrets baked into the image** — `ENV API_KEY=...`, `COPY .env`, or secrets passed as build args
  (build args are visible in image history via `docker history`). Inject at runtime instead.
- **Example file present** — a committed `.env.example` with blank values is good practice; confirm it
  exists and that real values aren't in it.

## Container & process privileges

- **Runs as non-root** — Dockerfile creates a user and `USER`s to it; the app process isn't UID 0.
  → *Critical* if root + network-exposed.
- **No `--privileged`, minimal capabilities** — check compose/run flags. Drop caps you don't need.
- **Read-only root filesystem where possible** — `read_only: true` with explicit `tmpfs` for writable paths
  is a strong hardening step for stateless services. → *Warning* if absent, not required.
- **No Docker socket mounted** into app containers unless genuinely required — it's root-equivalent host
  access.

## Network exposure

- **Only the reverse proxy is public** — app/db/cache containers use `expose:` (internal) not `ports:`
  (host-published). A database with a published host port is a common, serious leak.
  → *Critical* for databases, *Warning* for app containers behind a proxy.
- **HTTPS enforced** — TLS terminated (Caddy auto-HTTPS), HTTP redirects to HTTPS, HSTS header set.
  → *Critical* for anything handling auth or user data.
- **CORS not wide-open** — `Access-Control-Allow-Origin: *` on an authenticated API is a finding.

## Dependencies

- **Lockfile committed** — reproducible installs; without it prod can pull different (possibly compromised)
  versions than tested. → *Critical* for build reproducibility.
- **Known vulnerabilities** — run the ecosystem auditor if available: `npm audit --omit=dev`,
  `pip-audit`, `govulncheck`, etc. Report high/critical advisories; don't drown the user in transitive lows.
- **Base image freshness** — pinned but not ancient. A two-year-old base has unpatched CVEs.

## Application hardening

- **Debug/dev mode off in prod** — `NODE_ENV=production`, `DEBUG=False` (Django), no framework dev server
  (`next dev`, `flask run`, `vite`) used as the production server. → *Critical*; dev servers leak stack
  traces and aren't built for load.
- **Security headers** — HSTS, `X-Content-Type-Options`, `X-Frame-Options`/CSP, `Referrer-Policy`. Best set
  at the Caddy layer (see docker-caddy.md) so they apply uniformly.
- **Error responses don't leak internals** — no stack traces or SQL errors returned to clients in prod.
- **Server tokens suppressed** — hide framework/version banners (`-Server` in Caddy, `server_tokens off`).
