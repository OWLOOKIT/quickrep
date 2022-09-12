<?php
/**
 * Created by PhpStorm.
 * User: owlookit
 * Date: 9/6/22
 * Time: 2:26 PM
 */

namespace Owlookit\Quickrep\Models;

class QuickrepMeta extends AbstractQuickrepModel
{
    protected $table = 'quickrep_meta';

    protected $fillable = ['key', 'meta_key', 'meta_value'];
}