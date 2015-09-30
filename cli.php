#!/usr/bin/php
<?php
/**
 * Command Line Interface.
 */
use NXWSClient\argv;

require_once "vendor/autoload.php";

try {
  $argv_nx = new argv($argv);
  $argv_nx->run();  
}
catch (\Exception $e) {
  print $e->getMessage() . PHP_EOL;
}
