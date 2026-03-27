# AppSec Engineering Portfolio — Andrew Hardiman
Application security / product security portfolio built from small, reproducible case studies and secure-by-design engineering examples.

Primary background: backend software engineering (Python, PHP, AWS). Current focus: application security, product security, and secure SDLC practice.

## How to use this repo
- `main` contains the **remediated** implementation and regression tests.
- Vulnerable baselines are preserved on `vuln/<case>` branches for safe, local reproduction.

**OWASP mapping:** Numbering follows *OWASP Top 10 (2025)*.

## Case studies

### Web application vulnerabilities
- **A01 Broken Access Control** — IDOR in a REST API with authorization remediation and regression tests  
  Path: [`php/A01-broken-access-control/idor-rest-api-slim/`](https://github.com/Andrew-Hardiman/appsec-lab/tree/main/php/A01-broken-access-control/idor-rest-api-slim)
- **A02 Security Misconfiguration** — Exposed diagnostics endpoints with environment-based route gating and regression tests  
  Path: [`php/A02-security-misconfiguration/exposed-diagnostics-slim/`](https://github.com/Andrew-Hardiman/appsec-lab/tree/main/php/A02-security-misconfiguration/exposed-diagnostics-slim)

### Software supply chain / dependency risk
- **A03 Software Supply Chain Failures** — Known-vulnerable dependency in a CSV upload service with dependency remediation and secure version management  
  Path: [`python/A03-software-supply-chain-failures/insecure-csv-import-flask/`](https://github.com/Andrew-Hardiman/appsec-lab/tree/main/python/A03-software-supply-chain-failures/insecure-csv-import-flask)

## Secure SDLC and cloud security
- IAM least privilege review + example policies
- Logging/alerting checklist (CloudWatch + app logs)
- CI security gates (secret scanning + dependency scanning + SAST)

## Standards
- Minimal demos, no proprietary code
- Each case study includes: threat → repro → impact → fix → prevention
- Code is intentionally small and readable