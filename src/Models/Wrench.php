<?php
/**
 * Created by PhpStorm.
 * User: owlookit
 * Date: 9/6/22
 * Time: 2:26 PM
 */

namespace Owlookit\Quickrep\Models;

class Wrench extends AbstractQuickrepModel
{
    protected $table = 'wrench';

    protected $fillable = ['wrench_lookup_string', 'wrench_label'];

    /*
     * Eager-load the sockets
     */
    protected $with = ['sockets'];

    public function sockets()
    {
        return $this->hasMany(Socket::class )->orderBy('socket_label');
    }
}
