# Using PostgreSQL Materialized Views

Quickrep is optimized for materialized views.

---

## Example

```sql
CREATE MATERIALIZED VIEW mv_school_activity AS
SELECT
    school_id,
    COUNT(DISTINCT user_id) AS active_users,
    COUNT(*) AS total_visits
FROM visits
GROUP BY school_id;
```

Refresh Strategy
```sql
REFRESH MATERIALIZED VIEW CONCURRENTLY mv_school_activity;
```
Recommended:
•	Refresh asynchronously (cron / queue)
•	Never refresh during report execution
•	Always index filter columns

Why Materialized Views?
•	Offload heavy aggregations
•	Precompute expensive joins
•	Stable performance
•	Reduced load on stats DB
