# Deployment Runbook Reference

How to generate `DEPLOYMENT.md` — the markdown document the developer hands to an AI agent running on a VPS
so it can stand up the project in production without further context.

## What makes a runbook agent-executable

An agent on the box has shell access but **no memory of your conversation, the codebase, or your
intentions**. Every assumption you leave implicit becomes a place where the agent guesses or stalls. So:

- **Be deterministic.** One correct path, ordered steps, exact commands. Avoid "you could also…"; pick the
  recommended approach and commit to it.
- **Embed, don't reference.** Put full file contents inline in fenced code blocks with the target path as
  the heading. The agent shouldn't have to fetch anything.
- **Make placeholders impossible to miss.** Anything the agent can't derive — domain, secrets, registry —
  is an `<UPPER_SNAKE_IN_ANGLE_BRACKETS>` token, and every one is also listed in a single "Values you must
  provide" table near the top. If a value isn't there, the agent should stop and ask, not invent it.
- **Prefer idempotent commands.** `mkdir -p`, `docker compose up -d` (re-runnable), writing files with
  heredocs that overwrite cleanly. The agent may run the doc twice; that should be safe.
- **Verify, don't assume.** Each phase ends with a check whose output tells the agent whether to proceed.

## Required structure

Generate `DEPLOYMENT.md` with these sections in order. Fill them from the detected stack and the files you
generated in step 5 — keep them consistent (same image names, ports, service names).

```markdown
# Deployment Runbook — <project name>

> Instructions for an AI agent with shell access on the target VPS. Execute the steps in order. Where a
> value is written as `<LIKE_THIS>`, substitute the real value from the table below; if it is not provided,
> stop and request it rather than guessing.

## Overview
- **Stack:** <detected stack and runtime>
- **Architecture:** <app container behind a global Caddy reverse proxy, automatic HTTPS, etc.>
- **Target:** single Linux host (<assumed distro, e.g. Ubuntu 22.04+>)

## Values you must provide
| Placeholder | Meaning | Example |
|---|---|---|
| `<YOUR_DOMAIN>` | Public domain, DNS A record already pointing at this server | app.example.com |
| `<ADMIN_EMAIL>` | Email for Let's Encrypt / Caddy ACME registration | you@example.com |
| `<DATABASE_URL>` | App secret(s); one row per required env var | postgres://… |

## Prerequisites
Run these checks/installs first. Stop if any cannot be satisfied.
1. Confirm OS and that you have sudo: `cat /etc/os-release && id`
2. Install Docker Engine + Compose plugin if absent:
   ```bash
   command -v docker >/dev/null || curl -fsSL https://get.docker.com | sh
   docker compose version
   ```
3. Confirm DNS resolves to this host: `dig +short <YOUR_DOMAIN>` should return this server's public IP.
4. Confirm ports 80 and 443 are free and allowed through the firewall.

## Step 1 — Create the project directory and files
```bash
mkdir -p /opt/<project> && cd /opt/<project>
```
Then create each file exactly as below.

### `/opt/<project>/Dockerfile`
```dockerfile
<full Dockerfile generated for the detected stack>
```

### `/opt/<project>/.dockerignore`
```
<full .dockerignore>
```

### `/opt/<project>/docker-compose.yml`
```yaml
<full compose: app uses expose: not ports:, caddy publishes 80/443, caddy_data volume>
```

### `/opt/<project>/Caddyfile`
```caddy
<YOUR_DOMAIN> {
    reverse_proxy app:<port>
    # security headers, encode, etc.
}
```

## Step 2 — Provide secrets
Create `/opt/<project>/.env` with the values from the table above. Never commit this file.
```bash
cat > /opt/<project>/.env <<'EOF'
DATABASE_URL=<DATABASE_URL>
# one line per required variable
EOF
chmod 600 /opt/<project>/.env
```

## Step 3 — Bring up the stack
```bash
cd /opt/<project>
docker compose up -d --build
```

## Step 4 — Get the application code onto the host
<If building from source: how to clone/copy the repo into /opt/<project> before Step 3.
 If deploying a prebuilt image: docker pull / registry login instructions. Pick the one that matches
 the project and put it in the right order relative to Step 3.>

## Verification
Run each; all must pass before declaring success.
1. Containers healthy: `docker compose ps` — every service `Up`/`healthy`.
2. App reachable internally: `docker compose exec caddy wget -qO- http://app:<port>/ | head`.
3. HTTPS works and cert issued: `curl -sS -o /dev/null -w "%{http_code}\n" https://<YOUR_DOMAIN>` → 200.
4. Logs clean: `docker compose logs --tail=50 app` shows the server listening, no fatal errors.

## Troubleshooting & rollback
- **Cert not issued:** check `docker compose logs caddy` for ACME errors; verify DNS and that 80/443 are
  reachable from the internet (Let's Encrypt must reach the host).
- **App restarting:** `docker compose logs app` — usually a missing env var from the table.
- **Rollback:** `docker compose down` stops everything; the previous image tag can be restored by pinning
  it in `docker-compose.yml` and re-running `docker compose up -d`.
```

## Notes

- Adapt the directory (`/opt/<project>`) and steps if the user named a different target; keep it consistent
  throughout the doc.
- For a static SPA (no app server), the runbook has no `app` service — Caddy serves the build output
  directly (`root * /srv` + `file_server`); adjust Step 1 and verification accordingly.
- If the target is k8s / managed PaaS rather than a single VPS, this single-host runbook doesn't apply —
  say so and describe the platform-appropriate path instead.
