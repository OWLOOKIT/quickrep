# Deployment Guide

---

## PostgreSQL (appstats)

- Prefer read replica
- Use materialized views
- Avoid transactional joins

---

## Manticore

- Run on separate container/node
- Monitor memory usage
- Enable persistence if required

---

## PgBouncer Compatibility

CONFIG DB must enable:

```php
PDO::ATTR_EMULATE_PREPARES => true
```

Do not use server-side prepared statements.