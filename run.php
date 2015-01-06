<?php
use \NXWSClient\nx;
use \Zend\Config\Writer\Ini;

require_once "vendor/autoload.php";

$config = parse_ini_file('config.ini', TRUE);

$writer = new Ini();
$writer->toFile('test.ini', $config);


exit();
$test = new nx();

$qs = array('campo' => 'sku');

print_r( $test->update() );

