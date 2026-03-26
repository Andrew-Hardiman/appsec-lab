# A03 Software Supply Chain Failures — Known-vulnerable dependency in a CSV upload service (Flask, Python)

## Threat model

- **Asset:** availability and stability of the CSV import service, plus confidence that security-relevant request parsing components are maintained at safe versions
- **Entry point:** `POST /import-users` with a user-supplied CSV file uploaded as `multipart/form-data`
- **Trust boundary:** attacker-controlled input crosses from the HTTP client into the Flask/Werkzeug request parsing and file upload handling path before application-level validation runs
- **Attacker capability:** any caller able to send crafted HTTP requests to the upload endpoint
- **Security objective:** externally reachable request-processing components must not be deployed with known-vulnerable dependency versions; security-relevant packages should be kept within supported, remediated ranges

## Vulnerable behavior

The vulnerable baseline (see `vuln/python-a03-supply-chain`) exposes a CSV upload endpoint at `POST /import-users` and pins a known-vulnerable dependency set in that externally reachable upload path:

- `Flask==3.0.3`
- `Werkzeug==3.0.5`

This case study focuses on software supply-chain failure in the HTTP request parsing / file upload path, not on a flaw in the custom CSV business logic itself. The application accepts attacker-controlled `multipart/form-data`, and that request handling depends on Flask/Werkzeug before the application’s own validation logic executes.

`Werkzeug<=3.0.5` is affected by `CVE-2024-49767`, a documented resource-exhaustion / denial-of-service issue in multipart form parsing. In other words, the vulnerable baseline ships a known-vulnerable dependency in a request-processing component that handles attacker-controlled input.

On `main`, the feature and endpoint remain, but the dependency posture is remediated: the vulnerable Werkzeug version is removed by upgrading to a safe dependency state, while preserving the same small CSV import workflow and adding regression checks around intended behavior.

## Vulnerable snapshot

The intentionally vulnerable baseline is preserved on branch `vuln/python-a03-supply-chain`.

- **Vulnerable baseline (GitHub):** `https://github.com/Andrew-Hardiman/appsec-lab/tree/vuln/python-a03-supply-chain/python/A03-software-supply-chain-failures/insecure-csv-import-flask/`

## Reproduction (dependency state + local run)

Reproduce the vulnerable dependency state from the preserved baseline:

```bash
git checkout vuln/python-a03-supply-chain
cd python/A03-software-supply-chain-failures/insecure-csv-import-flask
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
python3 -m public.app
```

In another terminal, confirm the vulnerable dependency versions:

```bash
source .venv/bin/activate
pip show Flask Werkzeug
```

Expected result on the vulnerable baseline:

```text
Flask 3.0.3
Werkzeug 3.0.5
```

Then exercise the upload endpoint with a normal CSV file:

```bash
curl -i \
  -F 'file=@sample.csv;type=text/csv' \
  -F 'comments=baseline test upload' \
  http://127.0.0.1:5000/import-users
```

Example `sample.csv`:

```csv
email,name,role
alice@example.com,Alice Admin,admin
bob@example.com,Bob User,user
```

Expected result on the vulnerable baseline:

- HTTP `200`
- JSON response showing the import succeeded
- response headers showing the app is serving via Werkzeug `3.0.5` (supporting evidence alongside `pip show`)

This case study demonstrates that the service was running with a known-vulnerable dependency in a security-relevant upload path. It does not claim that a full destructive denial-of-service exploit was reproduced locally; the evidence here is the vulnerable dependency state and the fact that the exposed upload workflow depends on that request parsing path.

## Impact

- **Availability risk:** `CVE-2024-49767` affects Werkzeug multipart parsing prior to `3.0.6` and can allow a crafted upload request to trigger significant memory consumption in the request parsing path before normal application-level validation takes over.
- **Attacker-reachable attack surface:** because the vulnerable component sits behind an externally reachable file upload endpoint, the risk is not theoretical library hygiene alone; it is exposure in a request path that processes attacker-controlled input.
- **Service instability / denial of service:** in a real deployment, resource exhaustion in request parsing can degrade or terminate the import service and may also affect other workloads sharing the same host or container resources.
- **Supply-chain governance failure:** the root issue is not a bug in the custom CSV importer but the operational/security failure of shipping a known-vulnerable dependency version in a security-relevant request path.

## Fix

On `main`, the vulnerable dependency state is remediated by upgrading Werkzeug out of the affected range while keeping the same small CSV import feature and HTTP contract.

On `main`, this means moving Werkzeug from the vulnerable baseline version (`3.0.5`) to a version outside the affected range for `CVE-2024-49767`.

The security improvement is intentionally focused on dependency posture rather than changing the business behavior of the endpoint. The application still accepts a CSV upload at `POST /import-users`, but it no longer pins the known-vulnerable Werkzeug version used in the baseline.

This is the important remediation point for the case study: when a security-relevant component in an attacker-reachable request path is identified as vulnerable, the fix is to move to a supported, remediated version and keep that version under ongoing dependency governance rather than treating the issue as “just a library detail.”

In this case study, the custom CSV validation logic is not the fix. The fix is removing the insecure dependency version from the deployed application state.

## Regression tests

Run tests on `main` (patched branch):

```bash
git checkout main
cd python/A03-software-supply-chain-failures/insecure-csv-import-flask
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
python3 -m pytest
```

The suite covers intended application behavior:

- valid CSV upload returns `200`
- missing file returns controlled JSON `400`
- invalid UTF-8 / non-text upload returns controlled JSON `400`
- incorrect CSV headers return controlled JSON `400`
- invalid row content returns controlled JSON `400`

For this case study, the regression goal is not to “test the CVE” directly. The goal is to show that after dependency remediation, the application still preserves its intended HTTP contract and validation behavior.

Remediation verification evidence:

In addition to the HTTP regression tests, remediation can be verified directly by confirming that the patched branch no longer installs the vulnerable Werkzeug version (`Werkzeug<=3.0.5`):

```bash
pip show Flask Werkzeug
```

You can also make a normal request to confirm the running app is serving with the remediated Werkzeug version in the response headers.

## Prevention (patterns + SDLC controls)

- **Track direct and transitive dependencies:** maintain visibility of the packages the application actually ships with, especially framework and request-processing components that sit on attacker-reachable paths.
- **Upgrade vulnerable packages promptly:** when advisories identify an affected version range, move to a supported remediated version rather than leaving a known-vulnerable dependency pinned in place.
- **Automate dependency review in CI:** run dependency scanning in pull requests and default-branch builds so known-vulnerable versions are detected before release.
- **Treat upload and parsing libraries as security-relevant components:** multipart parsing, file handling, deserialization, and request decoding paths should receive the same security attention as authentication and authorization code.
- **Require evidence for dependency changes:** code review should check both the version change itself and the reason for it, including advisory reference, affected path, and expected regression coverage.
- **Preserve functional regression coverage during upgrades:** after remediating a vulnerable package, run tests that prove the application still honors its intended HTTP contract and validation behavior.

## Lab note

This case study is intentionally small and focused. The goal is to show a realistic application security review pattern: identify a known-vulnerable dependency in an attacker-reachable request-processing path, preserve a reproducible vulnerable baseline, remediate the dependency state, and verify that the application still behaves correctly after the change.

The example keeps the feature simple on purpose so the security lesson stays clear: dependency version management is part of the application’s security posture. This case study focuses on an upload and request-parsing path, but the same principle applies more broadly across authentication, deserialization, templating, API handling, and other security-relevant components.