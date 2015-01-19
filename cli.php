#!/usr/bin/php
<?php
/**
 * Command Line Interface.
 */

use NXWSClient\nx;
use Zend\Config\Reader\Ini as IniReader;
use Zend\Config\Writer\Ini as IniWriter;
use NXWSClient\tools;

require_once "vendor/autoload.php";

$nx = new nx();
$nx->bootstrap(TRUE);
