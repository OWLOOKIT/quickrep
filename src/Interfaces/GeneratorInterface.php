<?php
/**
 * Created by PhpStorm.
 * User: owlookit
 * Date: 4/13/18
 * Time: 12:08 PM
 */

namespace Owlookit\Quickrep\Interfaces;


use Owlookit\Quickrep\Models\QuickrepReport;

interface GeneratorInterface
{
    public function addFilter( array $filters );

    public function orderBy( array $orders );

    public function paginate( $length );

    public function init( array $params = null );

    public function toJson();
}
