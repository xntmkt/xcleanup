# Environment

## Environment variables
Copy `.env.example` to `.env` and configure per environment:
- SMTP: `CLEANUP_SMTP_*`
- Telegram: `CLEANUP_TELEGRAM_*`
- Slack/Discord: `CLEANUP_*_WEBHOOK_URL`

## Recommendations
- Do not commit `.env` to git.
- Use a secret manager (Vault/SSM) in production.

## Environment separation
- **Development:** verbose logging, dry-run by default.
- **Staging:** production-like configuration, synthetic dataset.
- **Production:** confirmation enabled, least privilege, JSON logs.
