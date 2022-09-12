<?php
/**
 * Created by PhpStorm.
 * User: owlookit
 * Date: 4/8/19
 * Time: 11:15 AM
 *
 * Wrapper for a database connection
 */

namespace Owlookit\Quickrep\Models;


class QuickrepDatabaseConnection
{
    protected $connectionName = '';

    public function __construct( $connectionName )
    {
        $this->connectionName = $connectionName;
    }

    public function connectionName()
    {
        return $this->connectionName;
    }

}