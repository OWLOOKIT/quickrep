# Failure Isolation Strategy

Quickrep ensures failures do not impact main API.

---

## If SOURCE (appstats) is down

- API returns HTTP 503
- Main API remains operational

---

## If CACHE (manticore) is down

- API returns HTTP 503
- No load redirected to PostgreSQL

---

## If CONFIG DB unstable

- Metadata failure logged
- Report execution continues

---

## API Response Format

```json
{
  "ok": false,
  "error": "Reports backend unavailable"
}