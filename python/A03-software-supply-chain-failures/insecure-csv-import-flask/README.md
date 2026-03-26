# A03 Software Supply Chain Failures — Known-vulnerable dependency in a CSV upload service (Flask, Python)

> **Vulnerable baseline:** `vuln/python-a03-supply-chain`
>
> This branch intentionally demonstrates a software supply-chain failure: the application pins a known-vulnerable dependency version in a security-relevant file upload path.

## Threat model

- **Asset:** availability and resilience of the file upload/import service
- **Entry point:** `POST /import-users` using `multipart/form-data`
- **Trust boundary:** untrusted external uploader vs. server-side multipart parsing and import processing
- **Attacker capability:** any caller able to submit crafted upload requests
- **Security objective:** dependencies in the upload parsing path must not remain on known-vulnerable versions

## Vulnerable behavior

The application accepts CSV uploads through a Flask endpoint that depends on Werkzeug for multipart form parsing. This vulnerable baseline pins `Werkzeug==3.0.5` in `requirements.txt`.

Werkzeug `<= 3.0.5` is affected by **CVE-2024-49767**, a documented resource-exhaustion / denial-of-service issue in multipart form parsing. In other words, the security failure in this branch is not custom parser logic; it is insecure dependency version management in a request-processing path that handles untrusted file uploads.

This branch demonstrates the dependency-risk baseline. It does **not** claim that a full destructive denial-of-service exploit was executed against production-like infrastructure; the key point is that the application is built and run with a dependency version that authoritative advisories identify as vulnerable in the multipart upload path.

## Reproduction

Assuming the app is running locally (adjust host/port if needed):

Run the vulnerable baseline:

```bash
git checkout vuln/python-a03-supply-chain
cd python/A03-software-supply-chain-failures/insecure-csv-import-flask
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
python3 -m public.app
```

In another terminal, verify the pinned dependency versions:

```bash
cd ~/appsec-lab/python/A03-software-supply-chain-failures/insecure-csv-import-flask
source .venv/bin/activate
pip show Flask Werkzeug
```

Expected version evidence:

```text
Flask 3.0.3
Werkzeug 3.0.5
```

Then exercise the upload endpoint with a normal CSV file:

```bash
curl -i -F "file=@/path/to/sample_users.csv" http://localhost:5000/import-users
```

Expected result:

```text
200 OK
JSON response confirming the import completed
Response headers show Werkzeug/3.0.5
```

## Impact

- **Availability risk:** the application pins a dependency version with a documented denial-of-service / resource-exhaustion issue in the multipart request parsing path.
- **Attack surface relevance:** the vulnerable component sits directly on the unauthenticated file upload path (`POST /import-users`), so the risk exists before custom CSV validation logic is reached.
- **Operational risk:** even if the business logic is correct, insecure dependency version management in request-processing code can still expose the service to disruption.
- **Security engineering lesson:** secure application behavior depends not only on custom code correctness, but also on maintaining patched versions of framework and library components in security-relevant paths.

## Fix

On `main`, the vulnerable dependency baseline is remediated by upgrading to a patched Werkzeug version and aligning the Flask/Werkzeug versions to a safe, supported combination.

The remediation goal is not to change the CSV import feature itself. The feature remains the same; the fix is to remove the known-vulnerable dependency state from the upload handling path.

✅ See the patched version on `main`:

- Upgrade from `Werkzeug==3.0.5` to a patched version (`3.0.6+`)
- Align Flask to a compatible safe version
- Preserve the existing upload validation behavior while removing the known-vulnerable dependency baseline

## Prevention (patterns + SDLC controls)

- Track framework and library versions in security-relevant request paths, especially file upload, request parsing, authentication, and deserialization flows.
- Upgrade promptly when authoritative advisories disclose security issues in dependencies used by the application.
- Pin approved dependency versions explicitly so builds are reproducible and reviewers can verify exactly what was deployed.
- Add dependency scanning in CI to detect known-vulnerable packages before merge or release.
- Include dependency review in code review and release checklists, not just custom application logic review.
- Treat secure dependency management as part of the application security boundary: correct business logic does not compensate for vulnerable framework or library components underneath it.

## Lab note

For this case study, the vulnerable baseline is intentionally pinned to a known-vulnerable Werkzeug version in the file upload request path. The goal is to demonstrate a realistic software supply-chain failure in dependency version management, not to claim a full production-scale denial-of-service exercise. The key lesson is that correct custom application logic does not remove risk introduced by vulnerable framework or library components underneath the feature.