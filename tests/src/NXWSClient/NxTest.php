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

	$this->unlockSetProperty('config');
	$this->unlockSetPropertyAction('return');
	$this->unlock();

	$this->assertTrue(empty($this->unlockObj));

	// Run $nx->bootstrap_root_folder();
	$this->unlockObj = new nx();
	$this->unlockSetMethod('bootstrap_root_folder');
	$this->unlock();

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
	
  }
}
