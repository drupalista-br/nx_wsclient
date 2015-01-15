#!/usr/bin/php
<?php
/**
 * Command Line Interface.
 */

use NXWSClient\nx;
use Zend\Config\Reader\Ini as IniReader;

require_once "vendor/autoload.php";

$a1=array("a"=>array("red"),"b"=>"green","c"=>"blue", "d"=>"pink");
$a2=array("a"=>"red","c"=>"blue 2","d"=>"pink");

$result=array_diff_assoc($a1,$a2);
print_r($result);


  $config = array(
	'ambiente' => 'producao',
	'endpoint' => array(
	  'sandbox' => 'http://loja.nortaoxsandbox.tk/api',
	  'producao' => 'https://loja.nortaox.com/api',
	  'dev' => 'http://loja.nortaox.local/api',
	),
	'servicos' => array(
	  'login' => 'user/login',
	  'produto' => 'produto',
	  'pedido' => 'pedido-consultar',
	  'cidades' => 'cidades',
	),
	'pastas' => array(
	  'dados' => '%app%/dados',
	  'tmp' => '%app%/tmp',
	),
	'servidor_smtp' => array(
	  'From' => 'drupalista.com.br@gmail.com',
	  'Username' => 'drupalista.com.br@gmail.com',
	  'Password' => 'wash4444',
	),
	'notificar' => array(
	  array('email' => 'franciscoferreiraluz@yahoo.com.au'),
	),
	'credenciais' => array(
	  'username' => 'Francisco Luz',
	  'password' => 'teste',
	),
  );

