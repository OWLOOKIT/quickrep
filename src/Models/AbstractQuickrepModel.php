<?php
/**
 * Created by PhpStorm.
 * User: owlookit
 * Date: 9/6/22
 * Time: 2:26 PM
 */

namespace Owlookit\Quickrep\Models;


use Illuminate\Database\Eloquent\Model;

abstract class AbstractQuickrepModel extends Model
{
    protected $connection = null;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // We use the quickrep config DB for our "in-house" models
        $this->connection = config('quickrep.QUICKREP_DB_CONNECTION');
    }
}