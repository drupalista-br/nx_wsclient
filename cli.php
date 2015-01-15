#!/usr/bin/php
<?php
/**
 * Command Line Interface.
 */

use NXWSClient\nx;
use Zend\Config\Reader\Ini as IniReader;

require_once "vendor/autoload.php";
$nx = new nx();
$nx->internet_connection();





