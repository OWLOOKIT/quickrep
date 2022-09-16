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

    public function __construct( DatabaseCache $cache )
    {
        $this->cache = $cache;
    }

    public function addFilter(array $filters)
    {
        foreach($filters as $field=>$value)
        {
            $urldecodedvalue =urldecode($value);
            if($field == '_')
            {
                $fields = QuickrepDatabase::getTableColumnDefinition( $this->cache->getTableName(), quickrep_cache_db() );
                $this->cache->getTable()->where(function($q) use($fields,$urldecodedvalue)
                {
                    foreach ($fields as $field) {
                        $field_name = $field['name'];
                        $q->orWhere($field_name, 'LIKE', '%' . $urldecodedvalue . '%');
                    }
                });
            } else
            {
                $this->cache->getTable()->Where($field,'LIKE','%'.$urldecodedvalue.'%');
            }
        }
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

        DB::connection(config('database.statistics'))->statement("DROP TABLE IF EXISTS {$full_table}");
        DB::connection(config('database.statistics'))->statement("CREATE TEMPORARY TABLE {$full_table} AS {$sql};",$params);

        return true;
    }

}
