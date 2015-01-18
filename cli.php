#!/usr/bin/php
<?php
/**
 * Command Line Interface.
 */

use NXWSClient\nx;
use Zend\Config\Reader\Ini as IniReader;
use NXWSClient\tools;

require_once "vendor/autoload.php";

$test = new FilesystemIterator("/home/francisco/drupalista-br/nx_wsclient/tmp");
foreach ($test as $object) {
  print $object->getBasename();
  print "\n";  
}



exit();
$nx = new nx();
$nx->bootstrap(TRUE);
$nx->get_cities();
