# A02 Security Misconfiguration — Exposed diagnostics endpoints (Slim, PHP)

## Threat model

- Asset: runtime configuration + internal application surface area (routes/endpoints)
- Entry points: `GET /debug/phpinfo`, `GET /debug/routes`
- Trust boundary: external caller vs. diagnostic/admin features intended for developers/operators
- Attacker capability: any unauthenticated internet user (or any authenticated user, depending on deployment)
- Security objective: diagnostics must not be exposed in non-development environments

## Vulnerable behavior
The vulnerable baseline (see `vuln/php-a02-misconfig`) exposes diagnostic endpoints (`/debug/phpinfo` and `/debug/routes`) without any access control or environment gating. These endpoints disclose runtime configuration and internal application surface area (route inventory) to any caller. In a real deployment, this information materially helps attackers enumerate technologies, misconfigurations, and high-value targets. On `main`, these endpoints are not registered by default and return `404 {"error":"Not found"}` unless explicitly enabled for local development.

## Vulnerable snapshot
The intentionally vulnerable baseline is preserved on branch `vuln/php-a02-misconfig` (folder link below).

- Vulnerable baseline (GitHub): https://github.com/Andrew-Hardiman/appsec-lab/tree/vuln/php-a02-misconfig/php/A02-security-misconfiguration/exposed-diagnostics-slim

## Reproduction (HTTP requests)
### Reproduce the vulnerability (exposed diagnostics)

Run the vulnerable snapshot:

```bash
git checkout vuln/php-a02-misconfig
cd php/A02-security-misconfiguration/exposed-diagnostics-slim
composer install
php -S localhost:8086 -t public
```

In another terminal, reproduce the misconfiguration (diagnostics exposed):

```bash
curl -i http://localhost:8086/debug/phpinfo | head -n 12
curl -i http://localhost:8086/debug/routes
```

Expected results:

```text
/debug/phpinfo returns 200 with Content-Type: text/html; charset=UTF-8 (phpinfo HTML)
/debug/routes returns 200 with Content-Type: application/json and a JSON route list (patterns + methods)
```

These results are for the `vuln/php-a02-misconfig` baseline; on main both endpoints return 404 {"error":"Not found"} by default.

## Impact

- Information disclosure: reveals PHP version, extensions, INI paths, and other environment details (useful for exploit selection and chaining).
- Attack surface expansion: `/debug/routes` enumerates internal endpoints that may not be otherwise discoverable, accelerating recon.
- Operational risk: exposes deployment details that can aid targeted attacks and weaken defense-in-depth assumptions.

## Fix

On `main`, diagnostics are gated behind an explicit allow condition:

- Default → `404 {"error":"Not found"}` for `/debug/*`
- If `APP_ENV=dev` → diagnostics routes are registered and return `200` (e.g. `APP_ENV=dev php -S localhost:8086 -t public`)

## Regression tests

Run tests on `main` (patched branch): `git checkout main`

Run the tests from the case study root:

```bash
cd php/A02-security-misconfiguration/exposed-diagnostics-slim
composer install
./vendor/bin/phpunit
```
The suite covers:

- 404 for /debug/phpinfo when APP_ENV is not dev
- 404 for /debug/routes when APP_ENV is not dev
- 200 for /debug/phpinfo when APP_ENV=dev
- 200 for /debug/routes when APP_ENV=dev

## Prevention (patterns + SDLC controls)

- Disable or remove diagnostic endpoints in production builds (deny-by-default).
- Gate debug features by environment + network controls (e.g., localhost/VPN/allowlist), not by obscurity.
- Add automated checks: config linting and tests that assert debug routes are unavailable in non-dev environments.
- Treat diagnostic data as sensitive: log access attempts and alert on unexpected requests to `/debug/*`.
- Code review checklist item: “Are any debug/admin endpoints exposed beyond intended environments?”

## Lab note

For this case study, diagnostic endpoints are intentionally added to demonstrate how a common “dev-only” feature becomes a production risk when misconfigured. In real systems, diagnostics should be removed or strongly gated (environment + network controls) and never exposed to untrusted clients.