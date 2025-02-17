<?php
/**
 * Created by PhpStorm.
 * User: owlookit
 * Date: 7/5/18
 * Time: 2:38 PM
 */

namespace Owlookit\Quickrep\Models;

use Illuminate\Support\Facades\DB;

class AbstractGenerator
{
    protected $cache = null;

    protected $_full_table = null;

    protected $_Table = null;
    protected $_filters = [];

    protected $_isManticoreConnection = false;

    public function __construct(DatabaseCache $cache)
    {
        $this->cache = $cache;
        $this->_isManticoreConnection = $this->isManticoreConnection();
    }

    public function addFilter(array $filters)
    {
        $columns = $this->cache->getColumns();
        $textMatchConditions = [];

        foreach ($filters as $field => $value) {

            if (is_array($value)) {

                $numericValues = array_map(function ($v) use ($columns, $field) {

                    if (is_numeric($v)) {
                        return strpos($v, '.') !== false ? (float)$v : (int)$v;
                    }

                    if (isset($columns[$field]) && in_array($columns[$field]['type'], ['datetime', 'timestamp'])) {
                        $v = preg_replace('/\.\d+Z$/', 'Z', $v); // remove milliseconds (Z is UTC)

                        // parse ISO 8601 without milliseconds
                        $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s\Z', $v, new \DateTimeZone($this->cache->getTimezone()));

                        if (!$date) {
                            $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $v);
                        }

                        return $date ? $date->getTimestamp() : null;
                    }

                    return null;
                }, $value);

                if ($value[1] === 'null') {
                    $this->cache->getTable()->where($field, '>=', $numericValues[0]);
                } else {
                    $this->cache->getTable()->whereBetween($field, $numericValues);
                }
                
            } else {

                $urldecodedvalue = urldecode($value);

                if ($field === '_') {
                    $this->cache->getTable()->whereRaw("MATCH('{$urldecodedvalue}')"); // global search (fulltext) on all Manticoresearch TEXT fields
                    continue;
                }

                if (!isset($columns[$field])) {
                    continue;
                }

                $fieldType = $columns[$field]['type'];

                if ($fieldType === 'string') {
                    $this->cache->getTable()->whereRaw("REGEX({$field}, '(?i){$urldecodedvalue}')");
                } elseif ($fieldType === 'text') {
                    $textMatchConditions[] = "@{$field} {$urldecodedvalue}"; // save for later
                } else {
                    $this->cache->getTable()->where($field, $urldecodedvalue);
                }
            }
        }

        // make a single MATCH query for all text fields
        if (!empty($textMatchConditions)) {
            $matchQuery = implode(' ', $textMatchConditions);
            $this->cache->getTable()->whereRaw("MATCH('{$matchQuery}')");
        }
    }

    /**
     * Проверяет, является ли соединение с ManticoreSearch
     *
     * @return bool
     */
    private function isManticoreConnection(): bool
    {
        $connectionName = $this->cache->getConnectionName();
        $driver = config("database.connections.{$connectionName}.driver");
        return $driver === 'manticore';
    }

    public function orderBy(array $orders)
    {
        foreach ($orders as $order) {
            $key = key($order);
            $direction = $order[$key];
            $this->cache->getTable()->orderBy($key, $direction);
        }
    }

    public function cacheTo($destination_database, $destination_table)
    {
        $full_table = "{$destination_database}.{$destination_table}";

        $CacheQuery = clone $this->_Table;
        $sql = $CacheQuery->select("*")->toSql();
        $params = $CacheQuery->getBindings();

        DB::connection(config('quickrep.QUICKREP_DB_CACHE_CONNECTION'))->statement("DROP TABLE IF EXISTS {$full_table}");
        DB::connection(config('quickrep.QUICKREP_DB_CACHE_CONNECTION'))->statement(
            "CREATE TEMPORARY TABLE {$full_table} AS {$sql};",
            $params
        );

        return true;
    }

}
