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
    $db = config("quickrep.QUICKREP_CACHE_DB");
    if (empty($db)) {
        info("Quickrep Cache DB not set in quickrep.php config file.");
    }
    return $db;
}

function quickrep_config_db()
{
    $db = config("quickrep.QUICKREP_CONFIG_DB");
    if (empty($db)) {
        info("Quickrep Config DB not set in quickrep.php config file.");
    }
    return $db;
}

function report_path()
{
    $reportNS = config("quickrep.REPORT_NAMESPACE");
    $parts = explode("\\", $reportNS);
    return app_path($parts[count($parts) - 1]);
}



