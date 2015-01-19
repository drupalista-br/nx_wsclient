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
//$nx->container['internet_connection_google'] = 'localhost';
//$nx->container['internet_connection_nortaox'] = 'localhost';
$nx->container['config_producao_uri'] = 'http://loja.nortaox.local/api';

$nx->check();
