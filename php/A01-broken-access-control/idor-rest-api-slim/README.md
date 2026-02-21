# A01 Broken Access Control — IDOR in a REST API (Slim, PHP)

> **Vulnerable snapshot:** `php-a01-idor-vulnerable`  

> This tag intentionally demonstrates an **Insecure Direct Object Reference (IDOR)**: access control is missing/insufficient, allowing one user to read another user’s private document by guessing/iterating `docId`.

## Threat model

- The API exposes documents by identifier: `GET /api/documents/{docId}`
- The caller is treated as “authenticated” for the purpose of the demo (see `X-User-Id` below)
- An attacker has a valid user context (any `X-User-Id`) and can enumerate or guess document IDs

## Vulnerable behavior

The endpoint returns document contents based only on `docId`, without correctly enforcing **authorization**.

**Expected:** A user should only be able to access:
- documents they own, or
- documents explicitly marked public

**Vulnerable:** A non-owner can access a private document by requesting its `docId` (classic IDOR).

## Reproduction (HTTP requests)

Assuming the API is running locally (adjust host/port if needed):

### 1) Read your own document (baseline)
```bash
curl -i -H "X-User-Id: 1" http://localhost:8080/api/documents/1
```

### 2) IDOR: read another user’s private document (the bug)

Request a document owned by someone else while authenticated as a different user:

```bash
curl -i -H "X-User-Id: 1" http://localhost:8080/api/documents/2
```

Vulnerable result: you receive 200 OK with the other user’s document data, even though you are not the owner.

If your seeded IDs differ, use any `docId` that is owned by another user and is not public.

## Impact
- Confidentiality breach: private documents can be exfiltrated by ID enumeration
- Escalation path: enables targeted data theft, account/tenant boundary violations, and privacy incidents
- In real systems, this is often a reportable incident (PII / customer data exposure)

## Fix (authz model)
The fixed implementation enforces owner-or-public authorization:

- Missing authentication → 401
- Non-owner requesting a private document → 403 with `{"error":"forbidden"}`
- Missing document → 404
- Public document (no owner) → 200
- Owner requesting their own document → 200

✅ See the patched version and regression tests on `main`:

- Browse: `main` branch in this repo
- The “Regression tests” section on `main` documents PHPUnit coverage and how to run it

## Prevention (patterns + SDLC controls)
- Enforce authorization at the resource level (owner check / policy layer), not just routing
- Use deny-by-default for protected resources
- Add regression tests for:
    - 401 unauthenticated
    - 403 non-owner
    - 404 missing
    - 200 owner
    - 200 public
- Log and alert on suspicious access patterns (enumeration, high 403/404 rates)
- Threat model “direct object references” whenever identifiers are user-controllable

## Lab note: why we use `X-User-Id`
Authentication is treated as out-of-scope for this case study. We simulate an authenticated user via an `X-User-Id` header so the demo stays focused on authorization (access control) and is easy to reproduce with `curl`.

In a real system, the user identity would come from a verified session or token (e.g., server-side session cookie or validated JWT claims), not from a client-supplied header.
