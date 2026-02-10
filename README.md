# AppSec Engineering Portfolio — Andrew

Backend engineer (PHP/AWS) with a focus on application security and secure-by-design engineering.

This repo contains small, reproducible security engineering case studies:
- how vulnerabilities happen in real systems
- how to reproduce them safely
- how to fix them correctly
- how to prevent recurrence (tests, patterns, SDLC controls)

## Case studies (code + writeups)
1) [ ] IDOR in a REST API (authz model + tests + fix)
2) [ ] Broken access control via missing policy checks
3) [ ] SQL injection in legacy data access
4) [ ] SSRF in an integration feature (allowlists + egress controls)
5) [ ] Stored XSS in an admin workflow (encoding + CSP notes)

## Secure SDLC & AWS notes (practical)
1) [ ] IAM least privilege review + example policies
2) [ ] Logging/alerting checklist (CloudWatch + app logs)
3) [ ] CI security gates (secret scanning + dependency scanning + SAST)

## Standards
- Minimal demos, no proprietary code
- Each case study includes: threat → repro → impact → fix → prevention
- Code is intentionally small and readable
