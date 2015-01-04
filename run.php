<?php
use \NXWSClient\nx;

require_once "vendor/autoload.php";

$test = new nx();

$qs = array('campo' => 'sku');

print_r( $test->retrieve('55') );

