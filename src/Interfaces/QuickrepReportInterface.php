<?php
/**
 * Created by PhpStorm.
 * User: owlookit
 * Date: 4/13/18
 * Time: 12:14 PM
 */

namespace Owlookit\Quickrep\Interfaces;


interface QuickrepReportInterface
{
    public function pushViewVariable($name, $value);

    public function setToken($token);

    public function isSQLPrintEnabled();
}
