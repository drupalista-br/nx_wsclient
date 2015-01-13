<?php
namespace NXWSClient\Test;

use NXWSClient\nx;
use NXWSClient\NxTestCase;
use Httpful\Request;
use org\bovigo\vfs\vfsStream;
use Pimple\Container;

$pathinfo = pathinfo(__DIR__);
$root_folder = dirname(dirname($pathinfo['dirname']));

require_once "$root_folder/vendor/autoload.php";

class NxTest extends NxTestCase {

  public $home_folder;
  
  public function setUp() {
	parent::setUp();
	$this->home_folder = vfsStream::setup('home');
  }

  function testBootstrapConfigMethodConfigIniFileDoesNotExist() {
	$this->setExpectedException('\Exception');

	$this->unlockObj = new nx();
	$this->unlockSetMethod('bootstrap_config');
	$this->unlock();
  }

  function testRootFolderPropertyIsSetByBootstrapRootFolderMethod() {
	// Get the value of $nx->root_folder;
	$this->unlockObj = new nx();
	$this->unlockSetProperty('root_folder');
	$this->unlockSetPropertyAction('return');
	$this->unlock();

	$this->assertFalse(is_dir($this->unlockObj));

	// Run $nx->bootstrap_root_folder();
	$this->unlockObj = new nx();
	$this->unlockSetMethod('bootstrap_root_folder');
	$this->unlock();

	// Get the new value of $nx->root_folder;
	$this->unlockSetProperty('root_folder');
	$this->unlockSetPropertyAction('return');
	$this->unlock();

	$this->assertTrue(is_dir($this->unlockObj));
  }

  function testBootstrapConfigMethodLoadsTheConfigIniFile() {
	// Run $nx->bootstrap_root_folder();
	$this->unlockObj = new nx();
	$this->unlockSetMethod('bootstrap_root_folder');
	$this->unlock();
	$nx = $this->unlockObj;

	$this->unlockSetProperty('config');
	$this->unlockSetPropertyAction('return');
	$this->unlock();

	$this->assertTrue(empty($this->unlockObj));

	$this->unlockObj = $nx;
	// Run $nx->bootstrap_config()
	$this->unlockSetMethod('bootstrap_config');
	$this->unlock();

	// Get the value of $nx->config;
	$this->unlockSetProperty('config');
	$this->unlockSetPropertyAction('return');
	$this->unlock();

	$config = $this->unlockObj;

	$this->assertTrue(isset($config['ambiente']));
	$this->assertTrue(is_array($config['endpoint']));
	$this->assertTrue(is_array($config['servicos']));
	$this->assertTrue(is_array($config['pastas']));
	$this->assertTrue(is_array($config['credenciais']));
  }
  
  function testBootstrapFoldersMethod() {
	$this->unlockObj = new nx();
	$this->unlockSetMethod('bootstrap_root_folder');
	$this->unlock();

	$this->unlockSetMethod('bootstrap_config');
	$this->unlock();

	// Keep our partially bootstraped object for later user.
	$nx = $this->unlockObj;

	// Swap $this->unlock->config['tmp'] and $this->unlock->config['tmp']
	$this->unlockSetProperty('config');
	$this->unlockSetPropertyAction('set');
	$config = array(
	  'pastas' => array(
		'tmp' => vfsStream::url('home/tmp'),
		'dados' => vfsStream::url('home/dados'),
	  ),
	);
	$this->unlockSetPropertyNewValue($config);
	$this->unlock();

	$this->unlockSetMethod('bootstrap_folders');
	$this->unlock();

	$this->unlockSetProperty('folders');
	$this->unlockSetPropertyAction('return');
	$this->unlock();

	$folders = $this->unlockObj;

	//print_r($folders);
	$tmp = $folders['tmp'];
	$dados = $folders['dados'];

	$this->assertTrue(is_dir("$dados/produto"));
	$this->assertTrue(is_dir("$dados/consulta"));
	$this->assertTrue(is_dir("$tmp/falhas/produto"));
	$this->assertTrue(is_dir("$tmp/sucessos/produto"));
	$this->assertTrue(is_dir("$tmp/logs"));

	// At this point the bootstrap shouldn't have triggered the merchant
	// authentication process.
	$this->assertFalse(file_exists("$tmp/.session"));

	// Expect output.
	$this->unlockObj = $nx;
	$this->unlockSetMethod('bootstrap_folders');
	$this->unlockSetMethodArgs(array(TRUE));
	$this->unlock();

	$this->unlockSetProperty('folders');
	$this->unlockSetPropertyAction('return');
	$this->unlock();

	$this->expectOutputString("As pastas dados, tmp e suas subpastas foram criadas com sucesso." . PHP_EOL);
  }
}
