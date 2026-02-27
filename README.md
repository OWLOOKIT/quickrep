# Quickrep Reporting Engine

[![Version](https://img.shields.io/badge/version-1.0.3-blue.svg)]()
[![License](https://img.shields.io/badge/license-MIT-green.svg)]()
[![PHP](https://img.shields.io/badge/php-%3E%3D8.0-777bb4.svg)]()
[![Laravel](https://img.shields.io/badge/laravel-%3E%3D10.x-ff2d20.svg)]()

A PHP reporting engine for Laravel that turns SQL (and Query Builders) into rich, interactive, web-based reports.

---

## What Quickrep is

Quickrep lets report authors focus primarily on **data selection** (SQL `SELECT`) while the engine handles:

- interactive tabular reports (paging/filter/sort)
- cards / tree / graph viewers (depending on installed viewers)
- CSV/XLSX exports
- report JSON API

Historically Quickrep assumed a single SQL database (MySQL/MariaDB).  
Starting with **v1.0.1**, Quickrep supports a **3-layer database topology** that enables scalable analytics in production systems.

---

## Core Reporting Features

- Write SQL → get interactive report UI
- Server-side paging for large datasets
- Download results (CSV/XLSX)
- JSON API endpoints
- Per-row decorations (links/buttons/html)
- One report = one PHP class (SQL + formatting + behavior)
- Request inputs available inside reports (`getInput()`, URL segments, etc.)
- **Builder support**: `GetSQL()` can return Query Builder / Eloquent Builder

---

## Architecture (v1.0.1)

Quickrep Engine v1.0.1 uses **three connections**:

| Layer   | Purpose                                   | Database |
|--------|--------------------------------------------|----------|
| SOURCE | Where report SQL runs (analytics data)     | PostgreSQL (`appstats`) |
| CACHE  | Cached report tables (paging/filtering)    | ManticoreSearch |
| CONFIG | Engine metadata (sockets/meta/wrenches)    | PostgreSQL (`appapi`) |

### Why this topology?

- SOURCE contains **materialized views** and aggregated analytics (fast & predictable).
- CACHE isolates expensive browsing operations (filtering/paging) from PostgreSQL.
- CONFIG stores report lifecycle metadata and socket/wrench configuration without coupling to analytics.

> Key goal: even if analytics systems are overloaded or down, the **main application remains operational**.

---

## Diagram

```mermaid
graph LR
    subgraph SOURCE["SOURCE: PostgreSQL (appstats)"]
        MV["Materialized Views / Aggregates"] --> Q["Report SQL"]
    end

    subgraph CACHE["CACHE: ManticoreSearch"]
        T["Cached Report Tables"]
    end

    subgraph CONFIG["CONFIG: PostgreSQL (appapi)"]
        META["quickrep_meta / sockets / wrenches"]
    end

    subgraph ENGINE["Quickrep Engine"]
        R["Report Files (PHP)"] --> Q
        Q --> T
        META --> ENGINE
    end

    subgraph API["API / Viewers"]
        T --> TAB["Tabular (DataTables)"]
        T --> CARD["Cards / Tree / Graph viewers"]
    end

    style SOURCE fill:#f9f,stroke:#333,stroke-width:2px
    style CACHE fill:#bbf,stroke:#333,stroke-width:2px
    style CONFIG fill:#ffd,stroke:#333,stroke-width:2px
    style ENGINE fill:#eef,stroke:#333,stroke-width:2px
    style API fill:#bfb,stroke:#333,stroke-width:2px
```

Documentation

Current docs (v1.0.1)
•	Architecture: documentation/01-architecture.md
•	DB topology: documentation/02-database-topology.md
•	Creating reports: documentation/03-creating-reports.md
•	Materialized views: documentation/04-materialized-views.md
•	Caching (Manticore): documentation/05-caching-and-manticore.md
•	Failure isolation: documentation/06-failure-isolation.md
•	Deployment & config: documentation/07-deployment-and-config.md
•	Troubleshooting: documentation/08-troubleshooting.md

Legacy docs (pre-1.0.1)

If you see references in older blog posts or forks, these legacy doc names map to the new ones:
•	documentation/Architecture.md → documentation/01-architecture.md
•	documentation/ControlCaching.md → documentation/05-caching-and-manticore.md
•	documentation/ConfigFile.md → documentation/07-deployment-and-config.md
•	documentation/Troubleshooting.md → documentation/08-troubleshooting.md

⸻

Quick Start (Production TopIQ-style)

This is the recommended production setup for high-load LMS analytics:
•	SOURCE: PostgreSQL appstats (analytics + materialized views)
•	CACHE: ManticoreSearch manticore (report cache tables)
•	CONFIG: PostgreSQL appapi via quickrep_config connection (meta/sockets/wrenches)

1) Install
```bash
composer require owlookit/quickrep
php artisan quickrep:install
```

2) Configure DB connections

In your Laravel app (config/database.php) define:
•	appstats (pgsql) — analytics DB
•	manticore (mysql) — manticore mysql-protocol endpoint
•	quickrep_config (pgsql) — points to your main app DB (appapi), with emulate prepares

Example (quickrep_config only):
```php
'quickrep_config' => [
    'driver' => 'pgsql',
    'host' => env('APPAPI_DB_HOST', env('DB_HOST')),
    'port' => env('APPAPI_DB_PORT', env('DB_PORT', 5432)),
    'database' => env('APPAPI_DB_DATABASE', env('DB_DATABASE')),
    'username' => env('APPAPI_DB_USERNAME', env('DB_USERNAME')),
    'password' => env('APPAPI_DB_PASSWORD', env('DB_PASSWORD')),
    'charset' => 'utf8',
    'prefix' => '',
    'prefix_indexes' => true,
    'schema' => 'public',
    'options' => extension_loaded('pdo_pgsql') ? [
        PDO::ATTR_EMULATE_PREPARES => true,
    ] : [],
],
```

3) Set env vars
```env
QUICKREP_SOURCE_DB_CONNECTION=appstats
QUICKREP_CACHE_DB_CONNECTION=manticore
QUICKREP_CONFIG_DB_CONNECTION=quickrep_config 
```


4) Clear config cache 
```bash
php artisan config:clear
php artisan cache:clear 
```

5) Run migrations for meta/sockets
```bash
php artisan migrate
```

6) Create your first report
```bash
php artisan quickrep:make_tabular YourNewReportName
```


Open:
•	/Quickrep/YourNewReportName
•	or API endpoint: /api/Quickrep/YourNewReportName?...

⸻

Prerequisites
•	PHP >= 8.0 recommended
•	Laravel >= 9.x recommended
•	PostgreSQL for SOURCE (analytics)
•	PostgreSQL for CONFIG (main app DB)
•	ManticoreSearch for CACHE


⸻

Installation (General)

```bash
composer require owlookit/quickrep
php artisan quickrep:install
```

Note: In v1.0.1 topology, quickrep:install is mainly scaffolding. You should configure the three connections explicitly.


Routes

You should see routes similar to:
```bash
php artisan route:list | grep Quickrep
```

Example patterns:
•	Quickrep/{report_key}
•	QuickrepCard/{report_key}
•	QuickrepTree/{report_key}
•	QuickrepGraph/{report_key}
•	api/Quickrep/{report_key}
•	api/Quickrep/{report_key}/Summary
•	api/Quickrep/{report_key}/Download

⸻

Configuration (v1.0.1)

Edit config/quickrep.php.

Required connections
```env
QUICKREP_SOURCE_DB_CONNECTION=appstats
QUICKREP_CACHE_DB_CONNECTION=manticore
QUICKREP_CONFIG_DB_CONNECTION=quickrep_config
```

	•	QUICKREP_SOURCE_DB_CONNECTION — where report SQL runs (PostgreSQL analytics)
	•	QUICKREP_CACHE_DB_CONNECTION — where cache tables live (Manticore)
	•	QUICKREP_CONFIG_DB_CONNECTION — where meta/sockets/wrenches are stored (PostgreSQL main app DB)

PgBouncer compatibility

If you use PgBouncer (transaction pooling) or any environment that resets prepared statements, CONFIG DB should enable:
```php
PDO::ATTR_EMULATE_PREPARES => true
```

Make Your First Report

Create a report class:
```bash
php artisan quickrep:make_tabular YourNewReportName
```

Open:
•	/Quickrep/YourNewReportName
•	or API endpoint: /api/Quickrep/YourNewReportName?...

⸻

Writing Reports

GetSQL()

GetSQL() is the core of a report. It may return:
•	string
•	array<string>
•	QueryBuilder
•	EloquentBuilder
•	array of the above

Builder example:
```php
public function GetSQL()
{
    return DB::connection(quickrep_source_db())
        ->table('mv_school_activity')
        ->select(['school_id', 'active_users', 'total_visits']);
}
```

Inputs

Use request inputs:
•	getInput($key) — GET/POST/JSON input
•	URL segments:
•	$this->getCode()
•	$this->getParameters()

⸻

Caching Model (v1.0.1)

In v1.0.1, caching is not just an optimization — it is a core part of performance isolation.
•	SQL executes in SOURCE (PostgreSQL)
•	Results are stored in CACHE (Manticore)
•	UI queries (filter/paging/sort) run against CACHE

This prevents the browser/UI from generating heavy workload on PostgreSQL.

⸻

Failure Isolation

If SOURCE (appstats) or CACHE (manticore) is unavailable, the API endpoints respond with HTTP 503:
```json
{
  "ok": false,
  "error": "Reports backend unavailable"
}
```

This ensures analytics failures do not crash the main application.

⸻

Materialized Views (Recommended)

For predictable performance, build reports on top of materialized views:
•	precompute expensive joins/aggregations
•	refresh asynchronously (cron/queue)
•	avoid heavy computations during report execution

See: documentation/04-materialized-views.md

⸻

Sockets / Wrenches (Advanced Feature)

Quickrep supports a “socket/wrench” concept for dynamic parameterized reports:
```php
$this->getSocket('someWrenchName');
```

In v1.0.1, sockets/wrenches live in CONFIG DB (PostgreSQL / appapi).
Migrations create:
•	quickrep_socket
•	quickrep_socketsource
•	quickrep_socket_user
•	quickrep_wrench
•	quickrep_meta

This allows interactive parameter selection in reports without hardcoding options.

⸻

Updating Quickrep

In your project root:
```bash
composer update owlookit/quickrep
php artisan quickrep:install
```

If you use view packages like quickrepbladetabular, follow their upgrade notes.

⸻

Uninstall
```bash
composer remove owlookit/quickrep
composer clear-cache
```

Troubleshooting

See: documentation/08-troubleshooting.md

Common issues:
•	prepared statement does not exist → enable PDO::ATTR_EMULATE_PREPARES on CONFIG DB connection
•	syntax error near "\”` → ensure cache connection is Manticore (MySQL protocol)
•	report hangs during refresh → use REFRESH MATERIALIZED VIEW CONCURRENTLY

⸻

License

MIT (see LICENSE file if present).

⸻

Why "Quickrep"?

Quickrep was developed by Owlookit LLC to make reporting on complex LMS/CMS/claims datasets simpler and faster by focusing on SQL-driven report definitions.







