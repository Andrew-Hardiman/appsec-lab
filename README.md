# AppSec Engineering Portfolio — Andrew

Backend engineer (PHP/PYTHON/AWS) with a focus on application security and secure-by-design engineering.

This is a proof-of-work hub: small, reproducible case studies that show
**threat → repro → impact → fix → prevention** (tests + SDLC controls).

## How to use this repo
- `main` contains the **remediated** implementation and regression tests.
- Vulnerable snapshots are preserved via **tags/commits** for safe, local reproduction.

## OWASP Top 10 case studies

### PHP
- **A01 Broken Access Control**
  - `php/A01-broken-access-control/idor-rest-api-slim/` — IDOR in a REST API (authz model + tests)

### Python
- (planned)

## Secure SDLC & AWS notes (practical)
1) [ ] IAM least privilege review + example policies
2) [ ] Logging/alerting checklist (CloudWatch + app logs)
3) [ ] CI security gates (secret scanning + dependency scanning + SAST)

## Standards
- Minimal demos, no proprietary code
- Each case study includes: threat → repro → impact → fix → prevention
- Code is intentionally small and readable
