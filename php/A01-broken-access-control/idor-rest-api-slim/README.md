# A01 Broken Access Control — IDOR in a REST API (Slim, PHP)

## Threat model

- Asset: documents (may contain sensitive/PII-like content)
- Entry point: `GET /api/documents/{docId}`
- Trust boundary: user identity (simulated via `X-User-Id`) vs. server-side authorization decision
- Attacker capability: any authenticated user can guess/enumerate `docId` values
- Security objective: only the owner can access a private document; public documents are accessible to anyone authenticated

## Vulnerable behavior

The vulnerable baseline returns a document solely based on the caller-supplied `docId` and fails to enforce **object-level authorization** (IDOR). A non-owner can read another user’s private document by requesting its ID.

## Vulnerable snapshot
The intentionally vulnerable baseline is preserved on branch `vuln/php-a01-bac` (folder link below).

- Vulnerable baseline (GitHub): https://github.com/Andrew-Hardiman/appsec-lab/tree/vuln/php-a01-bac/php/A01-broken-access-control/idor-rest-api-slim

## Reproduction (HTTP requests)

### Reproduce the vulnerability (IDOR)

Run the vulnerable snapshot:

```bash
git checkout vuln/php-a01-bac
cd php/A01-broken-access-control/idor-rest-api-slim
composer install
php -S localhost:8085 -t public
```
In another terminal, reproduce the IDOR (user 1 reads user 2's document by changing the ID):

```bash
curl -i -H "X-User-Id: 1" localhost:8085/api/documents/1
curl -i -H "X-User-Id: 1" localhost:8085/api/documents/2
```

Expected results:

```json
{"document":"This is user 1 personal information"}
{"document":"This is user 2 personal information"}
```

These results are for the `vuln/php-a01-bac` baseline; on `main` the second request returns `403` `{"error":"forbidden"}`.

## Impact

- Confidentiality breach: private documents can be read by other users via ID enumeration
- Tenant/user boundary violation: a common real-world incident class in multi-user systems
- Compliance risk if documents contain personal or customer data

## Fix (authz model)

On `main`, the endpoint enforces **owner-or-public** authorization:

- Missing `X-User-Id` → `401`
- Document not found → `404`
- Document is public (`ownerUserId` is `null`) → `200`
- Document is private and caller is not the owner → `403` with `{"error":"forbidden"}`
- Document is private and caller is the owner → `200`

## Regression tests

Run tests on `main` (patched branch): `git checkout main`

Run the tests from the case study root:

```bash
cd php/A01-broken-access-control/idor-rest-api-slim
composer install
./vendor/bin/phpunit
```
The suite covers:

- `401` when `X-User-Id` is missing (unauthenticated)
- `403` when a non-owner requests a private document (forbidden)
- `404` when the document does not exist (not found)
- `200` for owner access, and `200` for public documents

## Prevention (patterns + SDLC controls)

- Centralize authorization in a policy/guard (deny-by-default) instead of scattering checks in handlers
- Treat all direct object references (`/resource/{id}`) as high-risk until proven authorized
- Add regression tests for access-control decisions (401/403/404/200 paths)
- Log access denials and consider rate-limiting to reduce enumeration
- Code review checklist item: “Is there an object-level authorization check tied to the authenticated principal?”

## Lab note: why we use `X-User-Id`
For this case study, authentication is treated as out-of-scope. We simulate an authenticated user via an `X-User-Id` header so the demo stays focused on authorization (access control) and is easy to reproduce with curl. In a real system, the user identity would come from a verified session or token (e.g. server-side session cookie or validated JWT claims), not from a client-supplied header.
