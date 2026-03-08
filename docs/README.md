# KaiMail Docs

This folder contains the maintained system documentation.

## Files
- `ARCHITECTURE.md`: runtime components and data flow
- `SETUP.md`: environment and deployment setup
- `SECURITY.md`: auth model and security practices

## Scope
These docs are aligned with the current codebase state:
- `emails` schema has no expiry columns
- Admin bulk creation supports `1..50`
- External `POST /api/emails.php` supports `1..10`
