<?php
/**
 * Created by PhpStorm.
 * User: owlookit
 * Date: 9/6/22
 * Time: 2:26 PM
 */

namespace Owlookit\Quickrep\Models;

class Socket extends AbstractQuickrepModel
{
    protected $table = 'socket';

    protected $fillable = ['wrench_id', 'socket_value', 'socket_label', 'is_default_socket', 'socketsource_id'];

    public function wrench()
    {
        return $this->belongsTo(Wrench::class);
    }
}
