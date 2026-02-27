# Quickrep Engine Architecture (v1.0.1)

## Overview

Quickrep is a report engine designed for high-load LMS analytics with strict isolation from the main application database.

The engine is built around a **3-layer database topology**:

| Layer   | Purpose                                | Database |
|----------|----------------------------------------|-----------|
| SOURCE   | Analytical data & materialized views  | PostgreSQL (`appstats`) |
| CACHE    | Report cache tables for filtering     | ManticoreSearch |
| CONFIG   | Metadata & report lifecycle tracking  | PostgreSQL (`appapi`) |

---

## Design Goals

- Full isolation of analytics from core API
- Support for PostgreSQL materialized views
- Fast filtering and pagination via Manticore
- Builder-based SQL support
- Safe failure behavior (503 fallback)
- PgBouncer compatibility

---

## Execution Flow

1. API request hits Quickrep controller.
2. Report object builds SQL via `GetSQL()`.
3. SQL executes against SOURCE (`appstats`).
4. Result is materialized into CACHE (Manticore).
5. Pagination/filtering happens inside CACHE.
6. Metadata stored in CONFIG DB.
7. Response returned to client.

---

## Isolation Principle

Even if:

- SOURCE DB is down
- CACHE is down
- Heavy analytical queries overload stats DB

The main application database (`appapi`) remains unaffected.