# Troubleshooting

---

## prepared statement does not exist

Cause:
PgBouncer + server-side prepares

Fix:
Enable `PDO::ATTR_EMULATE_PREPARES`.

---

## syntax error near "`"

Cause:
MySQL-style backticks executed on PostgreSQL.

Fix:
Ensure cache connection is Manticore.

---

## Report hangs

Cause:
Materialized view refresh during read.

Fix:
Use `REFRESH MATERIALIZED VIEW CONCURRENTLY`.

---

## Cache table not dropping

Cause:
Schema builder used for Manticore.

Fix:
Use raw `DROP TABLE IF EXISTS` for non-pgsql drivers.