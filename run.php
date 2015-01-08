#!/usr/bin/php
<?php
use \NXWSClient\argv;

require_once "vendor/autoload.php";
try {
  $it = new FilesystemIterator("config.ini");
  foreach ($it as $fileinfo) {
	print $fileinfo->getFilename() . "\n";
  }
}
catch(Exception $e) {
  print $e->getMessage() . "\n";
}

exit();

/*$config = parse_ini_file('config.ini', TRUE);

$writer = new Ini();
$writer->toFile('test.ini', $config);*/

$test = new nx(FALSE, TRUE);

$qs = array('campo' => 'sku');

print_r( $test->get_cities() );

