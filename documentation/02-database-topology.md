# Database Topology

Quickrep uses three separate database connections.

---

## Environment Variables

```env
QUICKREP_SOURCE_DB_CONNECTION=appstats
QUICKREP_CACHE_DB_CONNECTION=manticore
QUICKREP_CONFIG_DB_CONNECTION=quickrep_config
```

SOURCE — PostgreSQL (appstats)

Responsible for:
	•	Materialized views
	•	Aggregated analytics tables
	•	Read-heavy queries

Rules:
	•	Never run heavy joins on production transactional DB
	•	Prefer materialized views
	•	Index filterable columns

CACHE — ManticoreSearch

Responsible for:
	•	Temporary report tables
	•	Fast filtering
	•	Pagination
	•	DataTables compatibility

Each report generates a unique cache table.

CONFIG — PostgreSQL (appapi)

Responsible for:
	•	quickrep_meta
	•	quickrep_socket
	•	quickrep_wrench

Stores:
	•	Cache lifecycle
	•	Expiration timestamps
	•	Report metadata

This DB must use:

```php
PDO::ATTR_EMULATE_PREPARES => true
```

to avoid PgBouncer prepared statement issues.
