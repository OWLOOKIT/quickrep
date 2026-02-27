# Cache Layer (ManticoreSearch)

Quickrep caches report data into Manticore.

---

## Cache Lifecycle

1. DROP TABLE IF EXISTS
2. CREATE TABLE
3. INSERT rows from SOURCE
4. Store metadata in CONFIG DB

---

## Why Manticore?

- Fast filtering
- Full-text search support
- Low memory overhead
- Independent from PostgreSQL

---

## Performance Notes

- Keep cache tables narrow
- Avoid storing unnecessary columns
- Set appropriate cache expiration