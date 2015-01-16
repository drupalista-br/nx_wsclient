<?php
namespace NXWSClient\Test;

use NXWSClient\nx,
	NXWSClient\NxTestCase,
	Httpful\Request,
	org\bovigo\vfs\vfsStream,
	Pimple\Container,
	Zend\Config\Reader\Ini as IniReader;

$pathinfo = pathinfo(__DIR__);
$root_folder = dirname(dirname($pathinfo['dirname']));

require_once "$root_folder/vendor/autoload.php";

class NxTest extends NxTestCase {
  public $root_folder;
  
  public function setUp() {
	parent::setUp();

	$pathinfo = pathinfo(__DIR__);
	$root_folder = dirname(dirname($pathinfo['dirname']));
	$this->root_folder = $root_folder;

	vfsStream::setup('home');
  }

  function testLogMethod() {
	$output = "A internet esta acessivel e o website da NortaoX.com esta responsivo." . PHP_EOL;
	$output .= "Endpoint http://loja.nortaox.local/api esta acessivel." . PHP_EOL;
	$output .= "As pastas dados, tmp e suas subpastas foram criadas com sucesso." . PHP_EOL;
	$output .= "O servidor do gmail respondeu Ok. As credencais do email nortaox.webservice.client@gmail.com sao validas." . PHP_EOL;

	$this->expectOutPutString($output);

	$root_folder = $this->root_folder;

	$date = new \DateTime();
	$date_gis = $date->format('G:i:s');
	$date_ymd = "logtest";
	$log_file = "$root_folder/tmp/logs/$date_ymd.log";

	$this->assertFalse(file_exists($log_file));

	$nx = new nx();	
	$nx->container['date_gis'] = $date_gis;
	$nx->container['date_time_gis'] = function($c) {
	  return $c['date_gis'];
	};

	$nx->container['date_ymd'] = $date_ymd;
	$nx->container['date_time_ymd'] = function($c) {
	  return $c['date_ymd'];
	};
	$nx->check(TRUE);

	$msg_1 = "1. PHPUnit." . PHP_EOL;
	$msg_2 = "2. PHPUnit." . PHP_EOL;

	$this->unlockObj = $nx;
	$this->unlockSetMethod('log');
	$this->unlockSetMethodArgs(array($msg_1));
	$this->unlock();

	$this->unlockObj = $nx;
	$this->unlockSetMethod('log');
	$this->unlockSetMethodArgs(array($msg_2));
	$this->unlock();

	$file_content_prediction = "----$date_gis----" . PHP_EOL;
	$file_content_prediction .= $msg_1;
	$file_content_prediction .= "----$date_gis----" . PHP_EOL;
	$file_content_prediction .= $msg_2;

	$file_content = file_get_contents($log_file);

	$this->assertTrue(file_exists($log_file));
	$this->assertTrue($file_content_prediction == $file_content);
	//unlink($log_file);
  }

  function testBootstrapMethodIsDevTrue() {
	$nx = new nx();
	$nx->bootstrap(TRUE);
  }

  /*function testGetCitiesMethod() {
	$root_folder = $this->root_folder;

	if (file_exists("$root_folder/dados/consulta/cidades.txt")) {
	  unlink("$root_folder/dados/consulta/cidades.txt");
	}

	$this->assertFalse(file_exists("$root_folder/dados/consulta/cidades.txt"));

	$nx = new nx();
	$nx->bootstrap(TRUE);
	$nx->get_cities();

	$this->assertTrue(file_exists("$root_folder/dados/consulta/cidades.txt"));

	$reader = new IniReader();
	$cities = $reader->fromFile("$root_folder/dados/consulta/cidades.txt");

	$this->assertTrue(isset($cities[0]['cod_cidade']));
	$this->assertTrue(isset($cities[0]['nome']));
	$this->assertTrue(isset($cities[0]['status']));
  }*/
}
