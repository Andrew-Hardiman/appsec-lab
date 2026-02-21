# A01 Broken Access Control — IDOR in a REST API (Slim, PHP)

## Threat model
## Vulnerable behavior

## Vulnerable snapshot
The intentionally vulnerable state is preserved at tag `php-a01-idor-vulnerable`.

To view the vulnerable code on GitHub (in this folder), open:
https://github.com/Andrew-Hardiman/appsec-lab/tree/php-a01-idor-vulnerable/php/A01-broken-access-control/idor-rest-api-slim

## Reproduction (HTTP requests)

### Reproduce the vulnerability (IDOR)

Run the vulnerable snapshot:

```bash
git checkout php-a01-idor-vulnerable
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

## Impact
## Fix (authz model)

## Regression tests

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

## Lab note: why we use `X-User-Id`
For this case study, authentication is treated as out-of-scope. We simulate an authenticated user via an `X-User-Id` header so the demo stays focused on authorization (access control) and is easy to reproduce with curl. In a real system, the user identity would come from a verified session or token (e.g. server-side session cookie or validated JWT claims), not from a client-supplied header.
