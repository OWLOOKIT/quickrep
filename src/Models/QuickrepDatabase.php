<?php
/**
 * Created by PhpStorm.
 * User: owlookit
 * Date: 29/07/22
 * Time: 9:19 AM
 *
 * This is a class of database helpers to perform common operations,
 * like dynamically configuring a database with Laravel, checking if
 * database exists, getting meta data, etc.
 */

namespace Owlookit\Quickrep\Models;


use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuickrepDatabase
{
    public static function configure( $database )
    {
        //
        $default = config( 'database.statistics' );
        Config::set( 'database.connections.'.$database, [
            'driver' => config( "database.connections.$default.driver" ),
            'host' => config( "database.connections.$default.host" ),
            'port' => config( "database.connections.$default.port" ),
            'database' => $database,
            'username' => config( "database.connections.$default.username" ),
            'password' => config( "database.connections.$default.password" ),
        ] );

        // @TODO: make a MySQL fallback
        // Set the max concat length for cache DB to be A LOT
        // This will also throw an exception if the DB doesn't exist

        $session_set_sql = "SET SESSION group_concat_max_len = 1000000;";
        // this way is no longer compatible with laravel 10
        // DB::connection($database)->statement(DB::raw($session_set_sql));
        // lets get the raw PDO instead.
        $pdo = DB::connection()->getPdo();
        $pdo->exec($session_set_sql);
    }

    public static function hasTable( $table_name, $connectionName )
    {
        return Schema::connection( $connectionName )->hasTable( $table_name );
    }

    public static function drop( $table_name, $connectionName )
    {
        return Schema::connection( $connectionName )->drop( $table_name );
    }


    public static function connection($connectionName)
    {
        try {
            return DB::connection($connectionName);
        } catch(\Exception $e) {
            $message = $e->getMessage()." You may have a permissions error with your database user. Please Refer to the Quickrep troubleshooting guide <a href='https://github.com/Owlookit/Quickrep#troubleshooting'>https://github.com/Owlookit/Quickrep#troubleshooting</a>";
            throw new \Exception($message, (int) $e->getCode(), $e);
        }
    }

    /**
     * @param $database
     * @return bool|null
     * @throws \Exception
     *
     * Returns true if DB exists, and false if it does not, NULL if the state of existence cannot be determined.
     */
    public static function doesDatabaseExist( $database )
    {
        // @TODO: MySQL fallback
//        $query = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME =  ?";
        $query = "SELECT datname FROM pg_database WHERE datname = ?";

        // In case the database in the database.php or .env file doesn't exist, we can safely
        // set this to null so the select call will work, otherwise, we get a mysterious error
//	$previous_mysql_database = config('database.connections.appstats.database');
//        config(["database.connections.mysql.database" => null]);

        try {
            $db = DB::connection(config('database.statistics'))->select( $query, [ $database ] );
        } catch ( \Exception $e ) {

            if ($e->getCode() == 1049) {
                // If the database in our configuration file doesn't exist, we have a problem,
                // So let's blow up.
                throw new \Exception($e->getMessage()."\n\nPlease make sure the database in your .env file exists.", (int) $e->getCode());
            } else if ($e->getCode() == 1045) {
                // If the user doens't have authorization, we have a problem.
                $default = config( 'database.default' ); // Get default connection
                $username = config( "database.connections.$default.username" ); // Get username for default connection
                $message = "\n\nPlease check your user credentials and permissions and try again. Here are some suggestions:";
                $message .= "\n* `$username` may not exist.";
                $message .= "\n* `$username` may have the incorrect password in your .env file.";
                throw new \Exception($e->getMessage().$message, (int) $e->getCode());
            } else if ($e->getCode() == 1044) {
                // Access Denied
                $default = config( 'database.default' ); // Get default connection
                $default_db = config( "database.connections.$default.database" );
                $username = config( "database.connections.$default.username" ); // Get username for default connection
                $message = "\n\nPlease make sure that your mysql user in your .env file has permissions on `$default_db`.";
                $message .= "\n* Run this mysql command to list users who have access:\n";
                $message .= "\tSELECT user from mysql.db where db='$default_db';"; // SHOW GRANTS FOR ken@localhost;;
                $message .= "\n* `$username` may have insufficient permissions and you may have to run the following command:\n";
                $message .= "\tGRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, LOCK TABLES ON `$default_db`.* TO '$username'@'localhost';\n";
                throw new \Exception($e->getMessage().$message, (int) $e->getCode());
            }

            $db = null;
        }

        //now that this is done, lets restore the previous database
//       config(["database.connections.appstats.database" => $previous_mysql_database]);


        // The DB exists if the schema name in the query matches our database
        $db_exists = false;
        if ( is_array($db) &&
            isset($db[0]) &&
            $db[0]->datname == $database
//            $db[0]->SCHEMA_NAME == $database // @TODO: test a MySQL fallback
        ) {
            $db_exists = true;
        } else {
            // Let's make sure that the database REALLY doesn't exist, not that we just don't have permission to see
            try {
                if (\DB::connection(config('database.statistics'))->getDriverName()=='pgsql') {
                    $query = <<<SQL
DO $$ DECLARE
    r RECORD;
BEGIN
    FOR r IN (SELECT tablename FROM pg_tables WHERE schemaname = current_schema()) LOOP
        EXECUTE 'DROP TABLE IF EXISTS ' || quote_ident(r.tablename) || ' CASCADE';
    END LOOP;
END $$;
SQL;
                    $db_exists = true;
                } else {
                    $query = "CREATE DATABASE IF NOT EXISTS `$database`;";
                }
                DB::connection(config('database.statistics'))->statement( $query );
            } catch ( \Exception $e ) {
                $default = config( 'database.statistics' ); // Get default connection
                $username = config( "database.connections.$default.username" ); // Get username for default connection
                $message = "\n\nYou may not have permission to the database `$database` to query its existence.";
                $message .= "\n* `$username` may have insufficient permissions and you may have to run the following command:\n";
                $message .= "\tGRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, LOCK TABLES ON `$database`.* TO '$username'@'localhost';\n";
                throw new \Exception($e->getMessage().$message, (int) $e->getCode());
            }
        }

        if (\DB::connection(config('database.statistics'))->getDriverName()=='pgsql') {
            # @TODO: refactor this to avoid deadlocks
            # https://stackoverflow.com/questions/40728788/drop-foreign-schema-in-postgresql-using-a-foreign-data-wrapper
            $query = <<<SQL
do
$$
declare
  l_rec record;
begin
  for l_rec in (select foreign_table_schema, foreign_table_name 
                from information_schema.foreign_tables) loop
     execute format('drop foreign table %I.%I', l_rec.foreign_table_schema, l_rec.foreign_table_name);
  end loop;
    IMPORT FOREIGN SCHEMA public EXCEPT (migrations, files, file_links, failed_jobs, json_api_client_jobs, media, oauth_access_tokens, oauth_refresh_tokens, temporary_uploads, user_networks) FROM SERVER app_server INTO public;
end;
$$
SQL;
            DB::connection(config('database.statistics'))->statement($query);
        }

        if ($db_exists) {
            return true;
        }

        if ($db_exists === false) {
            return false;
        }

        return null;
    }

    /**
     * basicTypeFromNativeType
     * Simple way to determine the type of the column.
     * It can return: integer,decimal,string
     *
     * @param string $native
     * @return string
     */
    public static function basicTypeFromNativeType(string $native)
    {
        if (strpos($native, "int") !== false) {
            return "integer";
        }
        if (strpos($native, "double") !== false) {
            return "decimal";
        }
        if (strpos($native, "decimal") !== false || strpos($native, "float") !== false) {
            $reg = '/^(\w+)\((\d+?),(\d+)\)$/i';
            if (preg_match($reg, $native, $matches)) {
                $type = $matches[1];
                $len = $matches[2];
                $precision = $matches[3];
                if ($precision > 0) {
                    return "decimal";
                }

                return "integer";
            }
        }

        if (strpos($native, "varchar") !== false || strpos($native, "text") !== false) {
            return "string";
        }

        if ($native == "date" || $native == "time" || $native == "datetime") {
            return $native;
        }

        if ($native == "timestamp") {
            return "datetime";
        }

        return "string";
    }

    /**
     * getTableColumnDefinition
     * Get the column name and the basic column data type (integer, decimal, string)
     *
     * @return array
     */
    public static function getTableColumnDefinition( $table_name, $connectionName ): array
    {
        // @TODO: make a MySQL fallback
        // $query = "SHOW COLUMNS FROM {$table_name}";
        $table_name = strtolower($table_name);
        $query = <<<SQL
                    SELECT *
                      FROM information_schema.columns
                     WHERE table_schema = 'public'
                       AND table_name   = '{$table_name}';
SQL;
        $result = self::connection($connectionName)->select($query);
        if ($result) {
            $column_meta = [];
            foreach ($result as $column) {
                $column_meta[$column->column_name] = [
//                    'Name' => $column->Field,
//                    'Type' => self::basicTypeFromNativeType($column->Type),
                    'name' => $column->column_name,
                    'type' => self::basicTypeFromNativeType($column->data_type),
                ];
            }
        } else {
            throw new \Exception("Could not execute `SHOW COLUMNS FROM {$table_name}`");
        }

        return $column_meta;
    }

    /**
     * isColumnInKeyArray
     * * Will take a column name and convert it into a word array to be passed to isWordInArray
     *
     * @param string $column_name
     * @param array $key_array
     * @return bool
     */
    public static function isColumnInKeyArray(string $column_name, array $key_array): bool
    {
        $column_name = strtoupper($column_name);
        /*
        Lets split the column name into 'words' and ucasing it
         */
        $words = ucwords(str_replace('_', ' ', $column_name), "\t\r\n\f\v ");
        $words = explode(" ", $words);

        $key_array = array_map('strtoupper', $key_array);
        if (in_array($column_name, $key_array)) {
            return true;
        }

        return self::isWordInArray($words, $key_array);
    }


    /**
     * isWordInArray
     * Determine if any word stub is inside a list of key words
     * Example: when $neddle is ['GROUP','ID'] and $haystack is ['ID'], then result will be true
     * This will also return true if $needle is ['GROUP','ID'] and the $haystack is ['GROUP_ID']
     *
     * @param array $needles
     * @param array $haystack
     * @return bool
     */
    protected static function isWordInArray(array $needles, array $haystack): bool
    {
        $full_needle = strtoupper(trim(implode(" ", $needles)));
        foreach ($haystack as $value) {
            $value = strtoupper($value);
            if (in_array($value, $needles) || $value == $full_needle) {
                return true;
            }

        }
        return false;
    }
}
