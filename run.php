#!/usr/bin/php
<?php
use \NXWSClient\argv;
use \NXWSClient\nx;

require_once "vendor/autoload.php";

$array = parse_ini_file('/home/francisco/drupalista-br/nx_wsclient/dados/consulta/cidades.txt', TRUE);

print current(array_keys($array));

print "\n";
exit();
/*
$item_data_folder = __DIR__;
try {
  $files = new FilesystemIterator($item_data_folder);
  foreach ($files as $fileinfo) {
	if ($fileinfo->isFile()) {
	  $file_name = $fileinfo->getFilename();
	  $file = $item_data_folder . "/$file_name";
	  if (filesize($file) === 0) {
		print filesize($file) . " $file_name\n";
	  }

	  //print_r(get_class_methods($fileinfo));
	}
  }
}
catch(Exception $e) {
  print $e->getMessage() . "\n";
}

exit();*/

/*$config = parse_ini_file('config.ini', TRUE);

$writer = new Ini();
$writer->toFile('test.ini', $config);*/

$test = new nx(TRUE);

print_r( $test->check() );

