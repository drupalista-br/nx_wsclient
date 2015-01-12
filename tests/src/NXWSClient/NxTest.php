<?php
namespace NXWSClient\Test;

use NXWSClient\nx;
use org\bovigo\vfs\vfsStream;
use Pimple\Container;

$pathinfo = pathinfo(__DIR__);
$root_folder = dirname(dirname($pathinfo['dirname']));

require_once "$root_folder/vendor/autoload.php";

class NxTest extends \PHPUnit_Framework_TestCase {

  function testBootstrapConfigMethodConfigIniDoesNotExist() {
	/*//$nx = new nx();
	$nx = $this->getMockBuilder('NXWSClient\nx')
	  //->setMethods(array('bootstrap_config'))
	  ->getMock();

	$nx->method('halt')
	  ->willReturn('foo');*/

	$nx = new nx();
	$this->setExpectedException('\Exception');
	$this->unlock($nx, 'bootstrap_config');
  }

  function testRootFolderPropertyIsSetByBootstrapRootFolderMethod() {
	$nx = new nx();
	$this->unlock($nx, 'bootstrap_root_folder');
	$root_folder = $this->unlock($nx, 'root_folder', array(), 'property');

	$this->assertTrue(is_dir($root_folder));
  }

  function testBootstrapConfigMethodLoadsTheConfigFile() {
	$nx = new nx();
	$this->unlock($nx, 'bootstrap_root_folder');
	$this->unlock($nx, 'bootstrap_config');

	$config = $this->unlock($nx, 'config', array(), 'property');

	$this->assertTrue(isset($config['ambiente']));
	$this->assertTrue(is_array($config['endpoint']));
	$this->assertTrue(is_array($config['servicos']));
	$this->assertTrue(is_array($config['pastas']));
	$this->assertTrue(is_array($config['credenciais']));
  }

  /**
   * Accesses private members of an object.
   */
  public function unlock(&$obj, $memberName, $args = array(), $type = 'method') {
	switch($type) {
	  case 'method':
		$unlock = function(&$obj, $memberName, $args) {
		  if (!empty($args)) {
			return call_user_func_array(array($obj, $memberName), $args);
		  }

		  $obj->{$memberName}();
		};

		$unlock = \Closure::bind($unlock, null, $obj);
		$unlock($obj, $memberName, $args);
	  break;
	  case 'property':
		$unlock = function($obj, $memberName) { return $obj->{$memberName}; };
	
		$unlock = \Closure::bind($unlock, null, $obj);
		return $unlock($obj, $memberName);
	  break;
	}
  }
}
