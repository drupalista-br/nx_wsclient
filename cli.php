#!/usr/bin/php
<?php
/**
 * Command Line Interface.
 */

use NXWSClient\nx;
use Zend\Config\Reader\Ini as IniReader;

require_once "vendor/autoload.php";
$nx = new nx();
$nx->check(TRUE);

exit();
$a1=array("a"=>array("red"),"b"=>"green","c"=>"blue", "d"=>"pink");
$a2=array("a"=>"red","c"=>"blue 2","d"=>"pink");

$result=array_diff_assoc($a1,$a2);
print_r($result);




