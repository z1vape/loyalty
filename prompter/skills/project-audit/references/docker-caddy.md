# Docker + Caddy Reference

Patterns for containerizing an app for production and fronting it with a global Caddy reverse proxy.
Adapt every snippet to the detected stack and the project's real build/start commands — don't paste
verbatim if the commands differ.

## Contents
- [Docker production checklist](#docker-production-checklist)
- [Multi-stage Dockerfile patterns](#multi-stage-dockerfile-patterns)
- [.dockerignore](#dockerignore)
- [Global Caddy reverse proxy](#global-caddy-reverse-proxy)
- [docker-compose with Caddy](#docker-compose-with-caddy)
- [When NOT to use global Caddy](#when-not-to-use-global-caddy)

## Docker production checklist

Audit existing Dockerfiles against these; generate new ones to satisfy them.

- **Multi-stage build** — build deps and toolchains stay in the build stage; the final image carries only
  the runtime artifact. Keeps images small and shrinks attack surface.
- **Pinned base image** — `node:22.11-slim`, `python:3.12-slim`, `golang:1.23` → scratch/distroless. Avoid
  bare `:latest`; it makes builds non-reproducible and silently drifts.
- **Non-root user** — create and `USER` a non-privileged account. A container breakout as root is far worse
  than as an unprivileged user.
- **No secrets baked in** — never `COPY .env` or `ENV SECRET=...`. Inject at runtime via compose `env_file`
  or orchestrator secrets.
- **`.dockerignore` present** — keeps `node_modules`, `.git`, `.env`, build caches out of the build context
  (faster builds, no accidental secret leakage).
- **HEALTHCHECK** — lets the orchestrator know when the app is actually serving, not just started.
- **Explicit `EXPOSE` for documentation**, but the app port is published only to the internal network, not
  the host (Caddy is the only public service).
- **Minimal layers / clean apt caches** — `rm -rf /var/lib/apt/lists/*` after installs.

## Multi-stage Dockerfile patterns

### Next.js (standalone output)
Set `output: "standalone"` in `next.config.js` so the runtime stage needs only the traced server.

```dockerfile
# ---- build ----
FROM node:22.11-slim AS builder
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---- run ----
FROM node:22.11-slim AS runner
WORKDIR /app
ENV NODE_ENV=production
RUN addgroup --system --gid 1001 nodejs \
 && adduser  --system --uid 1001 nextjs
COPY --from=builder /app/public ./public
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static
USER nextjs
EXPOSE 3000
HEALTHCHECK --interval=30s --timeout=3s --start-period=20s \
  CMD node -e "fetch('http://localhost:3000/').then(r=>process.exit(r.ok?0:1)).catch(()=>process.exit(1))"
CMD ["node", "server.js"]
```

### Generic Node (non-Next)
Same shape: `npm ci` in build, copy only `dist`/`build` + production deps into a slim runner, non-root,
`CMD ["node", "dist/index.js"]`.

### Python
```dockerfile
FROM python:3.12-slim AS base
ENV PYTHONUNBUFFERED=1 PIP_NO_CACHE_DIR=1
WORKDIR /app
COPY requirements.txt .
RUN pip install --prefix=/install -r requirements.txt
# runtime
FROM python:3.12-slim
WORKDIR /app
RUN useradd --system --uid 1001 appuser
COPY --from=base /install /usr/local
COPY . .
USER appuser
EXPOSE 8000
CMD ["gunicorn", "-b", "0.0.0.0:8000", "app:app"]
```

### Go (distroless)
```dockerfile
FROM golang:1.23 AS build
WORKDIR /src
COPY go.mod go.sum ./
RUN go mod download
COPY . .
RUN CGO_ENABLED=0 go build -o /app ./cmd/server
FROM gcr.io/distroless/static-debian12
COPY --from=build /app /app
USER nonroot:nonroot
EXPOSE 8080
ENTRYPOINT ["/app"]
```

## .dockerignore

```
.git
node_modules
.next
dist
build
*.env
.env*
**/__pycache__
.venv
*.log
.DS_Store
Dockerfile
docker-compose*.yml
README.md
```

## Global Caddy reverse proxy

One Caddy service fronts every app container. Caddy handles **automatic HTTPS** (Let's Encrypt) — no manual
certs, no cron renewals.

**Caddyfile** (place at project root or `./caddy/Caddyfile`):

```caddy
example.com {
    encode zstd gzip
    reverse_proxy app:3000

    header {
        Strict-Transport-Security "max-age=31536000; includeSubDomains"
        X-Content-Type-Options "nosniff"
        X-Frame-Options "DENY"
        Referrer-Policy "strict-origin-when-cross-origin"
        -Server
    }
}
```

`app:3000` is the compose service name + internal port — Docker's DNS resolves it on the shared network.
Add more `domain { reverse_proxy other-svc:port }` blocks to onboard additional services.

For **local testing without a real domain**, use `http://localhost` (Caddy serves plain HTTP and skips
ACME), or `tls internal` for a self-signed cert.

## docker-compose with Caddy

```yaml
services:
  app:
    build: .
    restart: unless-stopped
    env_file: .env            # secrets injected at runtime, never baked into the image
    expose:
      - "3000"                # internal only — NOT "ports:", so it's unreachable from the host
    networks: [web]

  caddy:
    image: caddy:2-alpine
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./Caddyfile:/etc/caddy/Caddyfile:ro
      - caddy_data:/data       # persists certs across restarts — important to avoid ACME rate limits
      - caddy_config:/config
    depends_on: [app]
    networks: [web]

networks:
  web:

volumes:
  caddy_data:
  caddy_config:
```

Key production touches to check for / include:
- `restart: unless-stopped` on long-running services.
- App uses `expose:` not `ports:` — only Caddy publishes to the host.
- `caddy_data` volume persists issued certificates (re-issuing on every deploy can hit Let's Encrypt rate
  limits).
- Consider `deploy.resources.limits` (memory/CPU) so one container can't starve the host.

## When NOT to use global Caddy

- **Kubernetes** — use an Ingress controller (or per-pod sidecar) instead; a single Caddy container doesn't
  fit the pod model.
- **Multi-host / autoscaling** — each host or service may need its own proxy, or a managed load balancer
  fronts everything.
- **Managed PaaS** (Vercel, Fly, Railway, Cloud Run) — the platform already terminates TLS and routes; a
  Caddy container is redundant.

In these cases, note it in the report rather than generating a global Caddy setup.
