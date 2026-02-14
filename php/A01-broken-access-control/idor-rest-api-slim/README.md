# A01 Broken Access Control — IDOR in a REST API (Slim, PHP)

## Threat model
## Vulnerable behavior
## Reproduction (HTTP requests)

## Vulnerable snapshot
The intentionally vulnerable state is preserved at tag `php-a01-idor-vulnerable`.

To view the vulnerable code on GitHub (in this folder), open:
https://github.com/Andrew-Hardiman/appsec-lab/tree/php-a01-idor-vulnerable/php/A01-broken-access-control/idor-rest-api-slim

## Impact
## Fix (authz model)
## Regression tests
## Prevention (patterns + SDLC controls)

## Lab note: why we use `X-User-Id`
For this case study, authentication is treated as out-of-scope. We simulate an authenticated user via an `X-User-Id` header so the demo stays focused on authorization (access control) and is easy to reproduce with curl. In a real system, the user identity would come from a verified session or token (e.g. server-side session cookie or validated JWT claims), not from a client-supplied header.
