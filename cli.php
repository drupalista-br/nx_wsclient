#!/usr/bin/php
<?php
/**
 * Command Line Interface.
 */

use NXWSClient\nx;

require_once "vendor/autoload.php";

$test = new nx(TRUE);

$test->container['scan_folder_path'] = '/home/francisco/drupalista-br/nx_wsclient';

$folders = $test->container['scan_folder'];

foreach($folders as $folder) {
  print $folder->getFilename() . "\n";
}