# QuickRep Reporting Engine Troubleshooting

An efficient PHP reporting engine, ideally suited for Laravel, designed with passion at [Owlookit Systems](https://owlookit.com).

## Installation Issues

If you encounter an error during the `php artisan quickrep:install` process similar to:
```
SQLSTATE[HY000] [2002] Connection refused (SQL: CREATE DATABASE IF NOT EXISTS `_quickrep_cache`;)

You may not have permission to the database `_quickrep_cache` to query its existence.
* `root` may have insufficient permissions and you may have to run the following command:
	GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, LOCK TABLES ON `_quickrep_cache`.* TO 'root'@'localhost';
```

This could indicate a lack of permissions for the `_quickrep_cache` database. Consider the following:
* Your `root` user may not have the necessary permissions. You might need to execute:
  ```
  GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, LOCK TABLES ON `_quickrep_cache`.* TO 'root'@'localhost';
  ```

* Double-check the `.env` file in the root of your Laravel project. Confirm that the host, port, username, and password for your PostgreSQL connection are accurate.
* If you're using tools like XAMPP or MAMP, the PostgreSQL port might differ from the default. Make sure to use the correct port number.
* Examine the PostgreSQL user table to ensure the 'host' field is accurate. You may need to switch from '127.0.0.1' to 'localhost' or apply the appropriate GRANT statement for the correct permissions.

## Post-Installation Troubles

If your reports fail to execute, you're met with a white or blank screen, consider the following:

* Inspect the `[project-root]/storage/logs/laravel.log` for any errors.

Encountering a 404 error when navigating to your report URL can be due to a few reasons:

* Confirm that the URL is correct. Use `php artisan route:list` to verify that your route is registered.
* Ensure that your report file is correctly placed in the App directory and has the correct namespace.
* The report class must inherit from `QuickrepReport` for the engine to detect and process it.
