#!/usr/bin/php
<?php
/**
 * Command Line Interface.
 */

use NXWSClient\nx;

require_once "vendor/autoload.php";

$test = new nx(TRUE);

$date_time = array('test');

$test->container['date_time'] = function($c) {
  return array('test');
};

$dt = $test->container['date_time'];

print_r($dt);
