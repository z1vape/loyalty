---
name: project-audit
description: >-
  Audits a project for production readiness and helps containerize it for deployment with Docker and Caddy.
  Use this whenever the user is preparing to ship, deploy, or launch a project — phrases like "is my project
  ready for production", "production checklist", "audit before deploy", "pre-launch check", "harden this for
  prod", as well as "dockerize this", "add Caddy", "set up a reverse proxy", or "containerize for deployment".
  Works across any stack (Node/Next.js, Python, Go, etc.) by detecting the stack first. If the project has no
  Docker setup, it asks whether to add one; if it is already containerized, it audits the existing Docker and
  Caddy config for production. Trigger it even when the user only hints at going live without saying
  "production readiness" explicitly.
---

# Production Readiness

Help a developer get a project ready to ship: find what would break, embarrass, or expose them in
production, report it clearly, and — only with their go-ahead — fix it or generate the missing
Docker/Caddy setup.

The guiding principle is **diagnose before you touch**. The developer knows their project; your job is
to surface the gaps they can't easily see and explain *why* each one matters, then let them decide what to
change. Never silently rewrite their deployment config.

## Workflow

### 1. Detect what you're working with

Before auditing anything, build an accurate picture. Look, don't assume.

- **Stack & runtime**: language, framework, and how it builds/runs. Check manifests like `package.json`,
  `pyproject.toml`/`requirements.txt`, `go.mod`, `Gemfile`, `pom.xml`, etc. Note the build command and the
  start/serve command — you'll need them for the Dockerfile and health checks.
- **Package manager**: lockfile present? (`package-lock.json`, `pnpm-lock.yaml`, `yarn.lock`, `poetry.lock`,
  `go.sum`). A missing lockfile is itself a finding.
- **Containerization**: is there a `Dockerfile`, `docker-compose.yml`/`compose.yaml`, `.dockerignore`?
- **Reverse proxy**: any `Caddyfile`, nginx config, or proxy service in compose?
- **Config & secrets**: `.env*` files, what's committed vs ignored, how env vars are read.

Read `references/build-config.md` for stack-specific build/start commands and detection cues.

### 2. Branch on Docker

This is the fork the user explicitly cares about.

**If the project is NOT containerized** → don't assume they want Docker. Ask:

> This project isn't containerized yet. Do you want me to add a production Docker + Caddy setup as part of
> this, or just audit it as a non-containerized deployment?

- If **yes, dockerize** → continue the audit, and in step 4 propose a Dockerfile, `.dockerignore`,
  `docker-compose.yml`, and a global Caddy reverse-proxy service. Confirm before writing (step 5).
- If **no** → still audit, but scope Docker findings to "here's what containerizing would buy you" rather
  than treating its absence as a failure. Focus on build, security, and config.

**If the project IS containerized** → audit the existing setup for production quality: multi-stage builds,
pinned base images, non-root user, `.dockerignore` coverage, health checks, secret handling, and whether a
proper reverse proxy (Caddy) fronts the app. Read `references/docker-caddy.md`.

### 3. Audit

Run the three areas below. For each finding, record a **severity**, a one-line **what**, and a short
**why it matters in production**. Don't pad the report with passing trivia — surface what's wrong or risky,
and confirm the important things that are right.

- **Security** → `references/security.md` — secrets in the image/repo, non-root user, exposed ports,
  unpinned dependencies, missing security headers, debug modes left on.
- **Build & config** → `references/build-config.md` — reproducible builds (lockfiles), env-var validation,
  build output correctness, `.dockerignore`, health checks, structured logging, graceful shutdown.
- **Docker + Caddy** → `references/docker-caddy.md` — image hardening, multi-stage builds, the global Caddy
  reverse-proxy pattern, automatic HTTPS, and only-the-proxy-is-public networking.

Severity scale:
- **Critical** — will break in prod or exposes a security hole (secrets committed, app runs as root with a
  public port, no HTTPS, build not reproducible).
- **Warning** — works but fragile or below best practice (no health check, fat single-stage image, missing
  resource limits).
- **Pass** — done right; worth a brief mention so the user trusts the audit was thorough.

### 4. Report

Use this structure so the output is skimmable and the priorities are obvious:

```
# Production Readiness Report — <project name>

**Stack:** <detected stack>  ·  **Containerized:** yes/no  ·  **Reverse proxy:** Caddy/none

## Summary
<2-3 sentences: overall state and the single most important thing to fix.>

## Critical  (fix before shipping)
- [Security] <what> — <why it matters>
- [Docker]   <what> — <why it matters>

## Warnings  (should fix soon)
- [Build] <what> — <why it matters>

## Passing
- <brief confirmations of what's already solid>

## Recommended next steps
1. <ordered, concrete actions — the highest-leverage fix first>
```

Lead with the report. Don't start editing files inside this step.

### 5. Offer changes — then confirm before writing

After the report, propose the concrete fixes you can make and the files you'd create (Dockerfile, compose,
Caddyfile, `.dockerignore`, etc.). **Ask before writing or modifying anything**, because deployment config
is high-stakes and the user may have constraints you can't see (existing CI, a target platform, a registry).

A good offer is specific:

> I can fix the criticals for you: add a multi-stage `Dockerfile` (non-root, pinned base), a
> `.dockerignore`, a `docker-compose.yml` with a global Caddy reverse proxy doing automatic HTTPS, and move
> the committed secrets to an ignored `.env`. Want me to apply these? I'll show each file before saving.

When you do write files, generate them from the patterns in `references/docker-caddy.md`, adapted to the
detected stack — don't paste a generic template that doesn't match their build/start commands.

### 6. Produce the deployment runbook

The developer often won't run the setup themselves on the box — they'll hand it to an AI agent on a VPS.
So the final deliverable is a **self-contained markdown runbook another agent can execute end to end**,
written to `DEPLOYMENT.md` at the project root (confirm before writing, like any file).

This document is not the same as the report. The report is for the human ("here's what's wrong"); the
runbook is for a machine ("do exactly this on the server"). Write it so an agent with shell access and no
prior context can follow it deterministically. The format and a worked example are in
`references/agent-runbook.md` — read it before generating. The essentials:

- **State assumptions and prerequisites up front** — OS, that Docker + Compose must be installed, DNS A
  record pointed at the server, ports 80/443 open. Give the install commands, don't assume they're present.
- **Embed the exact file contents** the agent must create (Dockerfile, `.dockerignore`,
  `docker-compose.yml`, `Caddyfile`), generated from the detected stack — not links to them.
- **Mark every value the agent cannot know** with an obvious placeholder (`<YOUR_DOMAIN>`,
  `<DATABASE_URL>`) and list them in one "values you must provide" section so nothing is guessed.
- **Number the steps and make them idempotent** where possible (safe to re-run), with a copy-pasteable
  command for each.
- **End with verification** — concrete checks the agent runs to confirm success (container healthy, HTTPS
  cert issued, app responds), plus a short troubleshooting/rollback section.

## Caddy: default to a global reverse proxy

Unless the user is targeting Kubernetes or multi-host deployment, use **one Caddy container fronting the app
container(s)** on a shared Docker network, rather than bundling Caddy into each app image. On a single host
only one process can bind ports 80/443, central cert management is simpler, and adding a second service is
just another block in the Caddyfile. The app container should *not* expose a host port — only Caddy is
public. Per-container/sidecar Caddy only makes sense when each service is deployed in isolation (k8s pods,
separate hosts); flag that as the exception. Details and ready-to-adapt configs are in
`references/docker-caddy.md`.
