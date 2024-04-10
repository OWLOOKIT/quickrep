QuickRep Reporting Engine: Guide to Initial Setup
========

An efficient PHP reporting engine optimized for Laravel, crafted with dedication at [Owlookit Systems](https://owlookit.com)


## Guide to Initial Setup
1. First, ensure your database is set up. Within the root directory of your project, input your database details in `.env` or within the `config/database.php` of your application. The database account should have the CREATE TABLE privilege to facilitate the creation of the \_quickrep database (applicable if you're setting up sample data.) For the `DB_DATABASE` parameter, use 'northwind_data' if you're incorporating sample data and reports. If integrating with an existing database, input its name in the `DB_DATABASE` slot. Remember to substitute the username and password with appropriate values. For those unfamiliar with this process, consider reviewing [Securing Your PostgreSQL Installation](https://www.postgresql.org/docs/current/security.html).

    ```
    DB_CONNECTION=pgsql
    DB_HOST=127.0.0.1
    DB_PORT=5432
    DB_DATABASE=northwind_data
    DB_USERNAME=choose_a_username
    DB_PASSWORD=create_a_strong_password_here
    ```

2. Execute the following commands in the command prompt at your Laravel **project's root** to install QuickRep:

    ```
    composer require owlookit/quickrep_installer
    php artisan quickrep:install
    ```

This process retrieves the quickrep package, which includes the API backend and all available view packages, such as quickrepbladetabular and quickrepbladecard. It also relocates quickrep resources like JavaScript and CSS files to support the view packages into the public/vendor/owlookit directory.

Following these steps adds a new `app/Reports` directory to your Laravel project for storing Quickrep report classes.

The installation process also establishes a `_quickrep_cache` database and a `_quickrep_config` database. The cache database maintains a cached view of the query results for each report, configurable within the report class. The config database stores report configuration data, including the socket/wrench variables injectable into report queries.


## Verifying Web Routes (default):

'Quickrep' routes should now be visible in your Laravel setup

To list your routes:

$ php artisan route:list | grep Quickrep
|        | GET|HEAD | Quickrep/{report_name}/{parameters?}            |      | Closure |              |
|        | GET|HEAD | api/Quickrep/{report_name}/{parameters?}        |      | Closure |              |
|        | GET|HEAD | api/QuickrepSummary/{report_name}/{parameters?} |      | Closure |              |



## TROUBLESHOOTING

For any issues, consult the [Troubleshooting](documentation/Troubleshooting.md) manual.
