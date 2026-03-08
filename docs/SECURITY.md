# Security

## Auth Models

### External API (HMAC)
Required headers:
- `X-API-KEY`
- `X-API-TIMESTAMP`
- `X-API-NONCE`
- `X-API-SIGNATURE`

Signature base string:
`METHOD + "\n" + PATH + "\n" + TIMESTAMP + "\n" + NONCE + "\n" + SHA256(BODY_RAW)`

### Admin API
Required header:
- `X-ADMIN-ACCESS-KEY`

Validation endpoint:
- `POST /api/admin/auth.php`

### Webhook
Required header:
- `X-WEBHOOK-SECRET`

## Operational Controls
- Timestamp TTL checks
- Nonce replay protection
- Rate limit on sensitive endpoints
- No-cache headers on protected responses

## Recommendations
- Rotate all keys regularly
- Keep `.env` out of source control
- Restrict admin/network access where possible
- Log and monitor authentication failures
