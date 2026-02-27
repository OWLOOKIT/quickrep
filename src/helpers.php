<?php

function api_prefix()
{
    $api_prefix = trim(config("quickrep.API_PREFIX"), "/ ");
    return $api_prefix;
}

function tree_api_prefix()
{
    $api_prefix = trim(config("quickrep.TREE_API_PREFIX"), "/ ");
    return $api_prefix;
}

function tabular_api_prefix()
{
    $api_prefix = trim(config("quickrep.TABULAR_API_PREFIX"), "/ ");
    return $api_prefix;
}

function graph_api_prefix()
{
    $api_prefix = trim(config("quickrep.GRAPH_API_PREFIX"), "/ ");
    return $api_prefix;
}

function quickrep_cache_db()
{
    // return connection name
    $conn = config('quickrep.QUICKREP_CACHE_DB_CONNECTION')
        ?: config('quickrep.QUICKREP_DB_CACHE_CONNECTION')
            ?: 'manticore';

    if (empty($conn)) {
        info("Quickrep Cache DB connection not set in quickrep.php config file.");
    }
    return $conn;
}

function quickrep_config_db()
{
    // return connection name (recommended)
    $conn = config('quickrep.QUICKREP_CONFIG_DB_CONNECTION') ?: 'pgsql';
    if (empty($conn)) {
        info("Quickrep Config DB connection not set in quickrep.php config file.");
    }
    return $conn;
}

function report_path()
{
    $reportNS = config("quickrep.REPORT_NAMESPACE");
    $parts = explode("\\", $reportNS);
    return app_path($parts[count($parts) - 1]);
}

function quickrep_source_db()
{
    $conn = config('quickrep.QUICKREP_SOURCE_DB_CONNECTION')
        ?: config('quickrep.QUICKREP_DB_CONNECTION')
            ?: 'appstats';
    if (empty($conn)) {
        info("Quickrep Source DB connection not set in quickrep.php config file.");
    }
    return $conn;
}

// Legacy: database *names* (not connections). Keep for compatibility if something still uses them.
function quickrep_cache_database_name()
{
    return config("quickrep.QUICKREP_CACHE_DB") ?: "_quickrep_cache";
}

function quickrep_config_database_name()
{
    return config("quickrep.QUICKREP_CONFIG_DB") ?: "_quickrep_config";
}
